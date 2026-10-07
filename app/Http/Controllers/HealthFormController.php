<?php

namespace App\Http\Controllers;

use App\Exports\HealthFormsExport;
use App\Helper\Helper;
use App\Imports\HealthFormsImport;
use App\Models\Camp;
use App\Models\City;
use App\Models\Group;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use App\Models\Help;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Ixudra\Curl\Facades\Curl;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Validator;
use Yajra\DataTables\Facades\DataTables;

class HealthFormController extends Controller
{
    /**
     * Session-Schlüssel mit den IDs der Gesundheitsblätter, die per Lager- und persönlichem Code freigeschaltet wurden.
     */
    private const UNLOCKED_HEALTH_FORMS = 'unlocked_health_forms';

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $title = 'Gesundheitsblätter';
        $help = Help::where('title', $title)->first();

        return view('dashboard.healthform.index', compact('title', 'help'));
    }

    public function createDataTables()
    {
        //
        $camp = Auth::user()->camp;
        $healthForm = HealthForm::where('camp_id', '=', $camp['id'])->get();

        return DataTables::of($healthForm)
            ->editColumn('group', function (HealthForm $healthForm) {
                return $healthForm->group ? $healthForm->group['name'] : $healthForm['group_text'];
            })
            ->editColumn('birthday', function (HealthForm $healthForm) {
                return $healthForm['birthday'] ? Carbon::parse($healthForm['birthday'])->format('d.m.Y') : '';
            })
            ->editColumn('nickname', function (HealthForm $healthForm) {
                $nickname = $healthForm->nickname;

                return '<a class="font-medium text-fg-brand hover:underline text-blue-500" href='.\URL::route('healthforms.showOrEdit', $healthForm).'>'.$nickname.'</a>';
            })
            ->editColumn('finish', function (HealthForm $healthForm) {
                return $healthForm->finish ? 'Ja' : 'Nein';
            })
            ->addColumn('Actions', function (HealthForm $healthForm) {
                $buttons = '<form action="'.\URL::route('healthforms.open', $healthForm).'" method="post">'.csrf_field();
                $camp = Auth::user()->camp;
                if ($healthForm['finish'] && $camp['forms_finished']) {
                    $buttons .= '  <button type="submit" class="btn btn-secondary btn-sm">Öffnen</button>';
                }
                $buttons .= '</form>';

                //                $buttons .= '<form action="'.\URL::route('healthforms.newCode', $healthForm).'" method="post">' . csrf_field();
                //                $buttons .= '  <button type="submit" class="btn btn-secondary btn-sm">Neuer Code</button>';
                //                $buttons .= '</form>';
                return $buttons;
            })
            ->rawColumns(['nickname', 'code', 'Actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
        $title = 'Gesundheitsblatt erstellen';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Gesundheitsblätter';
        $help['main_route'] = '/dashboard/healthforms';

        return view('dashboard.healthform.create', compact('title', 'help'));
    }

    public function createNew(Request $request)
    {
        //
        return $request;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
        $input = $request->all();
        $code = Helper::generateUniqueCode();
        $camp = Auth::user()->camp;
        $input['code'] = Crypt::encryptString($code);
        $input['camp_id'] = $camp['id'];
        $healthform = HealthForm::create($input);
        $healthinfo = HealthInformation::create(['code' => $code, 'camp_id' => $camp['id']]);
        Helper::updateGroup($healthform, $input['group_text']);
        $questions = $camp->questions;
        foreach ($questions as $question) {
            HealthInformationQuestion::create(['question_id' => $question['id'],
                'health_information_id' => $healthinfo['id']]);
        }
        if ($camp['independent_form_fill']) {
            $title = 'Gesundheitsblätter';
            $help = Help::where('title', $title)->first();

            return view('dashboard.healthform.index', compact('title', 'help'));
        } else {
            $health_questions = $healthinfo->questions;
            $title = 'Hallo';
            $subtitle = ' '.$healthform['nickname'].' ('.$healthform['code'].')';
            $help = Help::where('title', $title)->first();

            return view('healthform.edit', compact('healthform', 'healthinfo', 'health_questions', 'title', 'help', 'subtitle', 'camp'));
        }
    }

    public function uploadFile(Request $request)
    {
        if ($request->hasFile('file')) {

            $array = (new HealthFormsImport)->toArray(request()->file('file'));
            $importData_arr = $array[0];
            foreach ($importData_arr as &$importData) {
                if (! empty($importData['geburtstag'])) {
                    if (is_numeric($importData['geburtstag'])) {
                        $dayCarbon = Carbon::instance(Date::excelToDateTimeObject($importData['geburtstag']));
                        $day = Carbon::parse($dayCarbon)->format('Y-m-d');
                        $importData['geburtstag'] = $day;
                    } else {
                        $importData['geburtstag'] = trim($importData['geburtstag']);
                        $day = Carbon::parse($importData['geburtstag'])->format('Y-m-d');
                        $importData['geburtstag'] = $day;

                    }
                }
                $importData['ceviname'] = $importData['ceviname'] ? $importData['ceviname'] : $importData['vorname'];

            }

            $camp = Auth::user()->camp;

            // Insert to MySQL database
            foreach ($importData_arr as $importData_row) {
                if (! empty(trim($importData_row['ahv_nr'])) && ! empty(trim($importData_row['geburtstag']))) {
                    $group = Group::where('short_name', '=', $importData_row['abteilung'])->first();
                    $code = Helper::generateUniqueCode();
                    $insertData = [

                        'code' => Crypt::encryptString($code),
                        'first_name' => trim($importData_row['vorname']),
                        'last_name' => trim($importData_row['nachname']),
                        'nickname' => trim($importData_row['ceviname']),
                        'street' => trim($importData_row['strasse']),
                        'zip_code' => trim($importData_row['plz']),
                        'city' => trim($importData_row['ort']),
                        'birthday' => trim($importData_row['geburtstag']),
                        'ahv' => trim($importData_row['ahv_nr']),
                        'group_id' => $group ? $group['id'] : null,
                        'group_text' => $importData_row['abteilung'],
                        'camp_id' => $camp['id'],
                    ];
                    HealthForm::firstOrCreate(['ahv' => $importData_row['ahv_nr'], 'camp_id' => $camp['id']], $insertData);
                    HealthInformation::firstOrCreate(['code' => $code, 'camp_id' => $camp['id']]);
                }
            }
        }

        return redirect()->action('HealthFormController@index');

    }

    public function import()
    {
        $response = Curl::to('https://db.cevi.ch/groups/'.config('app.group_id').'/events/'.config('app.event_id').'/participations.json')
            ->withData(['token' => config('app.token')])
            ->get();
        $response = json_decode($response);
        $participants = $response->event_participations;
        foreach ($participants as $participant) {
            $user = User::where('id', '=', $participant->id)->first();
            if (! $user) {
                $group = Group::on('mysql_info')->where('id', '=', $participant->ortsgruppe_id)->first();
                if (! $group) {
                    $group_id = 2;
                } else {
                    $group_id = $group['id'];
                }
                $code = Helper::generateUniqueCode();
                $insertData = [
                    //                        'id' => $participant->id,
                    'code' => Crypt::encryptString($code),
                    'nickname' => $participant->nickname ? $participant->nickname : $participant->first_name,
                    'last_name' => $participant->last_name,
                    'first_name' => $participant->first_name,
                    'street' => $participant->address,
                    'zip_code' => $participant->zip_code,
                    'city' => $participant->town,
                    'group_id' => $group_id,
                    'birthday' => $participant->birthday,
                ];
                HealthForm::create($insertData);
                HealthInformation::create(['code' => $code]);
            }
        }

        return true;
    }

    public function NewCode(HealthForm $healthform)
    {
        $code = Helper::generateUniqueCode();
        $healthform->update(['code' => Crypt::encryptString($code)]);
        HealthInformation::create(['code' => $code,
            'drugs_only_contact' => true,
            'ointment_only_contact' => true]);

        return redirect('/dashboard/healthforms');
    }

    public function downloadFile()
    {
        return Excel::download(new HealthFormsExport, 'Teilnehmerliste.xlsx');
    }

    public function show(HealthForm $healthform)
    {
        $this->authorizeHealthFormAccess($healthform);
        $healthinfo = Helper::getHealthInfo($healthform['code']);
        $title = 'Gesundheitsblatt anzeigen';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Gesundheitsblätter';
        $help['main_route'] = '/dashboard/healthforms';

        return view('healthform.show', compact('healthform', 'healthinfo', 'help', 'title'));

    }

    public function showOrEdit(HealthForm $healthform)
    {
        $this->authorizeCamp($healthform->camp_id);
        $camp = Auth::user()->camp;
        $healthinfo = Helper::getHealthInfo($healthform['code']);
        if ($camp['independent_form_fill'] || ($healthform['finish'] && $camp['forms_finished'])) {
            $title = 'Gesundheitsblatt anzeigen';
            $help = Help::where('title', $title)->first();

            return view('healthform.show', compact('healthform', 'healthinfo', 'help', 'title'));
        } else {
            $health_questions = null;
            if ($healthinfo) {
                $health_questions = $healthinfo->questions;
            }
            $title = 'Hallo';
            $subtitle = ' '.$healthform['nickname'].' ('.$healthform['code'].')';
            $help = Help::where('title', $title)->first();
            $help['main_title'] = 'Gesundheitsblätter';
            $help['main_route'] = '/dashboard/healthforms';

            return view('healthform.edit', compact('healthform', 'healthinfo', 'health_questions', 'help', 'title', 'subtitle', 'camp'));
        }

    }

    public function downloadPDF(HealthForm $healthform)
    {
        $this->authorizeHealthFormAccess($healthform);
        $healthinfo = Helper::getHealthInfo($healthform['code']);

        return view('healthform.print', compact('healthform', 'healthinfo'));
    }

    public function open(HealthForm $healthform)
    {
        $this->authorizeCamp($healthform->camp_id);
        $input['finish'] = false;
        $input['date_finished'] = null;
        $healthform->update($input);

        return redirect('/dashboard/healthforms');
    }

    public function edit(Request $request)
    {
        //

        $input = $request->all();
        if (! $request->filled(['camp_code', 'code'])) {
            return redirect('/');
        }
        $camp = Camp::where('code', $input['camp_code'])->first();
        if (isset($camp) && ($camp->count() > 0) && ! $camp['finish']) {
            //            $healthform = HealthForm::where('code', 'LIKE', '%' . Crypt::encrypt($input['code']) . '%')->first();
            $code = $input['code'];
            $healthform = HealthForm::where('camp_id', $camp->id)->get()->first(function ($record) use ($code) {
                return $record->code == $code;
            });
            if ($healthform == null) {
                return redirect()->to(url()->previous())
                    ->withErrors('Kein Gesundheitsblatt mit den Eingaben gefunden.')->withInput();
            }
            $request->session()->push(self::UNLOCKED_HEALTH_FORMS, $healthform->id);
            $healthinfo = Helper::getHealthInfo($healthform['code']);
            $title = 'Hallo';
            $subtitle = ' '.$healthform['nickname'].' ('.$healthform['code'].')';
            $help = Help::where('title', $title)->first();
            if ($healthform['finish'] && $camp['forms_finished']) {
                return view('healthform.show', compact('healthform', 'healthinfo', 'title', 'subtitle', 'help'));
            } else {
                $health_questions = $healthinfo->questions;

                return view('healthform.edit', compact('healthform', 'healthinfo', 'health_questions', 'title', 'subtitle', 'help', 'camp'));
            }
        } else {
            return redirect()->to(url()->previous())
                ->withErrors('Kein Gesundheitsblatt mit den Eingaben gefunden.')->withInput();
        }
    }

    public function update(Request $request, HealthForm $healthform)
    {
        $this->authorizeHealthFormAccess($healthform);
        $input = $request->all();
        if ($input['submit_btn'] == 'Gesundheitsblatt abschliessen') {
            $validator = Validator::make($request->all(), [
                'healthform.file_allergies' => 'mimes:pdf|max:2000',
                'healthinfo.accept_privacy_agreement' => 'required',
            ], [
                'healthform.file_allergies.max' => 'Die maximale Dateigrösse beträgt 2 MB.',
                'healthform.file_allergies.mimes' => 'Nur PDF-Dateien sind erlaubt.',
                'healthinfo.accept_privacy_agreement.required' => 'Für den Abschluss brauchen wir deine Bestätigung.', ]);
        } else {
            if ($request->hasFile('healthform.file_allergies')) {
                $validator = Validator::make($request->all(), [
                    'healthform.file_allergies' => 'mimes:pdf|max:2000',
                ], [
                    'healthform.file_allergies.max' => 'Die maximale Dateigrösse beträgt 2 MB.',
                    'healthform.file_allergies.mimes' => 'Nur PDF-Dateien sind erlaubt.', ]);
            } else {
                $validator = Validator::make($request->all(), []);
            }
        }

        if ($validator->fails()) {
            return redirect()->to(url(null, ['camp_code' => 1234])->previous())
                ->withErrors($validator)
                ->withInput();
        }
        $input_healthinfo = $input['healthinfo'];
        $input_healthform = $input['healthform'];
        $input_health_questions = [];
        if (isset($input['health_question'])) {
            $input_health_questions = $input['health_question'];
        }

        $camp = Camp::where('id', $healthform['camp_id'])->first();
        if (! $camp['demo'] && $file_allergies = $request->file('healthform.file_allergies')) {
            $save_path = 'app/files/'.$camp['code'].'/'.$healthform['code'];
            if (! file_exists(storage_path($save_path))) {
                mkdir(storage_path($save_path), 0755, true);
            }
            $name = 'Allergiepass.'.$file_allergies->getClientOriginalExtension();

            $file_allergies->move(storage_path($save_path), $name);
            $input_healthform['file_allergies'] = $save_path.'/'.$name;
        }

        $healthinfo = Helper::getHealthInfo($healthform['code']);

        $input_healthform['swimmer'] = isset($input_healthform['swimmer']);
        if ($input['submit_btn'] == 'Gesundheitsblatt abschliessen') {
            $healthform['finish'] = true;
            $healthform['date_finished'] = now();
            $message = 'Dein Gesundheitsblatt wurde übermittelt, vielen Dank.';
            $finish = true;
        } else {
            $finish = false;
            $message = 'Vielen Dank für die Eingaben. Dein Gesundheitsblatt wurde aktualisiert.';
        }
        $input_healthinfo['drugs_only_contact'] = isset($input_healthinfo['drugs_only_contact']);
        $input_healthinfo['ointment_only_contact'] = isset($input_healthinfo['ointment_only_contact']);
        $input_healthinfo['accept_privacy_agreement'] = isset($input_healthinfo['accept_privacy_agreement']);
        $healthform->update($input_healthform);
        $healthinfo->update($input_healthinfo);
        foreach ($input_health_questions as $key => $input_health_question) {
            HealthInformationQuestion::where('id', $key)->update(['answer' => $input_health_question]);
        }
        if (Auth::user()) {
            if (! $camp['konekta']) {
                return redirect()->route('healthforms.index');
            } else {
                return redirect()->route('healthinformation.show', $healthinfo);
            }
        } else {
            if ($finish) {
                return redirect()->route('healthform.show', $healthform);
            } else {
                return redirect()->to('/')->with('success', $message);
            }
        }
    }

    public function downloadAllergy(HealthForm $healthform)
    {
        $this->authorizeHealthFormAccess($healthform);

        abort_unless($healthform['file_allergies'] && file_exists(storage_path($healthform['file_allergies'])), 404);

        return response()->download(storage_path($healthform['file_allergies']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Health_Form  $health_Form
     * @return Response
     */
    public function destroy(Health_Form $health_Form)
    {
        //
    }

    public function searchResponseCity(Request $request)
    {
        // $query = $request->get('term','');
        $cities = City::search($request->get('term'))->get();

        return $cities;
    }

    public function searchResponseGroups(Request $request)
    {
        $groups = Group::on('mysql_info')->search($request->get('term'))->get();

        return $groups;
    }

    /**
     * Zugriff auf ein Gesundheitsblatt nur mit Lager- und persönlichem Code (Session)
     * oder als Lagerleitung des Lagers, zu dem das Blatt gehört.
     */
    private function authorizeHealthFormAccess(HealthForm $healthform): void
    {
        if (in_array($healthform->id, session(self::UNLOCKED_HEALTH_FORMS, []), true)) {
            return;
        }

        $user = Auth::user();
        if ($user && $user->isManager() && (int) $user->camp_id === (int) $healthform->camp_id) {
            return;
        }

        abort(403);
    }
}
