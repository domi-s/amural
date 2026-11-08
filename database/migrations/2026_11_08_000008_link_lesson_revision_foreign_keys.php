<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lessons point at revisions, and revisions point back at lessons, so the lesson
        // side of the link is added once both tables exist.
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('head_revision_id')->references('id')->on('revisions');
            $table->foreign('forked_from_revision_id')->references('id')->on('revisions');
            $table->foreign('translated_from_revision_id')->references('id')->on('revisions');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['head_revision_id']);
            $table->dropForeign(['forked_from_revision_id']);
            $table->dropForeign(['translated_from_revision_id']);
        });
    }
};
