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
        Schema::create('szamlazz_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('external_id')->unique(); // <banktranz><id>
            $table->string('bankszamla');
            $table->date('erteknap');
            $table->enum('irany', ['BE', 'KI']);
            $table->string('tipus')->nullable();
            $table->boolean('technikai');
            $table->double('osszeg');
            $table->string('devizanem');
            $table->string('partner_nev')->nullable();
            $table->string('partner_bankszamla')->nullable();
            $table->string('kozlemeny')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('szamlazz_transactions');
    }
};
