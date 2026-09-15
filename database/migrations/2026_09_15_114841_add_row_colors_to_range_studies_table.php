<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('range_studies', function (Blueprint $table) {
            // {row_label: "#hexcolor"} — one background color per position
            // (EP, MP, CO, BTN...), reused for that row's buttons everywhere
            // in the study's navigation.
            $table->json('row_colors')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('range_studies', function (Blueprint $table) {
            $table->dropColumn('row_colors');
        });
    }
};
