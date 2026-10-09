<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Help;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UsersController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(User $user)
    {
        $aktUser = Auth::user();
        if ($aktUser && ($aktUser->id == $user->id)) {
            $title = 'Profil';
            $subtitle = 'von '.$aktUser['username'];
            $help = Help::where('title', $title)->first();

            return view('dashboard.user', compact('aktUser', 'title', 'subtitle', 'help'));
        }

        return redirect('/dashboard');
    }

    public function update(ProfileUpdateRequest $request, User $user)
    {
        $user->update(['email' => $request->validated('email')]);
        if (filled($request->validated('password'))) {
            $user->update(['password' => bcrypt($request->validated('password'))]);
            Session::flash('message', 'Passwort erfolgreich verändert!');
        }

        return redirect('/dashboard');
    }
}
