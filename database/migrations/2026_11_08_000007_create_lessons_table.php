<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // head_revision_id and the fork pointers reference revisions, which are created
        // after this table. Their foreign keys are added in the link migration below.
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('locale_code', 10);
            $table->foreign('locale_code')->references('code')->on('locales');
            $table->string('title');
            $table->string('slug');
            $table->string('licence')->default('CC-BY-SA-4.0');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedBigInteger('head_revision_id')->nullable();
            $table->unsignedBigInteger('forked_from_revision_id')->nullable();
            $table->unsignedBigInteger('translated_from_revision_id')->nullable();
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->enum('type', ['heading', 'markdown', 'embed', 'image', 'exercise']);
            $table->jsonb('derived_from');
            $table->timestamps();
        });

        Schema::create('block_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('blocks');
            $table->jsonb('content');
            $table->string('content_hash', 64)->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_version_id')->nullable()->constrained('block_versions')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->foreignId('parent_id')->nullable()->constrained('revisions');
            $table->foreignId('merge_parent_id')->nullable()->constrained('revisions');
            $table->jsonb('block_version_ids');
            $table->string('title');
            $table->string('message', 200);
            $table->string('licence');
            $table->foreignId('author_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
        Schema::dropIfExists('block_versions');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('lessons');
    }
};
