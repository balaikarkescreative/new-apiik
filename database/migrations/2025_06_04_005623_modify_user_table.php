<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('kategori')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kotakab')->nullable();
            $table->string('pimpinan')->nullable();
            $table->string('pj_akun')->nullable();
            $table->string('kontak_pj')->nullable();
            $table->string('nomor_surat_izin_operasional')->nullable();
            $table->date('masa_berlaku_surat_izin_operasional')->nullable();
            $table->enum('pertanyaan1', ['Ya', 'Tidak'])->nullable();
            $table->enum('pertanyaan2', ['Ya', 'Tidak'])->nullable();
            $table->enum('status_akun', ['Aktif', 'Tidak Aktif'])->nullable();
            $table->enum('verifikasi_akun', ['Diterima', 'Ditolak'])->nullable();
            $table->datetime('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'latitude',
                'longitude',
                'kategori',
                'alamat',
                'kotakab',
                'pimpinan',
                'pj_akun',
                'kontak_pj',
                'nomor_surat_izin_operasional',
                'masa_berlaku_surat_izin_operasional',
                'pertanyaan1',
                'pertanyaan2',
                'status_akun',
                'date_updated',
            ]);
        });
    }
};
