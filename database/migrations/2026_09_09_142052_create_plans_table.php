<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();

            // Pricing (display only — Stripe remains the source of truth for charges)
            $table->decimal('price', 8, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('interval', ['month', 'year'])->default('month');
            $table->string('stripe_price_id')->nullable();

            // What the package is for
            $table->string('stakes_cap')->nullable();   // e.g. "NL100" — covers up to this stake
            $table->string('stakes_label')->nullable(); // optional override text on the card
            $table->json('features')->nullable();        // bullet points shown on the card

            $table->unsignedSmallInteger('trial_days')->nullable(); // overrides global default

            $table->boolean('is_visible')->default(true);  // shown on the public pricing page
            $table->boolean('is_active')->default(true);    // can be subscribed to at all
            $table->boolean('is_highlighted')->default(false); // "Best value" badge
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
