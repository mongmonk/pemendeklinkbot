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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('short_code', 10)->unique();
            $table->text('long_url');
            $table->boolean('is_custom')->default(false);
            $table->unsignedBigInteger('telegram_user_id')->nullable();
            $table->timestamps();
            $table->integer('clicks')->default(0);
            
            $table->index('short_code');
            $table->index('created_at');
            $table->index('telegram_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
