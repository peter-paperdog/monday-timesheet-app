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
        // Ensure all group_id values are converted to string before modifying the column
        DB::statement("UPDATE monday_items SET group_id = CAST(group_id AS CHAR) WHERE group_id IS NOT NULL");

        // Modify group_id column type to string
        Schema::table('monday_items', function (Blueprint $table) {
            $table->string('group_id', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_items', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->change();
        });
    }
};
