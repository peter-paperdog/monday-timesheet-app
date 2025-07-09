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
        Schema::table('invoice_projects', function (Blueprint $table) {
            $table->dropForeign(['invoice_group_id']);
            $table->dropColumn('invoice_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_group_id')->nullable();
            $table->foreign('invoice_group_id')->references('id')->on('invoice_groups');
        });
    }
};
