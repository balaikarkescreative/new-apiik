<?php

namespace App\Console\Commands;

use App\Models\Icv;
use App\Models\IcvBuktiBayar;
use App\Models\IcvSbbk;
use App\Models\IzinDokumenPks;
use App\Models\IzinPersetujuan;
use App\Models\VideoPanduan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-files 
                            {--model= : Specific model to cleanup (optional)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned files from storage that are no longer referenced in database';

    /**
     * Model configurations for file cleanup
     */
    protected array $modelConfigs = [
        VideoPanduan::class => [
            'fields' => ['video_path'],
            'disk' => 'public',
            'path_prefix' => 'video-panduan/',
        ],
        IzinDokumenPks::class => [
            'fields' => ['dokumen_pks_klinik', 'dokumen_pks_bkk'],
            'disk' => 'public',
            'path_prefix' => 'dokumen-pks/',
        ],
        IcvBuktiBayar::class => [
            'fields' => ['file_bukti_bayar'],
            'disk' => 'public',
            'path_prefix' => 'icv/bukti-bayar/',
        ],
        IcvSbbk::class => [
            'fields' => ['file_sbbk'],
            'disk' => 'public',
            'path_prefix' => 'icv/sbbk/',
        ],
        IzinPersetujuan::class => [
            'fields' => ['file_persetujuan'],
            'disk' => 'public',
            'path_prefix' => 'surat-persetujuan/',
        ],
        Icv::class => [
            'fields' => ['file1', 'file2', 'file3', 'file4', 'file5', 'file6', 'file7'],
            'disk' => 'public',
            'path_prefix' => 'icv/',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $specificModel = $this->option('model');
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Starting orphaned files cleanup...');
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No files will actually be deleted');
        }

        $totalDeleted = 0;
        $totalSize = 0;

        foreach ($this->modelConfigs as $modelClass => $config) {
            if ($specificModel && class_basename($modelClass) !== $specificModel) {
                continue;
            }

            $this->info("\n📂 Processing {$modelClass}...");

            $result = $this->cleanupModelFiles($modelClass, $config, $dryRun);
            $totalDeleted += $result['deleted'];
            $totalSize += $result['size'];
        }

        $this->newLine();
        $this->info('✅ Cleanup completed!');
        $this->info('📊 Total files '.($dryRun ? 'would be ' : '')."deleted: {$totalDeleted}");
        $this->info('💾 Total space '.($dryRun ? 'would be ' : '').'freed: '.$this->formatBytes($totalSize));

        Log::info('Orphaned files cleanup completed', [
            'files_deleted' => $totalDeleted,
            'size_freed' => $totalSize,
            'dry_run' => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Cleanup files for a specific model
     */
    protected function cleanupModelFiles(string $modelClass, array $config, bool $dryRun): array
    {
        $disk = Storage::disk($config['disk']);
        $deletedCount = 0;
        $totalSize = 0;

        // Get all files referenced in database
        $referencedFiles = collect();
        foreach ($config['fields'] as $field) {
            $files = $modelClass::whereNotNull($field)
                ->where($field, '!=', '')
                ->pluck($field)
                ->filter()
                ->map(function ($file) use ($config) {
                    // Remove path prefix if present
                    if (str_starts_with($file, $config['path_prefix'])) {
                        return $file;
                    }

                    return $config['path_prefix'].$file;
                });

            $referencedFiles = $referencedFiles->merge($files);
        }

        $referencedFiles = $referencedFiles->unique();

        $this->info("📋 Found {$referencedFiles->count()} files referenced in database");

        // Get all files from storage directory
        if (! $disk->exists($config['path_prefix'])) {
            $this->warn("⚠️  Directory {$config['path_prefix']} does not exist");

            return ['deleted' => 0, 'size' => 0];
        }

        $allFiles = collect($disk->allFiles($config['path_prefix']));
        $this->info("💾 Found {$allFiles->count()} files in storage directory");

        // Find orphaned files
        $orphanedFiles = $allFiles->reject(function ($file) use ($referencedFiles) {
            return $referencedFiles->contains($file);
        });

        if ($orphanedFiles->isEmpty()) {
            $this->info('✅ No orphaned files found');

            return ['deleted' => 0, 'size' => 0];
        }

        $this->warn("🗑️  Found {$orphanedFiles->count()} orphaned files");

        // Process orphaned files
        foreach ($orphanedFiles as $file) {
            try {
                $size = $disk->size($file);
                $totalSize += $size;

                if ($dryRun) {
                    $this->line("  Would delete: {$file} (".$this->formatBytes($size).')');
                } else {
                    $disk->delete($file);
                    $this->line("  Deleted: {$file} (".$this->formatBytes($size).')');
                }

                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to process {$file}: ".$e->getMessage());
                Log::error('Failed to cleanup file', [
                    'file' => $file,
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['deleted' => $deletedCount, 'size' => $totalSize];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
