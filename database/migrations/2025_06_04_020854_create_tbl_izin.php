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
        Schema::create('tbl_izin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('kategori_izin');
            $table->date('tanggal_pengajuan');
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->string('file1')->nullable();
            $table->string('file2')->nullable();
            $table->string('file3')->nullable();
            $table->string('file4')->nullable();
            $table->string('file5')->nullable();
            $table->string('file6')->nullable();
            $table->string('file7')->nullable();
            $table->string('file8')->nullable();
            $table->string('file9')->nullable();
            $table->string('file10')->nullable();
            $table->string('file11')->nullable();
            $table->string('file12')->nullable();
            $table->string('file13')->nullable();
            $table->string('file14')->nullable();
            $table->integer('status')->default(0); // 0: pending, 1: approved, 2: rejected
            $table->datetime('date_updated')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('tbl_izin');
    }
};
