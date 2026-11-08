<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_content_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('locale_code', 10);
            $table->foreign('locale_code')->references('code')->on('locales');
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['user_id', 'locale_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_content_locales');
    }
};
