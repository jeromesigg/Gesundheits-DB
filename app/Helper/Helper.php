<?php

namespace App\Helper;

use App\Enums\TrafficLight;
use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\Group;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use App\Models\HealthStatus;
use App\Models\Help;
use App\Models\Intervention;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;

class Helper
{
    public static function getHealthInfo($code)
    {
        return HealthInformation::where('code', $code)->first();
    }

    public static function getHealthForm($code)
    {
        return HealthForm::where('code_hash', HealthForm::hashCode($code))->first();
    }

    public static function updateGroup($master, $group_text)
    {
        $group = Group::where('name', '=', $group_text)->first();
        if (isset($group)) {
            $master->update(['group_id' => $group['id']]);
        } else {
            $master->update(['group_id' => null]);
        }
    }

    public static function updateCamp(User $user, Camp $camp)
    {
        $camp_user = CampUser::firstOrCreate(['camp_id' => $camp->id, 'user_id' => $user->id]);
        $role_id = $camp_user['role_id'];
        if ($role_id === null) {
            $role_id = $camp['global_camp'] ? UserRole::Participant : $user['role_id'];
        }
        $camp_user->update([
            'role_id' => $role_id,
        ]);
        $user->update([
            'camp_id' => $camp->id,
            'role_id' => $camp_user->role->id,
        ]);
    }

    public static function generateUniqueCode(): int
    {
        do {
            $code = random_int(100000, 999999);
        } while (HealthInformation::where('code', '=', $code)->first());

        return $code;
    }

    /**
     * Medizinischer Teil eines neuen Gesundheitsblatts, mit leeren Antworten auf die aktiven Fragen des Lagers.
     */
    public static function createHealthInformation(Camp $camp, int $code): HealthInformation
    {
        $healthInformation = HealthInformation::create(['code' => $code, 'camp_id' => $camp->id]);
        foreach ($camp->questions as $question) {
            HealthInformationQuestion::create([
                'question_id' => $question->id,
                'health_information_id' => $healthInformation->id,
            ]);
        }

        return $healthInformation;
    }

    public static function generateUniqueCampCode(): int
    {
        do {
            $code = random_int(1000, 9999);
        } while (Camp::where('code', '=', $code)->first());

        return $code;
    }

    public static function getHealthStatus(Intervention $intervention)
    {
        $status = null;
        if (! isset($intervention['date_close'])) {
            $light = $intervention->health_status_id ?? TrafficLight::Green;
            $red = $light === TrafficLight::Red ? 'red' : '';
            $yellow = $light === TrafficLight::Yellow ? 'yellow' : '';
            $green = $light === TrafficLight::Green ? 'green' : '';

            $status = '<div class="text-center-profile mbl">
                                    <div class="ampel row" id="ampel">
                                        <div class="ampel-btn col-4">
                                            <div class="circle '.$red.'"></div>
                                        </div>
                                        <div class="ampel-btn col-4">
                                            <div class="circle '.$yellow.'"></div>
                                        </div>
                                        <div class="ampel-btn col-4">
                                             <div class="circle '.$green.'"></div>
                                        </div>
                                    </div>
                                </div>';
        }

        return $status;
    }

    public static function getName(HealthInformation $healthInformation)
    {
        $name = '';
        if (Auth::user()->isManager() || Auth::user()->camp['show_names']) {
            $healthForm = Helper::getHealthForm($healthInformation['code']);
            $name = ' ('.$healthForm['nickname'].')';
        }

        return $name;
    }

    public static function getHealthInformationShow(Intervention $intervention, ?HealthInformation $healthinformation = null, $intervention_close = false)
    {
        if ($healthinformation === null) {
            $healthinformation = $intervention->health_information;
        }
        $name = Helper::getName($healthinformation);
        $camp = Auth::user()->camp;
        $title = 'J+S-Patientenprotokoll';
        $subtitle = 'von '.$healthinformation['code'].$name;
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Teilnehmerübersicht';
        $help['main_route'] = '/dashboard/healthinformation';
        $health_status = HealthStatus::pluck('name', 'id')->all();
        $intervention_masters = ['' => 'Übergeordnete Intervention'] + $healthinformation->interventions()->select('id', DB::raw("CONCAT(interventions.serial_number,' ',interventions.parameter) as name"))->whereNull('intervention_master_id')->pluck('name', 'interventions.id')->toArray();
        $interventions = $intervention->interventions()->get();
        $intervention_close = $intervention_close || isset($intervention['date_close']);

        return view('dashboard.healthinformation.show', compact('healthinformation', 'intervention', 'help', 'title', 'subtitle', 'health_status', 'intervention_masters', 'camp', 'interventions', 'intervention_close'));
    }

    public static function UpdateInterventionStatus(Intervention $intervention)
    {
        $intervention_sub = $intervention->interventions()->orderBy('serial_number', 'desc')->first();
        if ($intervention_sub) {
            $intervention->update(['health_status_id' => $intervention_sub['health_status_id']]);
        }
    }

    /**
     * Absoluter Pfad einer hochgeladenen Datei, aber nur, wenn sie wirklich in storage/app/files liegt.
     */
    public static function uploadedFilePath(?string $relativePath): ?string
    {
        if (blank($relativePath)) {
            return null;
        }
        $uploadFolder = realpath(storage_path('app/files'));
        $path = realpath(storage_path($relativePath));
        if ($uploadFolder === false || $path === false || ! str_starts_with($path, $uploadFolder.DIRECTORY_SEPARATOR) || ! is_file($path)) {
            return null;
        }

        return $path;
    }

    public static function getAvatarPath($avatar)
    {
        $path = null;
        if ($avatar) {
            if (str_starts_with($avatar, 'https')) {
                $path = $avatar;
            } else {
                $path = asset('storage/'.$avatar);
            }
        }

        return $path;
    }
}
