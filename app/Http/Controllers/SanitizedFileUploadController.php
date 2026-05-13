<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Override default Livewire FileUploadController untuk men-sanitasi
 * nama file yang mengandung karakter khusus (emoji, tanda baca, dll)
 * sebelum di-encode ke base64 dan disimpan di temp storage.
 *
 * Tanpa sanitasi ini, nama file seperti:
 *   - "Dokumen🔥ICV🔥Final🔥2026.pdf"
 *   - "SURAT !!! FINAL ((( REVISI ))) [FIX].pdf"
 *   - "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9....pdf" (mengandung '-')
 * akan menyebabkan error "Unable to retrieve the file_size for file at
 * location: livewire-tmp/livewire-tmp" karena base64 yang di-encode
 * mengandung karakter '-' sehingga merusak parsing nama file temp.
 */
class SanitizedFileUploadController extends FileUploadController
{
    public function handle()
    {
        abort_unless(request()->hasValidSignature(), 401);

        $disk = FileUploadConfiguration::disk();

        $filePaths = $this->validateAndStore(request('files'), $disk);

        return ['paths' => $filePaths];
    }

    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $fileHashPaths = collect($files)->map(function ($file) use ($disk) {
            // Sanitasi nama file original sebelum di-embed ke nama file temp
            $sanitizedFile = $this->wrapFileWithSanitizedName($file);

            $filename = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($sanitizedFile);

            return $sanitizedFile->storeAs('/'.FileUploadConfiguration::path(), $filename, [
                'disk' => $disk,
            ]);
        });

        // Strip out the temporary upload directory from the paths.
        return $fileHashPaths->map(function ($path) {
            return str_replace(FileUploadConfiguration::path('/'), '', $path);
        });
    }

    /**
     * Wrap file dengan nama yang sudah disanitasi.
     * Membuat anonymous class yang mengoverride getClientOriginalName()
     * agar nama file bersih dari karakter yang bisa merusak base64 parsing.
     */
    protected function wrapFileWithSanitizedName($file)
    {
        $originalName = $file->getClientOriginalName();
        $sanitizedName = $this->sanitizeFilename($originalName);

        // Jika nama sudah bersih, tidak perlu wrap
        if ($sanitizedName === $originalName) {
            return $file;
        }

        return new class($file, $sanitizedName) extends \Illuminate\Http\UploadedFile
        {
            private $originalFile;
            private $sanitizedName;

            public function __construct($file, $sanitizedName)
            {
                $this->originalFile = $file;
                $this->sanitizedName = $sanitizedName;

                parent::__construct(
                    $file->getPathname(),
                    $sanitizedName,
                    $file->getMimeType(),
                    $file->getError(),
                    true
                );
            }

            public function getClientOriginalName(): string
            {
                return $this->sanitizedName;
            }

            public function getClientOriginalExtension(): string
            {
                return pathinfo($this->sanitizedName, PATHINFO_EXTENSION);
            }

            public function getPathname(): string
            {
                return $this->originalFile->getPathname();
            }

            public function getRealPath(): string|false
            {
                return $this->originalFile->getRealPath();
            }

            public function getMimeType(): ?string
            {
                return $this->originalFile->getMimeType();
            }

            public function getSize(): int|false
            {
                return $this->originalFile->getSize();
            }

            public function getError(): int
            {
                return $this->originalFile->getError();
            }

            public function storeAs($path, $name = null, $options = [])
            {
                return $this->originalFile->storeAs($path, $name, $options);
            }
        };
    }

    /**
     * Sanitasi nama file dari karakter yang bermasalah.
     *
     * Karakter yang dibuang/diganti:
     * - Emoji dan karakter unicode non-ASCII
     * - Tanda baca yang bermasalah: ! @ # $ % ^ & * ( ) [ ] { } | \ : ; " ' < > , ?
     * - Karakter '-' di nama file (akan merusak parsing "-meta...-" pada base64)
     *   → diganti dengan underscore
     *
     * Karakter yang DIPERTAHANKAN: huruf, angka, spasi, titik, underscore
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Pisahkan nama dan ekstensi
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // 1. Buang karakter non-ASCII (emoji, karakter unicode khusus)
        $name = preg_replace('/[^\x00-\x7F]/', '', $name);

        // 2. Ganti karakter '-' dengan '_' (kritis: '-' merusak parsing base64 "-meta...-")
        $name = str_replace('-', '_', $name);

        // 3. Buang karakter khusus yang bermasalah, pertahankan huruf/angka/spasi/underscore/titik
        $name = preg_replace('/[^A-Za-z0-9\s_.]/', '', $name);

        // 4. Normalisasi spasi berlebih
        $name = trim(preg_replace('/\s+/', '_', $name), '_');

        // 5. Jika nama jadi kosong setelah sanitasi, beri nama default
        if (empty($name)) {
            $name = 'dokumen_' . time();
        }

        // 6. Batasi panjang nama (max 100 karakter) untuk menghindari path terlalu panjang
        if (strlen($name) > 100) {
            $name = substr($name, 0, 100);
        }

        return $name . ($extension ? '.' . $extension : '');
    }
}
