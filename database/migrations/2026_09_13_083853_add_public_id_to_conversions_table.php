<?php

use App\Models\Conversion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->string('public_id', 30)->nullable()->unique()->after('id');
        });

        // Backfill existing rows — new ones get theirs from the model's
        // "creating" hook, but rows already in the table need one too.
        Conversion::whereNull('public_id')->orderBy('id')->each(function (Conversion $conversion): void {
            $conversion->forceFill(['public_id' => Str::random(26)])->save();
        });
    }

    public function down(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
