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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_user_id')->unique();
            $table->string('username', 191)->nullable(); // Reduced for MySQL compatibility
            $table->string('password_hash');
            $table->string('email', 191)->nullable(); // Reduced for MySQL compatibility
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('telegram_user_id');
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
