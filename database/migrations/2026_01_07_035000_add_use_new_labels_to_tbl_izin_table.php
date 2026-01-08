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
        Schema::table('tbl_izin', function (Blueprint $table) {
            // Kolom untuk menandakan apakah menggunakan label baru atau lama
            // true = gunakan new labels (file15, file16 ada)
            // false/null = gunakan old labels (hanya file1-file14)
            $table->boolean('use_new_labels')->default(false)->after('kategori_izin');
        });
        
        // Tambah kolom file15 dan file16 untuk new labels
        Schema::table('tbl_izin', function (Blueprint $table) {
            $table->string('file15')->nullable()->after('file14');
            $table->string('file16')->nullable()->after('file15');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_izin', function (Blueprint $table) {
            $table->dropColumn(['use_new_labels', 'file15', 'file16']);
        });
    }
};
