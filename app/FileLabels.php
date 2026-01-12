<?php

namespace App;

trait FileLabels
{
    public static function getIzinLabels(): array
    {
        return [
            'file1' => 'Surat Permohonan',
            'file2' => 'Surat Rekomendasi Dinkes',
            'file3' => 'Surat Pernyataan Penandatanganan ICV',//ada
            'file4' => 'Daftar Tenaga Medis Pencatatan Pelaporan',
            'file5' => 'Daftar fasilitas',
            'file6' => 'Surat Pernyataan Memiliki COLD CHAIN',
            'file7' => 'Surat Pernyataan Memiliki Alat Pengolah data komputer, printer, passbook, jaringan internet',
            'file8' => 'Surat Pernyataan Bermaterai Bersedia Mengisi SINKARKES',
            'file9' => 'Lembar Pemantauan Suhu COLD CHAIN',
            'file10' => 'SOP Pelayanan Vaksinasi Internasional',
            'file11' => 'Sertifikat Akreditasi',
            'file12' => 'Surat Izin Operasional Klinik RS',
            'file13' => 'Surat izin Praktik Dokter',
            'file14' => 'Sertifikat Pelatihan Vaksin / Imunologi',
        ];
    }


    public static function getNewIzinLabels(): array
    {
        return [
            'file1' => 'Surat Permohonan',
            'file2' => 'Surat Rekomendasi Dinkes',//ada
            'file3' => 'Surat Pernyataan Penandatanganan ICV',//ada
            'file4' => 'SK Penunjukan Petugas Administrasi (terdapat petugas dengan latarbelakang pendidikan komputer, minimal 1 orang)',
            'file5' => 'Daftar Sarana dan Fasilitas (Lengkapi dengan foto)',//ada
            'file6' => 'Surat Pernyataan Memiliki COLD CHAIN (bermaterai)',
            'file7' => 'Surat Pernyataan Memiliki Alat Pengolah data komputer, printer, passbook, jaringan internet (bermaterai)',
            'file8' => 'Surat Pernyataan Bersedia Mengisi SINKARKES (bermaterai)',
            'file9' => 'Daftar Alat Pemantau Suhu Penyimpan Vaksin disertai foto (Thermometer, Freeze tag, Log Tag, Form pencatatan suhu manual)',
            'file10' => 'SOP Pelayanan Vaksinasi Internasional',//ada
            'file11' => 'Sertifikat Akreditasi',
            'file12' => 'Surat Izin Operasional Klinik RS',
            'file13' => 'Surat izin Praktik Dokter',//ada
            'file14' => 'Sertifikat Pelatihan Vaksin / Imunologi',
            'file15' => 'Daftar Tenaga Vaksinator (Minimal 2 Dokter dan 2 Perawat)',
            'file16' => 'Daftar Peralatan Pendukung Cold Chain Disertai Foto (Automatic Voltage Stabilizer, Standby Generator)',
        ];
    }

    public static function getIcvLabels(): array
    {
        return [
            'file1' => 'Surat Permohonan',
            'file2' => 'Bukti laporan Ke Dinas Kesehatan',
            'file3' => 'Rekap Pemakaian ICV',
            'file4' => 'Faktur Pembelian Vaksin',
            'file5' => 'Berita Acara Kerusakan ICV',
            'file6' => 'Daftar Nama Penerima ICV',
            'file7' => 'Screenshoot sisa stok di Sinkarkes',
        ];
    }

    public static function getIzinLabel(string $key, $record = null): string
    {
        // Jika ada record dan use_new_labels aktif, gunakan new labels
        if ($record && method_exists($record, 'getAttribute') && $record->getAttribute('use_new_labels')) {
            return self::getNewIzinLabels()[$key] ?? $key;
        }
        
        // Fallback ke old labels
        return self::getIzinLabels()[$key] ?? $key;
    }

    public static function getNewIzinLabel(string $key): string
    {
        return self::getNewIzinLabels()[$key] ?? $key;
    }

    public static function getIcvLabel(string $key): string
    {
        return self::getIcvLabels()[$key] ?? $key;
    }
}
