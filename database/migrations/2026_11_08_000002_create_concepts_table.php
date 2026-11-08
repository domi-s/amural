<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concepts', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->enum('subject', ['mathematics'])->default('mathematics');
            $table->enum('kind', ['definition', 'concept', 'procedure', 'result', 'representation', 'application']);
            $table->enum('stage_from', ['early_primary', 'primary', 'lower_secondary', 'upper_secondary', 'undergraduate', 'graduate']);
            $table->enum('stage_to', ['early_primary', 'primary', 'lower_secondary', 'upper_secondary', 'undergraduate', 'graduate']);
            $table->enum('status', ['machine_proposed', 'draft', 'reviewed', 'deprecated'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('concept_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->constrained('concepts');
            $table->foreignId('replaced_by_id')->constrained('concepts');
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_replacements');
        Schema::dropIfExists('concepts');
    }
};
