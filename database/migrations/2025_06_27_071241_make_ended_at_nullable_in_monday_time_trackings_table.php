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
        Schema::table('monday_time_trackings', function (Blueprint $table) {
            $table->dateTime('ended_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_time_trackings', function (Blueprint $table) {
            $table->dateTime('ended_at')->nullable(false)->change();
        });
    }
};
