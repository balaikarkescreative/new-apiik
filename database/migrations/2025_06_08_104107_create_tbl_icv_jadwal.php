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
        Schema::create('tbl_icv_jadwal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_icv');
            $table->date('jadwal_pengambilan');
            $table->integer('status')->default(0);
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_icv')
                ->references('id')
                ->on('tbl_icv')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('id_icv');
            $table->index('jadwal_pengambilan');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_icv_jadwal');
    }
};
