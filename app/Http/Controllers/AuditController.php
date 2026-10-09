<?php

namespace App\Http\Controllers;

use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    //
    public function index()
    {
        $audits = Audit::with('user')
            ->orderBy('created_at', 'desc')->get();

        return view('dashboard.audits', compact('audits'));
    }
}
