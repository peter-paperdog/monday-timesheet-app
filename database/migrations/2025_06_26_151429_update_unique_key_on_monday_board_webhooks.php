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
        Schema::table('monday_board_webhooks', function (Blueprint $table) {
            $table->dropUnique('monday_board_webhooks_board_id_event_unique');
            $table->unique(['board_id', 'event']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_board_webhooks', function (Blueprint $table) {
            $table->dropUnique(['board_id', 'event']);
            $table->unique('board_id');
        });
    }
};
