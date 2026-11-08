<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concept_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->nullable()->constrained('concepts');
            $table->string('source');
            $table->string('source_version');
            $table->string('source_item_id');
            $table->string('locale');
            $table->text('text');
            $table->jsonb('path');
            $table->string('licence');
            $table->text('attribution');
            $table->string('url')->nullable();
            $table->jsonb('raw');
            $table->timestamps();

            $table->unique(['source', 'source_item_id']);
        });

        Schema::create('concept_external_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->constrained('concepts');
            $table->enum('scheme', ['learning_commons', 'wikidata', 'msc2020']);
            $table->string('value');
            $table->timestamps();

            $table->unique(['scheme', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_external_ids');
        Schema::dropIfExists('concept_sources');
    }
};
