<?php

use App\Models\Contact;
use App\Models\LeadStatus;
use App\Models\User;
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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('amount')->nullable();
            $table->foreignIdFor(Contact::class)->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class)->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Ответственный сотрудник');
            $table->foreignIdFor(LeadStatus::class, 'status_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
