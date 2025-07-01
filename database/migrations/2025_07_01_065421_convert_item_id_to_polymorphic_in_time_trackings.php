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
            $table->unsignedBigInteger('trackable_id')->nullable()->after('user_id');
            $table->string('trackable_type')->nullable()->after('trackable_id');
        });

        DB::table('monday_time_trackings')->update([
            'trackable_id' => DB::raw('item_id'),
            'trackable_type' => 'App\\Models\\Task',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monday_time_trackings', function (Blueprint $table) {
            $table->dropColumn(['trackable_id', 'trackable_type']);
        });
    }
};
