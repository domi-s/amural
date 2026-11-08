<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concept_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_concept_id')->constrained('concepts');
            $table->foreignId('to_concept_id')->constrained('concepts');
            $table->enum('type', ['broader', 'prerequisite', 'special_case', 'related']);
            $table->enum('strength', ['required', 'helpful'])->nullable();
            $table->decimal('confidence', 3, 2)->nullable();
            $table->jsonb('evidence');
            $table->enum('status', ['machine_proposed', 'reviewed', 'rejected']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['from_concept_id', 'to_concept_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_edges');
    }
};
