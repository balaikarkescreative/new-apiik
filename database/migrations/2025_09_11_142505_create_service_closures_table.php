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
        Schema::create('service_closures', function (Blueprint $table) {
            $table->id();
            $table->string('service_type'); // 'icv', 'izin', 'account'
            $table->boolean('is_closed')->default(false);
            $table->text('closure_reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_closures');
    }
};
