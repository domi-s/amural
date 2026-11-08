<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // concept_labels is a cache of the latest revision. The revisions table is the record.
        // current_revision_id is linked to that table in the foreign-key migration below.
        Schema::create('concept_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_id')->constrained('concepts');
            $table->string('locale_code', 10);
            $table->foreign('locale_code')->references('code')->on('locales');
            $table->string('pref', 200);
            $table->jsonb('alt');
            $table->text('definition')->nullable();
            $table->string('slug');
            $table->enum('status', ['machine', 'draft', 'reviewed'])->default('machine');
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->text('search_text');
            $table->timestamps();

            $table->unique(['concept_id', 'locale_code']);
        });

        Schema::create('concept_label_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concept_label_id')->constrained('concept_labels');
            $table->string('pref', 200);
            $table->jsonb('alt');
            $table->text('definition')->nullable();
            $table->enum('status', ['machine', 'draft', 'reviewed']);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_label_revisions');
        Schema::dropIfExists('concept_labels');
    }
};
