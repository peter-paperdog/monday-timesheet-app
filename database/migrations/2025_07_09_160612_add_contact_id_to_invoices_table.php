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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('contact_id')->nullable();

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->onDelete('set null');
        });
    }
};
