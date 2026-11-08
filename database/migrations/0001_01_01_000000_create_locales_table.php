<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table) {
            $table->string('code', 10)->primary();
            $table->string('language', 3);
            $table->string('flag_country', 2);
            $table->string('native_name');
            $table->string('english_name');
            $table->string('script', 4);
            $table->boolean('is_interface')->default(false);
            $table->boolean('is_launch')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
