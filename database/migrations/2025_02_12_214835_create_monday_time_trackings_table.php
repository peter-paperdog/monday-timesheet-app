<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monday_time_trackings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Set monday_id as the primary key
            $table->unsignedBigInteger('user_id'); // User who started the timer
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monday_time_trackings');
    }
};
