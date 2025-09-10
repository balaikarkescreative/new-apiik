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
        Schema::create('tbl_icv_cek_dokumen', function (Blueprint $table) {
            $table->id(); // This creates an auto-incrementing primary key
            $table->string('hasil_verif', 20)->nullable();
            $table->integer('stok_vaksin')->nullable();
            $table->unsignedBigInteger('id_icv');
            $table->integer('jumlah_icv')->nullable();
            $table->text('catatan')->nullable();
            $table->integer('status');
            $table->timestamp('date_updated')->useCurrent();

            // Foreign key constraint for id_icv
            $table
                ->foreign('id_icv')
                ->references('id')
                ->on('tbl_icv')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_icv_cek_dokumen');
    }
};
