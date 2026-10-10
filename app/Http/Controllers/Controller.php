<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Daten eines Lagers sind nur im aktuell gewählten Lager des Users zugänglich (auch für Admins).
     */
    protected function authorizeCamp(?int $campId): void
    {
        abort_unless($campId !== null && $campId === (int) Auth::user()?->camp_id, 403);
    }
}
