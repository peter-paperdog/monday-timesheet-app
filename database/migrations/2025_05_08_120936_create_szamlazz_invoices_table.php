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
        Schema::create('szamlazz_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('szamlaszam');
            $table->date('kelt');
            $table->date('telj');
            $table->date('fizh');
            $table->string('fizmod');
            $table->string('devizanem');
            $table->double('netto');
            $table->double('afa');
            $table->double('brutto');
            $table->string('vevo_nev');
            $table->string('vevo_adoszam');
            $table->string('szallito_nev');
            $table->string('szallito_adoszam');
            $table->boolean('teszt')->default(false);
            $table->boolean('sztornozott')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('szamlazz_invoices');
    }
};
