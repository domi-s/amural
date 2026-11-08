<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('concept_id')->constrained('concepts');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['lesson_id', 'concept_id']);
        });

        // At most one primary concept per lesson. Works on PostgreSQL and SQLite.
        DB::statement('CREATE UNIQUE INDEX lesson_concepts_one_primary ON lesson_concepts (lesson_id) WHERE is_primary');
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_concepts');
    }
};
