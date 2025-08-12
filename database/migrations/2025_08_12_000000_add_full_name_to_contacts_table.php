<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        Schema::table('contacts', function (Blueprint $table) use ($driver) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $table->fullText(['surname', 'name', 'patronymic'], 'contacts_fulltext_name');
            } else {
                $table->index(['surname', 'name', 'patronymic'], 'contacts_name_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('contacts', function (Blueprint $table) use ($driver) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $table->dropFullText('contacts_fulltext_name');
            } else {
                $table->dropIndex('contacts_name_idx');
            }
        });
    }
};
