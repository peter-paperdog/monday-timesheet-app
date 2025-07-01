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
        Schema::table('tasks', function (Blueprint $table) {
            // Add polymorphic columns
            $table->unsignedBigInteger('taskable_id')->after('group_id');
            $table->string('taskable_type')->after('taskable_id');
        });

        // Copy existing project_id values as polymorphic Project references
        DB::table('tasks')->update([
            'taskable_id' => DB::raw('project_id'),
            'taskable_type' => 'App\\Models\\Project',
        ]);

        // Drop the old project_id
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Restore old column
            $table->unsignedBigInteger('project_id')->nullable()->after('group_id');
        });

        // Restore project_id only for tasks linked to Project
        DB::table('tasks')
            ->where('taskable_type', 'App\\Models\\Project')
            ->update([
                'project_id' => DB::raw('taskable_id')
            ]);

        // Drop polymorphic columns
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['taskable_id', 'taskable_type']);
        });
    }
};
