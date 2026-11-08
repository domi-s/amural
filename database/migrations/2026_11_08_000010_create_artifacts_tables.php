<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('owner_id')->constrained('users');
            $table->enum('type', ['youtube', 'video_url', 'image', 'file', 'worksheet', 'link']);
            $table->string('locale_code', 10)->nullable();
            $table->foreign('locale_code')->references('code')->on('locales');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('youtube_id')->nullable();
            $table->string('licence');
            $table->timestamps();
        });

        Schema::create('artifactables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->cascadeOnDelete();
            $table->string('artifactable_type');
            $table->unsignedBigInteger('artifactable_id');
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('attached_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['artifactable_type', 'artifactable_id']);
            $table->unique(['artifact_id', 'artifactable_type', 'artifactable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artifactables');
        Schema::dropIfExists('artifacts');
    }
};
