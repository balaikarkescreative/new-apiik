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
            $table->string('bukti_perbaikan')->nullable()->after('catatan');
            $table->timestamp('tanggal_perbaikan_visitasi')->nullable()->after('bukti_perbaikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_izin_visitasi', function (Blueprint $table) {
            $table->dropColumn(['bukti_perbaikan', 'tanggal_perbaikan_visitasi']);
        });
    }
};
