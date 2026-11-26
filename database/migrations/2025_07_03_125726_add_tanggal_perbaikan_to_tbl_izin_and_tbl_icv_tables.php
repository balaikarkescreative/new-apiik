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
            $table->timestamp('tanggal_perbaikan')->nullable()->after('status');
        });

        Schema::table('tbl_icv', function (Blueprint $table) {
            $table->timestamp('tanggal_perbaikan')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_izin', function (Blueprint $table) {
            $table->dropColumn('tanggal_perbaikan');
        });

        Schema::table('tbl_icv', function (Blueprint $table) {
            $table->dropColumn('tanggal_perbaikan');
        });
    }
};
