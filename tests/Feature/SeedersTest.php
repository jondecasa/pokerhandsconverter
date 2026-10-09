<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\RangeScenario;
use App\Models\RangeStudy;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RangeStudySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_range_seeder_loads_the_study_and_ignores_other_fixtures_in_its_folder(): void
    {
        $this->seed(RangeStudySeeder::class);

        $study = RangeStudy::where('slug', 'cash-6max-100bb')->firstOrFail();
        $this->assertSame(26, $study->scenarios()->count());
        $this->assertSame(1, RangeStudy::count());
    }

    #[Test]
    public function charts_that_classify_hands_instead_of_prescribing_an_action_stay_out_of_the_quiz(): void
    {
        $this->seed(RangeStudySeeder::class);

        $this->assertSame(['Rol'], RangeScenario::where('quiz_enabled', false)->pluck('group_label')->unique()->values()->all());
        $this->assertSame(22, RangeScenario::where('quiz_enabled', true)->count());
    }

    #[Test]
    public function the_full_database_seeder_runs_to_the_end(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Post::count());
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
