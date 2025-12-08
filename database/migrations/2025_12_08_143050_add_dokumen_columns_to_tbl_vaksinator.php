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
        Schema::table('tbl_vaksinator', function (Blueprint $table) {
            $table->string('dokumen_izin_praktek')->nullable()->after('masa_aktif_izin_praktek');
            $table->string('dokumen_sertifikat_vaksinator')->nullable()->after('masa_aktif_sertifikat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_vaksinator', function (Blueprint $table) {
            $table->dropColumn(['dokumen_izin_praktek', 'dokumen_sertifikat_vaksinator']);
        });
    }
};
