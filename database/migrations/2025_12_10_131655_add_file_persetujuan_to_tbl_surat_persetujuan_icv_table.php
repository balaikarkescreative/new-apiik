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
        Schema::table('tbl_surat_persetujuan_icv', function (Blueprint $table) {
            $table->string('file_persetujuan')->nullable()->after('masa_berlaku');
            $table->tinyInteger('status_approval')->default(0)->after('file_persetujuan')->comment('0=pending, 1=approved, 2=rejected');
            $table->text('catatan_approval')->nullable()->after('status_approval');
            $table->unsignedBigInteger('approved_by')->nullable()->after('catatan_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_surat_persetujuan_icv', function (Blueprint $table) {
            $table->dropColumn([
                'file_persetujuan',
                'status_approval',
                'catatan_approval',
                'approved_by',
                'approved_at',
            ]);
        });
    }
};
