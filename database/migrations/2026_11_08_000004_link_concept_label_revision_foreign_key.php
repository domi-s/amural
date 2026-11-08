<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // concept_labels and concept_label_revisions reference each other, so this
        // side of the link is added once both tables exist.
        Schema::table('concept_labels', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('concept_label_revisions');
        });
    }

    public function down(): void
    {
        Schema::table('concept_labels', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
        });
    }
};
