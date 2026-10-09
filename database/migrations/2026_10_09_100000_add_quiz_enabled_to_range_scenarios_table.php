<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('range_scenarios', function (Blueprint $table) {
            $table->boolean('quiz_enabled')->default(true)->after('is_default');
        });

        // Charts that classify hands (e.g. favourable / unfavourable) rather
        // than prescribe an action have no right answer to quiz on.
        DB::table('range_scenarios')->get(['id', 'legend'])->each(function (object $scenario) {
            $keys = array_column(json_decode($scenario->legend, true) ?: [], 'key');

            if ($keys !== [] && array_diff($keys, ['fav', 'unfav']) === []) {
                DB::table('range_scenarios')->where('id', $scenario->id)->update(['quiz_enabled' => false]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('range_scenarios', function (Blueprint $table) {
            $table->dropColumn('quiz_enabled');
        });
    }
};
