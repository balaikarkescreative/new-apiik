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
        Schema::create('tbl_vaksinologi', function (Blueprint $table) {
            $table->id('id'); // Auto increment primary key (int 11)
            $table->unsignedBigInteger('id_akun'); // int(11)
            $table->string('nama_vaksinologi', 100);
            $table->date('masa_aktif_izin_praktek');
            $table->date('masa_aktif_sertifikat');
            $table->timestamp('date_updated')->useCurrent()->useCurrentOnUpdate();

            // Foreign key constraint
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
