<?php

namespace App\Http\Controllers;

use App\Events\CampCreated;
use App\Helper\Helper;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\Help;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Validator;

class CampController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //

        $users = [];
        $title = 'Lager erstellen';
        $help = Help::where('title', $title)->first();
        $help['main_title'] = 'Lager';
        $help['main_route'] = '/dashboard/camps';

        return view('dashboard.camps.create', compact('users', 'title', 'help'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'unique:camps',
        ]);

        if ($validator->fails()) {
            return redirect()->to(url()->previous())
                ->withErrors($validator, 'camps')
                ->withInput();
        }
        if (! Auth::user()->demo) {

            $input = $request->all();

            $user = User::findOrFail(Auth::user()->id);
            $input['user_id'] = $user->id;
            $input['global_camp'] = false;
            $input['code'] = Helper::generateUniqueCampCode();
            $input['forms_finished'] = isset($input['closed_when_finished']);
            $input['closed_when_finished'] = isset($input['closed_when_finished']);
            $input['independent_form_fill'] = isset($input['independent_form_fill']);
            $input['show_names'] = isset($input['show_names']);
            $input['konekta'] = isset($input['konekta']);
            $camp = Camp::create($input);
            CampCreated::dispatch($camp);
            $user->update(['camp_id' => $camp->id, 'role_id' => config('status.role_Lagerleiter')]);
            CampUser::create([
                'user_id' => $user->id,
                'camp_id' => $camp->id,
                'role_id' => config('status.role_Lagerleiter'), ]);
        }

        return redirect('dashboard');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Camp $camp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Camp $camp)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Camp $camp)
    {
        //
        $this->authorize('switchTo', $camp);
        if (! Auth::user()->demo) {
            Helper::updateCamp(Auth::user(), $camp);
        }

        return redirect('/dashboard');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Camp $camp)
    {
        //
    }
}
