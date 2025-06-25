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
        Schema::create('tbl_icv_sbbk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_icv');
            $table->string('file_sbbk', 255)->nullable();
            $table->enum('sinkarkes', ['Belum', 'Sudah'])->default('Belum');
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_icv')
                ->references('id')
                ->on('tbl_icv')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('id_icv');
            $table->index('sinkarkes');
            $table->index('file_sbbk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_icv_sbbk');
    }
};
