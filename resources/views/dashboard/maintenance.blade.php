@extends('layouts.layout')
@section('content')
    <div class="breadcrumb-holder">
        <div class="container-fluid">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Wartung</li>
            </ul>
        </div>
    </div>
    <section>
        <div class="container-fluid">
            <header>
                <h1 class="h3 display">Wartung</h1>
            </header>
            <div class="row">
                <div class="col-lg-6">
                    <h4>Offene Migrationen</h4>
                    @if (count($pendingMigrations) === 0)
                        <p>Keine offenen Migrationen.</p>
                    @else
                        <ul>
                            @foreach ($pendingMigrations as $migration)
                                <li><code>{{ $migration }}</code></li>
                            @endforeach
                        </ul>
                    @endif
                    <x-forms.form :action="route('admin.maintenance.migrate')" method="POST">
                        <x-forms.button type="submit" class="btn btn-primary">Migrationen ausführen</x-forms.button>
                    </x-forms.form>
                    <br>
                    <h4>Caches</h4>
                    <p>Nach einem Deployment: Konfiguration, Routen und Views neu laden.</p>
                    <x-forms.form :action="route('admin.maintenance.clearCache')" method="POST">
                        <x-forms.button type="submit" class="btn btn-primary">Caches leeren</x-forms.button>
                    </x-forms.form>
                </div>
                <div class="col-lg-6">
                    @if ($output !== null)
                        <h4>Ausgabe</h4>
                        <pre class="p-2 rounded-lg font-mono text-sm overflow-x-auto bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white">{{ $output }}</pre>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
