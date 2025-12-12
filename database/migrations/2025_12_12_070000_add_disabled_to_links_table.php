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
        Schema::table('links', function (Blueprint $table) {
            $table->boolean('disabled')->default(false)->after('clicks');
            $table->text('disable_reason')->nullable()->after('disabled');
            $table->timestamp('disabled_at')->nullable()->after('disable_reason');
            
            // Add indexes for better performance
            $table->index('disabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropIndex(['disabled']);
            $table->dropColumn(['disabled', 'disable_reason', 'disabled_at']);
        });
    }
};