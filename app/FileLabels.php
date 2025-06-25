<?php

namespace App;

trait FileLabels
{
    public static function getIzinLabels(): array
    {
        return [
            'file1' => 'Surat Permohonan',
            'file2' => 'Surat Rekomendasi Dinkes',
            'file3' => 'Surat Pernyataan Penandatanganan ICV',
            'file4' => 'Daftar Tenaga Medis Pencatatan Pelaporan',
            'file5' => 'Daftar fasilitas',
            'file6' => 'Surat Pernyataan Memiliki COLD CHAIN',
            'file7' => 'Surat Pernyataan Memiliki Alat Pengolah data komputer, printer, passbook, jaringan internet',
            'file8' => 'Surat Pernyataan Bermaterai Bersedia Mengisi SINKARKES',
            'file9' => 'Lembar Pemantauan Suhu COLD CHAIN',
            'file10' => 'SOP Pelayanan Vaksinasi Internasional',
            'file11' => 'Bagan Syok Anafilaktik',
            'file12' => 'Surat Izin Operasional Klinik RS',
            'file13' => 'Surat izin Praktik Dokter',
            'file14' => 'Sertifikat Pelatihan Vaksin / Imunologi'
        ];
    }

    public static function getIcvLabels(): array
    {
        return [
            'file1' => 'Surat Permohonan',
            'file2'=> 'Bukti laporan Ke Dinas Kesehatan',
            'file3'=> 'Rekap Pemakaian ICV',
            'file4'=> 'Faktur Pembelian Vaksin',
            'file5'=> 'Berita Acara Kerusakan ICV',
            'file6'=> 'Daftar Nama Penerima ICV',
            'file7'=> 'Screenshoot sisa stok di Sinkarkes',
        ];
    }

    public static function getIzinLabel(string $key): string
    {
        return self::getIzinLabels()[$key] ?? $key;
    }

    public static function getIcvLabel(string $key): string
    {
        return self::getIcvLabels()[$key] ?? $key;
    }
}
