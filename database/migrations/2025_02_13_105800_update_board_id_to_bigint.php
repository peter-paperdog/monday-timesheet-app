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
            $table->unsignedBigInteger('id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_boards', function (Blueprint $table) {
            $table->string('id')->change(); // Revert back if needed
        });
    }
};
