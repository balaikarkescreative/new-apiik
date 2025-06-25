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
        Schema::create('tbl_icv_billing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_icv');
            $table->bigInteger('kode_billing');
            $table->string('nominal', 50);
            $table->integer('status')->default(0);
            $table->string('bayar', 5)->nullable();
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_icv')
                ->references('id')
                ->on('tbl_icv')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('id_icv');
            $table->index('kode_billing');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_icv_billing');
    }
};
