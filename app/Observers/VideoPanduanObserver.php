<?php

namespace App\Observers;

use App\Models\VideoPanduan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoPanduanObserver
{
    /**
     * Handle the VideoPanduan "created" event.
     */
    public function created(VideoPanduan $videoPanduan): void
    {
        // Log when new video is created
        Log::info('Video panduan created', [
            'id' => $videoPanduan->id,
            'title' => $videoPanduan->title,
            'video_path' => $videoPanduan->video_path
        ]);
    }

    /**
     * Handle the VideoPanduan "updated" event.
     */
    public function updated(VideoPanduan $videoPanduan): void
    {
        // Check if video file was changed
        if ($videoPanduan->isDirty('video_path')) {
            $originalPath = $videoPanduan->getOriginal('video_path');
            
            // Delete old video file if it exists and is different
            if ($originalPath && $originalPath !== $videoPanduan->video_path) {
                $this->deleteVideoFile($originalPath);
                
                Log::info('Old video file deleted due to update', [
                    'id' => $videoPanduan->id,
                    'old_path' => $originalPath,
                    'new_path' => $videoPanduan->video_path
                ]);
            }
        }
    }

    /**
     * Handle the VideoPanduan "deleted" event.
     */
    public function deleted(VideoPanduan $videoPanduan): void
    {
        // Delete associated video file when record is deleted
        if ($videoPanduan->video_path) {
            $this->deleteVideoFile($videoPanduan->video_path);
            
            Log::info('Video file deleted', [
                'id' => $videoPanduan->id,
                'title' => $videoPanduan->title,
                'video_path' => $videoPanduan->video_path
            ]);
        }
    }

    /**
     * Handle the VideoPanduan "restored" event.
     */
    public function restored(VideoPanduan $videoPanduan): void
    {
        // Log when video is restored (if using soft deletes)
        Log::info('Video panduan restored', [
            'id' => $videoPanduan->id,
            'title' => $videoPanduan->title
        ]);
    }

    /**
     * Handle the VideoPanduan "force deleted" event.
     */
    public function forceDeleted(VideoPanduan $videoPanduan): void
    {
        // Delete associated video file when record is force deleted
        if ($videoPanduan->video_path) {
            $this->deleteVideoFile($videoPanduan->video_path);
            
            Log::info('Video file force deleted', [
                'id' => $videoPanduan->id,
                'title' => $videoPanduan->title,
                'video_path' => $videoPanduan->video_path
            ]);
        }
    }

    /**
     * Delete video file from storage
     */
    private function deleteVideoFile(string $filePath): void
    {
        try {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete video file', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }
}
