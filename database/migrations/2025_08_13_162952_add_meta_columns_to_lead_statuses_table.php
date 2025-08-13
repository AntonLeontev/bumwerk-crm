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
        Schema::table('lead_statuses', function (Blueprint $table) {
            $table->after('is_final', function (Blueprint $table) {
                $table->boolean('is_start')->default(false);
                $table->boolean('is_win')->default(false);
                $table->boolean('is_loose')->default(false);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_statuses', function (Blueprint $table) {
            $table->dropColumn('is_start');
            $table->dropColumn('is_win');
            $table->dropColumn('is_loose');
        });
    }
};
