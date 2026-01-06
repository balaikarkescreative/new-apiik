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
        Schema::table('service_closures', function (Blueprint $table) {
            // Menambahkan field permit_type untuk izin spesifik (izin_baru, izin_perpanjangan)
            // Nullable karena hanya dipakai untuk service_type = 'izin'
            $table->string('permit_type')->nullable()->after('service_type');
            
            // Drop unique constraint lama dan buat yang baru dengan kombinasi service_type + permit_type
            $table->dropUnique(['service_type']);
            $table->unique(['service_type', 'permit_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_closures', function (Blueprint $table) {
            // Kembalikan unique constraint lama
            $table->dropUnique(['service_type', 'permit_type']);
            $table->unique(['service_type']);
            
            // Hapus kolom permit_type
            $table->dropColumn('permit_type');
        });
    }
};
