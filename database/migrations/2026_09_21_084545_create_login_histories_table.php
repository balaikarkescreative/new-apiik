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
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('login_identifier')->nullable()->comment('Username atau email yang diinput');
            $table->string('role')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 30)->default('success')->comment('success, failed, master_password');
            $table->string('failure_reason')->nullable();
            $table->string('device')->nullable()->comment('Desktop, Mobile, Tablet');
            $table->string('platform')->nullable()->comment('OS name: Windows, Android, iOS, etc.');
            $table->string('browser')->nullable()->comment('Browser name: Chrome, Firefox, etc.');
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
