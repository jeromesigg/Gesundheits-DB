<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/**
 * Wartung ohne SSH-Zugang: Migrationen ausführen und Caches leeren, nur für Admins und nur per POST.
 */
class MaintenanceController extends Controller
{
    public function index(): View
    {
        $migrator = app('migrator');
        $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
        $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];
        $pendingMigrations = array_values(array_diff($files, $ran));

        return view('dashboard.maintenance', [
            'title' => 'Wartung',
            'pendingMigrations' => $pendingMigrations,
            'output' => session('maintenance_output'),
        ]);
    }

    public function migrate(): RedirectResponse
    {
        Artisan::call('migrate', ['--force' => true]);

        return $this->backWithOutput();
    }

    public function clearCache(): RedirectResponse
    {
        Artisan::call('optimize:clear');

        return $this->backWithOutput();
    }

    private function backWithOutput(): RedirectResponse
    {
        return redirect()->route('admin.maintenance')->with('maintenance_output', Artisan::output());
    }
}
