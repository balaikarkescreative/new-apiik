<?php

namespace App\Console\Commands;

use App\Models\VideoPanduan;
use Illuminate\Console\Command;

class CleanupVideoPanduanFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video-panduan:cleanup {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned video panduan files that no longer have database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of orphaned video panduan files...');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No files will actually be deleted');
        }

        // Get all video paths from database
        $videoPaths = VideoPanduan::pluck('video_path')->filter()->toArray();
        $this->info('Found '.count($videoPaths).' video files in database');

        // Get all files in storage
        $storageFiles = \Storage::disk('public')->files('video-panduan');
        $this->info('Found '.count($storageFiles).' files in storage');

        // Find orphaned files
        $orphanedFiles = array_diff($storageFiles, $videoPaths);

        if (empty($orphanedFiles)) {
            $this->info('No orphaned files found!');

            return Command::SUCCESS;
        }

        $this->warn('Found '.count($orphanedFiles).' orphaned files:');

        // Show orphaned files
        foreach ($orphanedFiles as $file) {
            $size = \Storage::disk('public')->size($file);
            $sizeFormatted = $this->formatBytes($size);
            $this->line("  - {$file} ({$sizeFormatted})");
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN: Would delete '.count($orphanedFiles).' orphaned files');

            return Command::SUCCESS;
        }

        // Confirm deletion
        if (! $this->confirm('Do you want to delete these orphaned files?')) {
            $this->info('Operation cancelled');

            return Command::SUCCESS;
        }

        // Delete orphaned files
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($orphanedFiles as $file) {
            try {
                \Storage::disk('public')->delete($file);
                $deletedCount++;
                $this->line("Deleted: {$file}");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Failed to delete {$file}: ".$e->getMessage());
            }
        }

        $this->info('Cleanup completed!');
        $this->info("Deleted: {$deletedCount} files");
        if ($failedCount > 0) {
            $this->error("Failed: {$failedCount} files");
        }

        return Command::SUCCESS;
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
