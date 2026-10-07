<?php

namespace App\Http\Controllers;

use App\Events\CampCreated;
use App\Helper\Helper;
use App\Models\Camp;
use App\Models\Help;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

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
        //
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
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Camp $camp)
    {
        //
        $users = User::where('role_id', config('status.role_Lagerleiter'))->pluck('username', 'id')->all();

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
    public function update(Request $request, Camp $camp)
    {
        if (! Auth::user()->demo) {
            $input = $request->all();
            $input['independent_form_fill'] = isset($input['independent_form_fill']);
            $input['forms_finished'] = isset($input['closed_when_finished']);
            $input['closed_when_finished'] = isset($input['closed_when_finished']);
            $input['show_names'] = isset($input['show_names']);
            $input['konekta'] = isset($input['konekta']);
            $camp->update($input);
            Helper::updateGroup($camp, $input['group_text']);
        }

        return redirect('/dashboard/camps');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Camp $camp)
    {
        if (! Auth::user()->demo) {
            $users = Auth::user()->camp->allUsers;
            $camp_global = Camp::where('global_camp', true)->first();

            foreach ($users as $user) {
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
