<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_laporan_penggunaan_icv', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_jenis_vaksin');
            $table->date('tgl_pelaporan');
            $table->integer('jml_terbit_icv');
            $table->integer('sisa_icv');
            $table->integer('jml_guna_vaksin');
            $table->integer('sisa_vaksin');
            $table->text('keterangan')->nullable();
            $table->timestamp('date_updated')->useCurrent();

            $table->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_jenis_vaksin')
                ->references('id')
                ->on('tbl_vaksin')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_penggunaan_icv');
    }
};
