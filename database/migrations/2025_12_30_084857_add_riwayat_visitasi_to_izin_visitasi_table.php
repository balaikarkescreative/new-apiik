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
        Schema::table('tbl_izin_visitasi', function (Blueprint $table) {
            $table->json('riwayat_visitasi')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_izin_visitasi', function (Blueprint $table) {
            $table->dropColumn('riwayat_visitasi');
        });
    }
};
