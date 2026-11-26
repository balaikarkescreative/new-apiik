<?php

namespace App\Services\Export;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExportBatchService
{
    /**
     * Cache prefix for batch export sessions.
     */
    protected const CACHE_PREFIX = 'export_batch_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Create a new batch export session in cache.
     */
    public static function createBatch(string $type, array $params, int $totalCount, int $chunkSize = 25): array
    {
        $token = Str::random(32);
        $totalChunks = (int) max(1, ceil($totalCount / $chunkSize));

        $batch = [
            'token' => $token,
            'type' => $type, // 'izin' or 'icv'
            'format' => $params['format'] ?? 'pdf',
            'tipe_laporan' => $params['tipe_laporan'] ?? 'lengkap',
            'params' => $params,
            'total_count' => $totalCount,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'completed_chunks' => 0,
            'temp_files' => [],
            'created_at' => now()->timestamp,
        ];

        Cache::put(self::CACHE_PREFIX.$token, $batch, self::CACHE_TTL);

        return [
            'token' => $token,
            'type' => $type,
            'format' => $batch['format'],
            'tipe_laporan' => $batch['tipe_laporan'],
            'total' => $totalCount,
            'chunkSize' => $chunkSize,
            'totalChunks' => $totalChunks,
        ];
    }

    /**
     * Retrieve batch state from cache.
     */
    public static function getBatch(string $token): ?array
    {
        return Cache::get(self::CACHE_PREFIX.$token);
    }

    /**
     * Update batch state in cache.
     */
    public static function updateBatch(string $token, array $data): void
    {
        $batch = self::getBatch($token);
        if ($batch) {
            $updated = array_merge($batch, $data);
            Cache::put(self::CACHE_PREFIX.$token, $updated, self::CACHE_TTL);
        }
    }

    /**
     * Delete batch state and clean up temporary files.
     */
    public static function deleteBatch(string $token): void
    {
        $batch = self::getBatch($token);
        if ($batch) {
            self::cleanupTempFiles($batch['temp_files'] ?? []);
            if (! empty($batch['final_file']) && file_exists($batch['final_file'])) {
                @unlink($batch['final_file']);
            }
            Cache::forget(self::CACHE_PREFIX.$token);
        }
    }

    /**
     * Clean up an array of file paths.
     */
    public static function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_string($file) && file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
