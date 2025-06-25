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
        Schema::create('tbl_laporan_pemanfaatan_icv', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_klinik');
            $table->date('tanggal');
            $table->string('judul_laporan');
            $table->string('file');
            // $table->timestamps();

            $table->foreign('id_klinik')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_laporan_pemanfaatan_icv');
    }
};
