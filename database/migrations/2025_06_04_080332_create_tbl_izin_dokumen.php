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
        Schema::create('tbl_izin_dokumen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('izin_id')->nullable();
            $table->integer('hasil')->default(0); // 0: pending, 1: approved, 2: rejected
            $table->text('catatan')->nullable();
            $table->integer('status')->default(0); // 0: pending, 1: approved, 2: rejected
            $table->timestamp('date_updated')->useCurrent();

            $table->foreign('izin_id')
                ->references('id')
                ->on('tbl_izin')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_izin_dokumen');
    }
};
