<?php

namespace App\Console\Commands;

use App\Enums\TrafficLight;
use App\Models\Camp;
use App\Models\Group;
use Illuminate\Console\Command;

class dailyTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a task daily';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->ResetDemoCamp();

        return Command::SUCCESS;
    }

    public function ResetDemoCamp()
    {
        Camp::where('demo', true)->update([
            'code' => 1234,
            'finish' => false,
            'counter' => 0,
        ]);
        $camp = Camp::where('demo', true)->first();
        if ($camp === null) {
            return;
        }
        $interventions = $camp->interventions()->get();
        foreach ($interventions as $intervention) {
            $intervention->update([
                'parameter' => fake()->realText(50, 1),
                'value' => fake()->realText(50, 1),
                'comment' => fake()->realText(100, 1),
                'user_erf' => 'Admin',
                'further_treatment' => null,
                'user_close' => null,
                'date_close' => null,
                'time_close' => null,
                'comment_close' => null,
            ]);
        }
        $health_infos = $camp->health_infos()->get();
        foreach ($health_infos as $health_info) {
            $health_info->update([
                'recent_issues' => null,
                'recent_issues_doctor' => null,
                'drug_longterm' => null,
                'drug_demand' => null,
                'drug_emergency' => null,
                'drugs_only_contact' => false,
                'ointment_only_contact' => false,
                'chronicle_diseases' => null,
                'allergy' => null,
                'health_status_id' => TrafficLight::Green,
                'accept_privacy_agreement' => false,
            ]);
        }
        $health_forms = $camp->health_forms()->get();
        foreach ($health_forms as $health_form) {
            $name = fake()->firstName();
            $health_form->update([
                'first_name' => $name,
                'last_name' => fake()->lastName(),
                'nickname' => $name,
                'street' => fake()->streetAddress(),
                'zip_code' => fake()->postcode(),
                'city' => fake()->city(),
                'birthday' => fake()->date(),
                'ahv' => fake()->ahv13(),
                'phone_number' => fake()->mobileNumber(),
                'group_id' => Group::pluck('id')[fake()->numberBetween(0, Group::count() - 1)],
                'emergency_contact_name' => null,
                'emergency_contact_address' => null,
                'emergency_contact_phone' => null,
                'doctor_contact' => null,
                'health_insurance_contact' => null,
                'accident_insurance_contact' => null,
                'liability_insurance_contact' => null,
                'finish' => false,
                'swimmer' => false,
                'vaccination' => null,
            ]);
        }
    }
}
