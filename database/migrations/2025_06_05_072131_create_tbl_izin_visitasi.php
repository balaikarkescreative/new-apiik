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
        Schema::create('tbl_izin_visitasi', function (Blueprint $table) {
            $table->id(); // id int auto increment
            $table->unsignedBigInteger('id_izin'); // id_izin int(4)
            $table->date('tanggal_visitasi'); // tanggal_visitasi date
            $table->unsignedInteger('status'); // status int(4)
            $table->timestamp('date_updated')->useCurrent();

            $table->foreign('id_izin')
                ->references('id')
                ->on('tbl_izin')
                ->onUpdate('cascade')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_izin_visitasi');
    }
};
