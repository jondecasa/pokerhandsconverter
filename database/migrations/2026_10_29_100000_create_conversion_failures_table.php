<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every upload the converter turned down or crashed on. Successful conversions live in "conversions";
        // this is the other half, so a "my file doesn't convert" report can be answered from the admin panel.
        Schema::create('conversion_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();   // bytes, as received
            $table->string('reason', 40)->index();                  // see ConversionFailure::REASONS
            $table->text('message');                                // what the user was told
            $table->text('details')->nullable();                    // what we saw (first line, limits, exception…)
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_failures');
    }
};
