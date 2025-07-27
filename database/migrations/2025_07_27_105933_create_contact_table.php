<?php

use App\Models\Contact;
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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('patronymic')->nullable();
            $table->string('telegram')->nullable();
            $table->string('comment', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->timestamps();
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phones');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('contacts');
    }
};
