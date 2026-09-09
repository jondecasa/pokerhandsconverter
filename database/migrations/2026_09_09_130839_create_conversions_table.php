<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('output_path');           // relative path on the "local" disk
            $table->unsignedInteger('hand_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedBigInteger('input_bytes')->default(0);
            $table->json('format_breakdown')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
