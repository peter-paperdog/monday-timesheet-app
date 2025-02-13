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
        Schema::table('monday_boards', function (Blueprint $table) {
            $table->dropColumn('activity_at'); // Drop the column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_boards', function (Blueprint $table) {
            $table->timestamp('activity_at')->nullable(); // Re-add column if rolled back
        });
    }
};
