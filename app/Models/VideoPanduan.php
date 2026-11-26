<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoPanduan extends Model
{
    protected $fillable = [
        'title',
        'description',
        'video_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getVideoUrlAttribute(): ?string
    {
        if ($this->video_path) {
            return route('video.stream', $this->id);
        }
        return null;
    }

    public function getVideoSizeAttribute(): ?string
    {
        if ($this->video_path && Storage::disk('public')->exists($this->video_path)) {
            $bytes = Storage::disk('public')->size($this->video_path);
            return $this->formatBytes($bytes);
        }
        return null;
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if video file exists in storage
     */
    public function videoFileExists(): bool
    {
        if (!$this->video_path) {
            return false;
        }
        
        return Storage::disk('public')->exists($this->video_path);
    }

    /**
     * Get full path to video file
     */
    public function getVideoFullPathAttribute(): ?string
    {
        if (!$this->video_path) {
            return null;
        }
        
        return Storage::disk('public')->path($this->video_path);
    }

    /**
     * Clean up orphaned video files that don't have database records
     */
    public static function cleanupOrphanedFiles(): int
    {
        $videoPaths = static::pluck('video_path')->filter()->toArray();
        $storageFiles = Storage::disk('public')->files('video-panduan');
        $orphanedFiles = array_diff($storageFiles, $videoPaths);
        
        $deletedCount = 0;
        foreach ($orphanedFiles as $file) {
            try {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            } catch (\Exception $e) {
                \Log::error('Failed to delete orphaned video file', [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $deletedCount;
    }
}
