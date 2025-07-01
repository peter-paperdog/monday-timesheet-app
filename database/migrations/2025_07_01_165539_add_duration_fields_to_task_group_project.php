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
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('duration_seconds')->default(0);
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('duration_seconds')->default(0);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('duration_seconds')->default(0);
        });
    }
};
