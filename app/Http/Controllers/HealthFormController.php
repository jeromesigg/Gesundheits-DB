<?php

namespace App\Http\Controllers;

use App\Exports\HealthFormsExport;
use App\Helper\Helper;
use App\Http\Requests\HealthFormUpdateRequest;
use App\Http\Requests\ImportHealthFormsRequest;
use App\Http\Requests\StoreHealthFormRequest;
use App\Imports\HealthFormsImport;
use App\Models\Camp;
use App\Models\City;
use App\Models\Group;
use App\Models\HealthForm;
use App\Models\HealthInformation;
use App\Models\HealthInformationQuestion;
use App\Models\Help;
use App\Policies\HealthFormPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yajra\DataTables\Facades\DataTables;

class HealthFormController extends Controller
{
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
    public function store(StoreHealthFormRequest $request)
    {
        $input = $request->validated();
        $code = Helper::generateUniqueCode();
        $camp = Auth::user()->camp;
        $input['code'] = Crypt::encryptString($code);
        $input['camp_id'] = $camp['id'];
        $healthform = HealthForm::create($input);
        $healthinfo = Helper::createHealthInformation($camp, $code);
        Helper::updateGroup($healthform, $input['group_text'] ?? null);
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

    public function uploadFile(ImportHealthFormsRequest $request)
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
                    $healthform = HealthForm::firstOrCreate(['ahv' => trim($importData_row['ahv_nr']), 'camp_id' => $camp['id']], $insertData);
                    if ($healthform->wasRecentlyCreated) {
                        Helper::createHealthInformation($camp, $code);
                    }
                }
            }
        }

        return redirect()->action('HealthFormController@index');

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
        return Excel::download(new HealthFormsExport(Auth::user()->camp), 'Teilnehmerliste.xlsx');
    }

    public function show(HealthForm $healthform)
    {
        $this->authorize('access', $healthform);
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
        $this->authorize('access', $healthform);
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
        $input = $request->all();
        if (! $request->filled(['camp_code', 'code'])) {
            return $this->showUnlockedForm($request);
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
            $request->session()->push(HealthFormPolicy::UNLOCKED_SESSION_KEY, $healthform->id);

            return $this->participantFormView($healthform, $camp);
        } else {
            return redirect()->to(url()->previous())
                ->withErrors('Kein Gesundheitsblatt mit den Eingaben gefunden.')->withInput();
        }
    }

    public function update(HealthFormUpdateRequest $request, HealthForm $healthform)
    {
        $finish = $request->input('submit_btn') === 'close';
        $input_healthform = Arr::only($request->input('healthform', []), [
            'nickname', 'first_name', 'last_name', 'street', 'zip_code', 'city', 'birthday', 'phone_number',
            'emergency_contact_name', 'emergency_contact_address', 'emergency_contact_phone',
            'doctor_contact', 'health_insurance_contact', 'accident_insurance_contact', 'vaccination',
        ]);
        $input_healthinfo = Arr::only($request->input('healthinfo', []), [
            'recent_issues', 'recent_issues_doctor', 'drug_longterm', 'drug_demand', 'drug_emergency', 'chronicle_diseases', 'allergy',
        ]);
        $input_health_questions = $request->input('health_question', []);

        $camp = Camp::where('id', $healthform['camp_id'])->first();
        if (! $camp['demo'] && $file_allergies = $request->file('healthform.file_allergies')) {
            $save_path = 'app/files/'.$camp['code'].'/'.$healthform['code'];
            if (! file_exists(storage_path($save_path))) {
                mkdir(storage_path($save_path), 0755, true);
            }
            $name = 'Allergiepass.pdf';

            $file_allergies->move(storage_path($save_path), $name);
            $input_healthform['file_allergies'] = $save_path.'/'.$name;
        }

        $healthinfo = Helper::getHealthInfo($healthform['code']);

        $input_healthform['swimmer'] = $request->has('healthform.swimmer');
        if ($finish) {
            $input_healthform['finish'] = true;
            $input_healthform['date_finished'] = now();
            $message = 'Dein Gesundheitsblatt wurde übermittelt, vielen Dank.';
        } else {
            $message = 'Vielen Dank für die Eingaben. Dein Gesundheitsblatt wurde aktualisiert.';
        }
        $input_healthinfo['drugs_only_contact'] = $request->has('healthinfo.drugs_only_contact');
        $input_healthinfo['ointment_only_contact'] = $request->has('healthinfo.ointment_only_contact');
        $input_healthinfo['accept_privacy_agreement'] = $request->has('healthinfo.accept_privacy_agreement');
        $healthform->update($input_healthform);
        $healthinfo->update($input_healthinfo);
        foreach ($input_health_questions as $key => $input_health_question) {
            HealthInformationQuestion::where('id', $key)
                ->where('health_information_id', $healthinfo->id)
                ->update(['answer' => $input_health_question]);
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
        $this->authorize('access', $healthform);

        $path = Helper::uploadedFilePath($healthform['file_allergies']);
        abort_if($path === null, 404);

        return response()->download($path);
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
     * GET /healthform?form=...: ein in dieser Session per Codes freigeschaltetes Blatt erneut anzeigen
     * (z. B. nach einem Validierungsfehler), solange das Lager läuft.
     */
    private function showUnlockedForm(Request $request)
    {
        $id = $request->query('form');
        if (! in_array($id, session(HealthFormPolicy::UNLOCKED_SESSION_KEY, []), true)) {
            return redirect('/');
        }
        $healthform = HealthForm::findOrFail($id);
        $camp = Camp::findOrFail($healthform->camp_id);
        if ($camp['finish']) {
            return redirect('/');
        }

        return $this->participantFormView($healthform, $camp);
    }

    /**
     * Formular für Teilnehmende; abgeschlossene Blätter nur lesend, wenn das Lager das so eingestellt hat.
     */
    private function participantFormView(HealthForm $healthform, Camp $camp)
    {
        $healthinfo = Helper::getHealthInfo($healthform['code']);
        $title = 'Hallo';
        $subtitle = ' '.$healthform['nickname'].' ('.$healthform['code'].')';
        $help = Help::where('title', $title)->first();
        if ($healthform['finish'] && $camp['forms_finished']) {
            return view('healthform.show', compact('healthform', 'healthinfo', 'title', 'subtitle', 'help'));
        }
        $health_questions = $healthinfo->questions;

        return view('healthform.edit', compact('healthform', 'healthinfo', 'health_questions', 'title', 'subtitle', 'help', 'camp'));
    }
}
