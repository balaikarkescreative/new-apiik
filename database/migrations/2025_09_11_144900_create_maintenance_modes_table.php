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
        Schema::create('maintenance_modes', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('secret_key')->nullable()->comment('Kata kunci untuk bypass maintenance');
            $table->text('message')->nullable()->comment('Pesan yang ditampilkan ke user');
            $table->integer('retry_after')->default(60)->comment('Waktu retry dalam detik');
            $table->timestamp('enabled_at')->nullable()->comment('Kapan maintenance dimulai');
            $table->timestamp('disabled_at')->nullable()->comment('Kapan maintenance selesai');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_modes');
    }
};
