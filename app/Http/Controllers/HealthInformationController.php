<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Camp;
use App\Models\HealthInformation;
use App\Models\Help;
use App\Models\Intervention;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Validator;
use Yajra\DataTables\Facades\DataTables;

class HealthInformationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
        $title = 'Teilnehmerübersicht';
        $help = Help::where('title', $title)->first();

        return view('dashboard.healthinformation.index', compact('title', 'help'));
    }

    public function createDataTables()
    {
        //
        $camp = Auth::user()->camp;
        $healthinfo = $camp->health_infos()->get();

        return DataTables::of($healthinfo)
            ->addColumn('code', function (HealthInformation $act_healthinfo) {
                $code = $act_healthinfo['code'].Helper::getName($act_healthinfo);

                return '<a href='.\URL::route('healthinformation.show', $act_healthinfo).'>'.$code.'</a>';
            })
            ->addColumn('status', function (HealthInformation $act_healthinfo) {
                $interventions_open = $act_healthinfo->interventions_open->sortByDesc('health_status_id')->first();
                $status = 'Keine offene Intervention';
                if (isset($interventions_open)) {
                    $status = Helper::getHealthStatus($interventions_open);
                }

                return $status;
            })
            ->addColumn('interventions', function (HealthInformation $act_healthinfo) {
                return count($act_healthinfo->interventions->whereNull('intervention_master_id'));
            })
            ->addColumn('interventions_open', function (HealthInformation $act_healthinfo) {
                return count($act_healthinfo->interventions_open);
            })
            ->rawColumns(['code', 'status'])
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
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  HealthInformation  $healthInformation
     * @return Response
     */
    public function show(HealthInformation $healthinformation)
    {
        $this->authorizeCamp($healthinformation->camp_id);
        $intervention = new Intervention([
            'health_information_id' => $healthinformation['id'],
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->format('H:i'),
        ]);

        return Helper::getHealthInformationShow($intervention, $healthinformation);
    }

    public function search(Request $request)
    {
        //
        $input = $request->all();
        $healthinfo = null;
        $camp = Auth::user()->camp;
        if ($input['code']) {
            $healthinfo = HealthInformation::where('code', '=', $input['code'])->where('camp_id', '=', $camp['id'])->first();
        }
        if ($healthinfo == null) {
            return redirect()->to(url()->previous())
                ->withErrors('Es konnte kein Teilnehmer mit diesen Angaben gefunden werden.')
                ->withInput();
        }

        return redirect()->route('healthinformation.show', [$healthinfo]);

    }

    public function uploadProtocol(Request $request, HealthInformation $healthinformation)
    {
        $this->authorizeCamp($healthinformation->camp_id);
        $validator = Validator::make($request->all(), [
            'file_protocol' => 'mimes:pdf|max:2000',
        ], [
            'file_protocol.max' => 'Die maximale Dateigrösse beträgt 2 MB.',
            'file_protocol.mimes' => 'Nur PDF-Dateien sind erlaubt.', ]);

        if ($validator->fails()) {
            return redirect()->to(url()->previous())
                ->withErrors($validator)
                ->withInput();
        }
        $camp = Camp::where('id', $healthinformation['camp_id'])->first();
        if (! $camp['demo'] && $file_protocol = $request->file('file_protocol')) {
            $save_path = 'app/files/'.$camp['code'].'/'.$healthinformation['code'];
            if (! file_exists(storage_path($save_path))) {
                mkdir(storage_path($save_path), 0755, true);
            }
            $name = 'Notfallblatt.'.$file_protocol->getClientOriginalExtension();

            $file_protocol->move(storage_path($save_path), $name);
            $input_healthform['file_protocol'] = $save_path.'/'.$name;
            $healthinformation->update(['file_protocol' => $input_healthform['file_protocol']]);
        }

        return redirect()->route('healthinformation.show', [$healthinformation]);
    }

    public function downloadProtocol(HealthInformation $healthinformation)
    {
        $this->authorizeCamp($healthinformation->camp_id);

        return response()->download(storage_path($healthinformation['file_protocol']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  HealthInformation  $healthInformation
     * @return Response
     */
    public function edit(HealthInformation $healthinformation) {}

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, HealthInformation $healthInformation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function print(HealthInformation $healthInformation)
    {
        $this->authorizeCamp($healthInformation->camp_id);
        $healthform = Helper::getHealthForm($healthInformation['code']);

        return view('dashboard.healthinformation.print', compact('healthform', 'healthInformation'));
    }

    public function searchResponseCode(Request $request)
    {
        $camp = Auth::user()->camp;

        return HealthInformation::search($request->get('term'))
            ->get()
            ->where('camp_id', $camp['id'])
            ->map(fn (HealthInformation $healthInformation) => ['code' => $healthInformation->code])
            ->values();
    }
}
