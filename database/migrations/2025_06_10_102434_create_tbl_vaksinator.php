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
        Schema::create('tbl_vaksinator', function (Blueprint $table) {
            $table->id('id'); // Auto increment primary key
            $table->unsignedBigInteger('id_akun');
            $table->string('nama_vaksinator', 100);
            $table->string('nomor_izin_praktek', 50);
            $table->date('masa_aktif_izin_praktek');
            $table->string('nomor_sertifikat_vaksinator', 50);
            $table->date('masa_aktif_sertifikat');
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_akun')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_vaksinologi');
    }
};
