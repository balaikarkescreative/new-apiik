<?php

namespace App\Services;

use App\FileLabels;
use App\Models\Izin;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class IzinZipService
{
    use FileLabels;

    /**
     * Generate and download zip for the given Izin record.
     */
    public function downloadZip(Izin $record): ?BinaryFileResponse
    {
        $files = $this->collectIzinFiles($record);

        if (empty($files)) {
            return null;
        }

        $clinicName = Str::slug($record->user?->name ?? 'Pemohon', '_');
        $date = $record->tanggal_pengajuan 
            ? $record->tanggal_pengajuan->format('Y-m-d') 
            : ($record->created_at ? $record->created_at->format('Y-m-d') : date('Y-m-d'));
        
        $zipFileName = "Dokumen_Izin_{$clinicName}_{$date}.zip";

        $tempPath = tempnam(sys_get_temp_dir(), 'izin_zip_');
        $zip = new ZipArchive();

        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file arsip ZIP.');
        }

        // To prevent duplicate entry names in zip
        $usedEntryNames = [];

        foreach ($files as $file) {
            $entryName = $file['name'];
            
            // Handle collision if any
            if (isset($usedEntryNames[$entryName])) {
                $usedEntryNames[$entryName]++;
                $pathInfo = pathinfo($entryName);
                $entryName = $pathInfo['filename'] . '_' . $usedEntryNames[$entryName] . '.' . ($pathInfo['extension'] ?? 'pdf');
            } else {
                $usedEntryNames[$entryName] = 1;
            }

            $zip->addFile($file['path'], $entryName);
        }

        $zip->close();

        return response()->download($tempPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Check if record has at least one downloadable file.
     */
    public function hasDownloadableFiles(Izin $record): bool
    {
        return !empty($this->collectIzinFiles($record));
    }

    /**
     * Collect all files for an Izin record.
     */
    public function collectIzinFiles(Izin $record): array
    {
        $collected = [];
        $maxFiles = $record->use_new_labels ? 16 : 14;

        // 1. Dokumen Permohonan (file1 s/d file16)
        for ($i = 1; $i <= $maxFiles; $i++) {
            $field = "file{$i}";
            $filePath = $record->$field;

            if ($filePath) {
                $realPath = $this->getRealFilePath($filePath);
                if ($realPath) {
                    $label = self::getIzinLabel($field, $record);
                    $cleanLabel = preg_replace('/[^\w\-\.]+/u', '_', trim($label));
                    $cleanLabel = trim($cleanLabel, '_');
                    $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'pdf';
                    $entryName = sprintf('%02d_%s.%s', $i, $cleanLabel, $ext);

                    $collected[] = [
                        'label' => $label,
                        'name' => $entryName,
                        'path' => $realPath,
                    ];
                }
            }
        }

        // 2. Bukti Perbaikan Visitasi (jika ada)
        if ($record->relationLoaded('izinVisitasi') || $record->izinVisitasi) {
            $buktiPerbaikan = $record->izinVisitasi?->bukti_perbaikan;
            if ($buktiPerbaikan) {
                $realPath = $this->getRealFilePath($buktiPerbaikan);
                if ($realPath) {
                    $ext = pathinfo($buktiPerbaikan, PATHINFO_EXTENSION) ?: 'pdf';
                    $collected[] = [
                        'label' => 'Bukti Perbaikan Visitasi',
                        'name' => "Lanjutan_Bukti_Perbaikan_Visitasi.{$ext}",
                        'path' => $realPath,
                    ];
                }
            }
        }

        // 3. Dokumen PKS BKK (jika ada)
        if ($record->relationLoaded('izinDokumenPks') || $record->izinDokumenPks) {
            $dokumenPks = $record->izinDokumenPks?->dokumen_pks_bkk;
            if ($dokumenPks) {
                $realPath = $this->getRealFilePath($dokumenPks);
                if ($realPath) {
                    $ext = pathinfo($dokumenPks, PATHINFO_EXTENSION) ?: 'pdf';
                    $collected[] = [
                        'label' => 'Dokumen PKS',
                        'name' => "Lanjutan_Dokumen_PKS.{$ext}",
                        'path' => $realPath,
                    ];
                }
            }
        }

        // 4. File Surat Persetujuan (jika ada)
        if ($record->relationLoaded('izinPersetujuan') || $record->izinPersetujuan) {
            $filePersetujuan = $record->izinPersetujuan?->file_persetujuan;
            if ($filePersetujuan) {
                $realPath = $this->getRealFilePath($filePersetujuan);
                if ($realPath) {
                    $ext = pathinfo($filePersetujuan, PATHINFO_EXTENSION) ?: 'pdf';
                    $collected[] = [
                        'label' => 'Surat Persetujuan',
                        'name' => "Lanjutan_Surat_Persetujuan.{$ext}",
                        'path' => $realPath,
                    ];
                }
            }
        }

        return $collected;
    }

    /**
     * Resolve absolute filesystem path from stored relative path.
     */
    protected function getRealFilePath(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $cleanPath = ltrim(preg_replace('#^storage/#', '', $relativePath), '/');

        // Check storage_path('app/public/' . $cleanPath)
        $candidate = storage_path('app/public/' . $cleanPath);
        if (file_exists($candidate) && is_file($candidate)) {
            return $candidate;
        }

        // Check public_path('storage/' . $cleanPath)
        $candidate = public_path('storage/' . $cleanPath);
        if (file_exists($candidate) && is_file($candidate)) {
            return $candidate;
        }

        // Check Storage disk public
        if (Storage::disk('public')->exists($cleanPath)) {
            $path = Storage::disk('public')->path($cleanPath);
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }

        // Check storage_path('app/' . $cleanPath)
        $candidate = storage_path('app/' . $cleanPath);
        if (file_exists($candidate) && is_file($candidate)) {
            return $candidate;
        }

        // Direct path
        if (file_exists($relativePath) && is_file($relativePath)) {
            return $relativePath;
        }

        return null;
    }
}
