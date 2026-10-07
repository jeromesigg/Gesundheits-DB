<?php

namespace App\Http\Controllers;

use App\Models\Help;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $akt_User = Auth::user();
        $camp = $akt_User->camp;
        $title = 'Dashboard';
        $subtitle = $camp['global_camp'] ? '' : ' für Lager "'.$camp['name'].'" ('.$camp['code'].')';
        $help = Help::where('title', $title)->first();

        return view('dashboard', compact('camp', 'title', 'help', 'subtitle'));
    }

    public function home()
    {
        $title = 'Gesundheitsblatt ausfüllen';
        $help = Help::where('title', $title)->first();

        return view('home', compact('title', 'help'));
    }
}
