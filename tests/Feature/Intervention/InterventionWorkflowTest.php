<?php

namespace Tests\Feature\Intervention;

use App\Models\Intervention;
use Tests\Support\CampScenario;
use Tests\TestCase;

/**
 * Ablauf der Interventionen: Master mit Laufnummer des Lagers, Folge-Interventionen mit eigener Nummerierung,
 * Abschliessen und Status (Ampel).
 */
class InterventionWorkflowTest extends TestCase
{
    private CampScenario $scenario;

    private Intervention $master;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scenario = CampScenario::create();
        $this->master = $this->scenario->openIntervention;
        $this->master->update(['serial_number' => 1, 'max_serial_number' => 0]);
    }

    public function test_follow_up_gets_next_number_of_master(): void
    {
        $this->store(['intervention_master_id' => $this->master->id, 'parameter' => 'Kontrolle 1']);
        $this->store(['intervention_master_id' => $this->master->id, 'parameter' => 'Kontrolle 2']);

        $this->assertSame(1, Intervention::where('parameter', 'Kontrolle 1')->value('serial_number'));
        $this->assertSame(2, Intervention::where('parameter', 'Kontrolle 2')->value('serial_number'));
        $this->assertSame(2, $this->master->fresh()->max_serial_number);
        $this->assertSame(2, $this->master->interventions()->count());
    }

    public function test_several_new_entries_are_attached_to_master(): void
    {
        $this->store([
            'intervention_id' => $this->master->id,
            'intervention_new' => [
                $this->entry('Nachtrag 1'),
                $this->entry('Nachtrag 2'),
            ],
        ]);

        $this->assertSame([1, 2], $this->master->interventions()->orderBy('serial_number')->pluck('serial_number')->all());
        $this->assertSame(2, $this->master->fresh()->max_serial_number);
    }

    public function test_master_status_follows_latest_follow_up(): void
    {
        $this->store(['intervention_master_id' => $this->master->id, 'health_status_id' => config('status.health_red')]);
        $this->assertSame(config('status.health_red'), $this->master->fresh()->health_status_id);

        $this->store(['intervention_master_id' => $this->master->id, 'health_status_id' => config('status.health_green')]);
        $this->assertSame(config('status.health_green'), $this->master->fresh()->health_status_id);
    }

    public function test_closing_master_closes_open_follow_ups(): void
    {
        $this->store(['intervention_master_id' => $this->master->id, 'parameter' => 'Offen']);

        $this->store([
            'intervention_id' => $this->master->id,
            'date_close' => '2026-07-10',
            'time_close' => '18:00',
            'user_close' => 'Lagerärztin',
            'comment_close' => 'Erledigt',
            'further_treatment' => 'Keine',
        ]);

        $master = $this->master->fresh();
        $this->assertSame('2026-07-10', $master->date_close);
        $followUp = Intervention::where('parameter', 'Offen')->firstOrFail();
        $this->assertSame('2026-07-10', $followUp->date_close);
        $this->assertSame('Lagerärztin', $followUp->user_close);
        $this->assertSame('Erledigt', $followUp->comment_close);
    }

    public function test_closed_intervention_is_no_longer_open_for_health_information(): void
    {
        $this->assertSame(1, $this->scenario->openInfo->interventions_open()->count());

        $this->store(['intervention_id' => $this->master->id, 'date_close' => '2026-07-10', 'time_close' => '18:00', 'user_close' => 'Leitung']);

        $this->assertSame(0, $this->scenario->openInfo->fresh()->interventions_open()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function store(array $overrides): void
    {
        $this->actingAs($this->scenario->helper)
            ->post(route('interventions.store'), array_merge([
                'health_information_id' => $this->scenario->openInfo->id,
            ], $this->entry('Eintrag'), $overrides))
            ->assertRedirect(route('healthinformation.show', $this->scenario->openInfo));
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(string $parameter): array
    {
        return [
            'date' => '2026-07-10',
            'time' => '10:00',
            'parameter' => $parameter,
            'value' => 'Wert',
            'user_erf' => $this->scenario->helper->username,
            'health_status_id' => config('status.health_yellow'),
        ];
    }
}
