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
        if (! Schema::hasColumn('tbl_icv_cek_dokumen', 'stok_icv')) {
            Schema::table('tbl_icv_cek_dokumen', function (Blueprint $table) {
                $table->integer('stok_icv')->nullable()->after('stok_vaksin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tbl_icv_cek_dokumen', 'stok_icv')) {
            Schema::table('tbl_icv_cek_dokumen', function (Blueprint $table) {
                $table->dropColumn('stok_icv');
            });
        }
    }
};
