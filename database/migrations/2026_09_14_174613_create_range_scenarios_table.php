<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('range_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('range_study_id')->constrained()->cascadeOnDelete();

            // Left-hand navigation: group box (e.g. "vs 3bet") -> row (e.g. "CO") -> button (e.g. "vs SBBB").
            $table->string('group_label');
            $table->unsignedInteger('group_order')->default(0);
            $table->string('row_label');
            $table->unsignedInteger('row_order')->default(0);
            $table->string('button_label');
            $table->unsignedInteger('button_order')->default(0);

            $table->boolean('is_default')->default(false); // shown when the study page first opens

            $table->json('legend'); // [{key, label, color}, ...] — action colors used by combos below
            $table->json('combos'); // {"AKs": "raise", "72o": "fold", ...} — 169 possible hands
            $table->json('stats')->nullable(); // {"badges":[{label,value,highlight}], "bars":[{label,pct,color}]}

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('range_scenarios');
    }
};
