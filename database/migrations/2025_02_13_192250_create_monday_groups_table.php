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
        Schema::create('monday_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // No auto-increment
            $table->string('name');
            $table->unsignedBigInteger('board_id'); // Foreign key to boards
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monday_groups');
    }
};
