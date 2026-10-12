<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Events\CampCreated;
use App\Exports\CampExport;
use App\Helper\Helper;
use App\Http\Requests\UpdateCampRequest;
use App\Models\Camp;
use App\Models\Help;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminCampController extends Controller
{
    //
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $aktUser = Auth::user();
        if (! $aktUser) {
            return redirect('/dashboard');
        }
        if (! $aktUser->isAdmin()) {
            if (isset($aktUser->camp)) {
                $camps = [$aktUser->camp];
            } else {
                $camps = null;
            }
        } else {
            $camps = Camp::all();
        }
        $title = 'Lagerübersicht';
        $help = Help::where('title', $title)->first();

        $title_modal = 'Lager löschen?';
        $text_modal = 'Beim Lager löschen werden alle Interventionen und hochgeladenen Dokumente gelöscht.';

        return view('dashboard.camps.index', compact('camps', 'title', 'help', 'title_modal', 'text_modal'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        if (! Auth::user()->demo) {
            $user = User::findOrFail(Auth::user()->id);

            if (! $user->isAdmin()) {
                $input['user_id'] = $user->id;
            }
            $input['forms_finished'] = isset($input['closed_when_finished']);
            $input['closed_when_finished'] = isset($input['closed_when_finished']);
            $input['show_names'] = isset($input['show_names']);
            $input['konekta'] = isset($input['konekta']);
            $camp = Camp::create($input);
            Helper::updateGroup($camp, $input['group_text']);
            CampCreated::dispatch($camp);
            if (! $user->isAdmin()) {
                $user->update(['camp_id' => $camp->id]);
            }
        }

        return redirect('dashboard/camps');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Camp $camp)
    {
        $this->authorize('manage', $camp);
        $users = $camp->allUsers()->wherePivot('role_id', UserRole::CampLeader->value)->pluck('username', 'users.id')->all();

        $title = 'Lager aktualisieren';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Lager';
        $help['main_route'] = '/dashboard/camps';

        $title_modal = 'Lager löschen?';
        $text_modal = 'Beim Lager löschen werden alle Interventionen und hochgeladenen Dokumente gelöscht.';

        return view('dashboard.camps.edit', compact('camp', 'users', 'title', 'help', 'title_modal', 'text_modal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateCampRequest $request, Camp $camp)
    {
        if (! Auth::user()->demo) {
            $camp->update([
                'name' => $request->validated('name'),
                'end_date' => $request->validated('end_date'),
                'independent_form_fill' => $request->filled('independent_form_fill'),
                'forms_finished' => $request->filled('closed_when_finished'),
                'closed_when_finished' => $request->filled('closed_when_finished'),
                'show_names' => $request->filled('show_names'),
                'group_text' => $request->validated('group_text'),
            ]);
            if ($request->filled('user_id')) {
                $camp->update(['user_id' => $request->validated('user_id')]);
            }
            Helper::updateGroup($camp, $request->validated('group_text'));
        }

        return redirect('/dashboard/camps');
    }

    /**
     * Teilnehmende und Interventionen eines laufenden Lagers als Excel (zwei Tabellenblätter).
     */
    public function export(Camp $camp): BinaryFileResponse
    {
        $this->authorize('manage', $camp);
        abort_if($camp->finish, 404);

        return Excel::download(new CampExport($camp), 'Lagerexport.xlsx');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Camp $camp)
    {
        $this->authorize('manage', $camp);
        if (! Auth::user()->demo) {
            $camp_global = Camp::where('global_camp', true)->first();

            foreach (User::where('camp_id', $camp->id)->get() as $user) {
                Helper::updateCamp($user, $camp_global);
            }
            foreach ($camp->camp_users_all()->get() as $camp_user) {
                $camp_user->delete();
            }
            $counter = $camp->health_infos()->count();
            foreach ($camp->health_infos()->get() as $health_info) {
                $health_info->delete();
            }
            foreach ($camp->health_forms()->get() as $health_form) {
                $health_form->delete();
            }

            File::deleteDirectory(storage_path('app/public/files/'.$camp['code']));
            File::deleteDirectory(storage_path('app/files/'.$camp['code']));
            $camp->update(['finish' => true, 'counter' => $counter]);
        }

        return redirect('/dashboard');
    }
}
