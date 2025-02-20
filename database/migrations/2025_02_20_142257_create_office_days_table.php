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
        Schema::create('office_days', function (Blueprint $table) {
            $table->date('date');
            $table->unsignedBigInteger('user_id'); // Reference to users table
            $table->string('status');
            $table->timestamps();

            $table->primary(['date', 'user_id']); // Composite primary key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_days');
    }
};
