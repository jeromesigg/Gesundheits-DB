<?php

namespace Tests\Feature\Console;

use App\Models\Camp;
use App\Models\HealthInformation;
use Database\Seeders\DemoCampSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * task:daily setzt das Demo-Lager zurück, täglich um 3 Uhr über den Scheduler.
 */
class DailyTaskTest extends TestCase
{
    public function test_task_is_scheduled_daily_at_three(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'task:daily'));

        $this->assertCount(1, $events);
        $this->assertSame('0 3 * * *', $events->first()->expression);
    }

    public function test_task_resets_demo_camp_and_leaves_other_camps_alone(): void
    {
        $this->seed(DemoCampSeeder::class);
        $scenario = CampScenario::create();
        $scenario->openInfo->update(['allergy' => 'echte Allergie']);
        $demoCamp = Camp::where('demo', true)->firstOrFail();
        $demoCamp->update(['code' => 9999, 'finish' => true]);
        $demoInfo = HealthInformation::where('camp_id', $demoCamp->id)->firstOrFail();
        $demoInfo->update(['allergy' => 'Eingabe von Besuchenden']);

        $this->artisan('task:daily')->assertSuccessful();

        $demoCamp = $demoCamp->fresh();
        $this->assertSame(1234, $demoCamp->code);
        $this->assertFalse($demoCamp->finish);
        $this->assertNull($demoInfo->fresh()->allergy);
        $this->assertSame('echte Allergie', $scenario->openInfo->fresh()->allergy);
    }

    public function test_task_succeeds_without_demo_camp(): void
    {
        $this->artisan('task:daily')->assertSuccessful();
    }
}
