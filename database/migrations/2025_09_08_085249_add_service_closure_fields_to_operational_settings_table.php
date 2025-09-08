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
        Schema::table('operational_settings', function (Blueprint $table) {
            // Kolom boolean untuk setiap layanan spesifik
            $table->boolean('icv_closed')->default(false)->after('operational_days');
            $table->boolean('izin_closed')->default(false)->after('icv_closed');
            $table->boolean('account_closed')->default(false)->after('izin_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operational_settings', function (Blueprint $table) {
            $table->dropColumn([
                'icv_closed',
                'izin_closed', 
                'account_closed'
            ]);
        });
    }
};
