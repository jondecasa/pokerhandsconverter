<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->unsignedInteger('splash_pots')->default(0)->after('hand_count');
            $table->unsignedInteger('bomb_pots')->default(0)->after('splash_pots');
        });
    }

    public function down(): void
    {
        Schema::table('conversions', function (Blueprint $table) {
            $table->dropColumn(['splash_pots', 'bomb_pots']);
        });
    }
};
