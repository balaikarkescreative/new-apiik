<?php

namespace App\Http\Controllers;

use App\Models\VideoPanduan;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Stream video with range request support
     */
    public function stream(Request $request, $id)
    {
        try {
            \Log::info('Video stream request started', ['id' => $id]);

            $video = VideoPanduan::findOrFail($id);
            \Log::info('Video found', ['video_id' => $video->id, 'title' => $video->title]);

            if (! $video->videoFileExists()) {
                \Log::error('Video file does not exist');
                abort(404, 'Video file not found');
            }

            $filePath = $video->getVideoFullPathAttribute();
            \Log::info('File path resolved', ['path' => $filePath]);

            if (! file_exists($filePath)) {
                \Log::error('File does not exist on disk', ['path' => $filePath]);
                abort(404, 'Video file not found on disk');
            }

            $fileSize = filesize($filePath);
            \Log::info('File size calculated', ['size' => $fileSize]);

            // Handle range request
            $headers = [
                'Content-Type' => 'video/mp4',
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $fileSize,
                'Cache-Control' => 'no-cache',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'Range',
                'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges',
            ];

            $rangeHeader = $request->header('Range');
            \Log::info('Range header', ['range' => $rangeHeader]);

            if ($rangeHeader) {
                \Log::info('Processing range request');

                return $this->handleRangeRequest($filePath, $fileSize, $rangeHeader, $headers);
            }

            \Log::info('Returning full file');

            // Return full file if no range requested
            return response()->file($filePath, $headers);
        } catch (\Exception $e) {
            \Log::error('Unexpected error in stream method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Internal Server Error: '.$e->getMessage());
        }
    }

    /**
     * Handle HTTP range requests for partial content
     */
    private function handleRangeRequest(string $filePath, int $fileSize, string $rangeHeader, array $baseHeaders)
    {
        if (! preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            abort(416, 'Range Not Satisfiable');
        }

        $start = $matches[1] === '' ? 0 : (int) $matches[1];
        $end = $matches[2] === '' ? $fileSize - 1 : (int) $matches[2];

        if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
            abort(416, 'Range Not Satisfiable');
        }

        $length = $end - $start + 1;

        // Batasi ukuran chunk (opsional, untuk performa)
        $maxChunk = 2 * 1024 * 1024; // 2MB
        if ($length > $maxChunk) {
            $end = $start + $maxChunk - 1;
            $length = $maxChunk;
        }

        $headers = array_merge($baseHeaders, [
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Cache-Control' => 'public, max-age=31536000', // cache lama
        ]);

        return response()->stream(function () use ($filePath, $start, $end) {
            $file = fopen($filePath, 'rb');
            if (! $file) {
                abort(500, 'Cannot open file');
            }

            // Lewati ke posisi start
            fseek($file, $start);

            $remaining = $end - $start + 1;
            while ($remaining > 0 && ! feof($file)) {
                $chunkSize = min(8192, $remaining);
                echo fread($file, $chunkSize);
                flush(); // penting untuk streaming
                $remaining -= $chunkSize;
            }

            fclose($file);
        }, 206, $headers);
    }

    /**
     * Get video metadata for player
     */
    public function metadata(Request $request, $id)
    {
        $video = VideoPanduan::findOrFail($id);

        if (! $video->videoFileExists()) {
            abort(404, 'Video file not found');
        }

        $filePath = $video->getVideoFullPathAttribute();
        $fileSize = filesize($filePath);

        // Get video duration using ffprobe if available
        $duration = null;
        if (function_exists('shell_exec') && is_executable('/usr/bin/ffprobe')) {
            $command = "/usr/bin/ffprobe -v quiet -print_format json -show_format -show_streams '{$filePath}'";
            $output = shell_exec($command);
            if ($output) {
                $probe = json_decode($output, true);
                if (isset($probe['format']['duration'])) {
                    $duration = (float) $probe['format']['duration'];
                }
            }
        }

        return response()->json([
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'size' => $fileSize,
            'size_formatted' => $video->video_size,
            'duration' => $duration,
            'url' => route('video.stream', $video->id),
        ]);
    }

    /**
     * Test endpoint untuk memeriksa range request support
     */
    public function test(Request $request, $id)
    {
        $video = VideoPanduan::findOrFail($id);

        if (! $video->videoFileExists()) {
            return response()->json(['error' => 'Video file not found'], 404);
        }

        $filePath = $video->getVideoFullPathAttribute();
        $fileSize = filesize($filePath);

        // Test range request dengan mengambil 1KB pertama
        $testRange = 'bytes=0-1023';

        try {
            $response = $this->handleRangeRequest($filePath, $fileSize, $testRange, [
                'Content-Type' => 'video/mp4',
                'Accept-Ranges' => 'bytes',
            ]);

            return response()->json([
                'success' => true,
                'video_id' => $video->id,
                'file_size' => $fileSize,
                'file_path' => $filePath,
                'range_supported' => true,
                'test_range_response' => [
                    'status' => $response->getStatusCode(),
                    'headers' => $response->headers->all(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'video_id' => $video->id,
                'file_size' => $fileSize,
            ], 500);
        }
    }
}
