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
        Schema::create('click_logs', function (Blueprint $table) {
            $table->id();
            $table->string('short_code', 10);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('referer', 191)->nullable(); // Reduced for MySQL compatibility
            $table->string('country', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('browser_version', 50)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->timestamp('timestamp')->useCurrent();
            
            $table->index('short_code');
            $table->index('timestamp');
            $table->index('ip_address');
            
            $table->foreign('short_code')->references('short_code')->on('links')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_logs');
    }
};
