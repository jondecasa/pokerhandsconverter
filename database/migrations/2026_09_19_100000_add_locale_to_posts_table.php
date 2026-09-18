<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('slug');
            // Slug of the default-language post this one is a translation of.
            $table->string('translation_of')->nullable()->after('locale');

            $table->dropUnique(['slug']);
            $table->unique(['locale', 'slug']);
            $table->index('translation_of');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['translation_of']);
            $table->dropUnique(['locale', 'slug']);
            $table->unique('slug');
            $table->dropColumn(['locale', 'translation_of']);
        });
    }
};
