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
        Schema::table('invoice_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_project_id')->nullable()->after('invoice_id');

            $table->foreign('invoice_project_id')
                ->references('id')
                ->on('invoice_projects')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_groups', function (Blueprint $table) {
            $table->dropForeign(['invoice_project_id']);
            $table->dropColumn('invoice_project_id');
        });
    }
};
