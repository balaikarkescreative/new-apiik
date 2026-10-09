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
        Schema::table('tbl_izin', function (Blueprint $table) {
            $table->unsignedBigInteger('pic_dokter_id')->nullable()->after('user_id');
            $table->foreign('pic_dokter_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_izin', function (Blueprint $table) {
            $table->dropForeign(['pic_dokter_id']);
            $table->dropColumn('pic_dokter_id');
        });
    }
};
