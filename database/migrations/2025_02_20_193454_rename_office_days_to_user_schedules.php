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
        Schema::table('user_schedules', function (Blueprint $table) {
            Schema::rename('office_days', 'user_schedules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_schedules', function (Blueprint $table) {
            Schema::rename('user_schedules', 'office_days');
        });
    }
};
