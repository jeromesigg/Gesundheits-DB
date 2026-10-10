@extends('layouts.layout')

@section('page')
    <x-page-title :title="$title" :help="$help" :subtitle="$subtitle"/>
        <x-forms.form :action="route('dashboard.update', $aktUser)" accept-charset="UTF-8" method="PATCH" :model="$aktUser">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <x-forms.container>
                <x-forms.text label="Name:" name="username" required=true readonly="true"/>
            </x-forms.container>
                <x-forms.container>
                <x-forms.text label="E-Mail:" name="email" type="email" required=true placeholder="name@abt.ch" autocomplete="email"/>
            </x-forms.container>
            <x-forms.container>
                <x-forms.text label="Passwort:" name="password" required=true type="password" autocomplete="new-password"/>
            </x-forms.container>
            <x-forms.container>
                <x-forms.text label="Passwort bestätigen:" name="password_confirmation" required=true type="password" autocomplete="new-password"/>
            </x-forms.container>
            <x-forms.container>
                <x-forms.button type="submit" class="btn btn-primary">
                    Benutzer aktualisieren
                </x-forms.button>
            </x-forms.container> 
        </x-forms.form>
@endsection
