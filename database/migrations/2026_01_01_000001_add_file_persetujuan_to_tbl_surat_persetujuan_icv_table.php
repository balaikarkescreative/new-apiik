<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_surat_persetujuan_icv', function (Blueprint $table) {
            $table->string('file_persetujuan')->nullable()->after('masa_berlaku');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_surat_persetujuan_icv', function (Blueprint $table) {
            $table->dropColumn('file_persetujuan');
        });
    }
};
