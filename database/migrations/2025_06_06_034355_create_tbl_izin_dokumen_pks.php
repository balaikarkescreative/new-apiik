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
        Schema::create('tbl_izin_dokumen_pks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_izin');
            $table->text('dokumen_pks_klinik');
            $table->text('dokumen_pks_bkk');
            $table->enum('status_dokumen', ['sign both', 'not sign'])->default('not sign');
            $table->date('tanggal_visitasi')->nullable();
            $table->timestamp('date_updated')->useCurrent();

            // Foreign key constraint
            $table->foreign('id_izin')->references('id')->on('tbl_izin')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_izin_dokumen_pks');
    }
};
