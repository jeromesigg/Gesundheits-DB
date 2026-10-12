@extends('layouts.layout')

@section('content')
<x-page-title :title="$title" :help="$help" />
<section>
    <div class="container-fluid">
        <!-- Page Header-->
        <div class="row">
            @if (!$camps)
            <div class="col-sm-3">
                <x-forms.form :action="route('camps.store')" accept-charset="UTF-8" method="POST">
                    <x-forms.container>
                        <x-forms.text label="Name:" name="name" required=true />
                    </x-forms.container>
                    <x-forms.container>
                        <x-forms.text label="Schlussdatum:" name="end_date" type="date" required=true />
                    </x-forms.container>
                    <x-forms.container>
                        <x-forms.checkbox label="Teilnehmer füllen selber Gesundheitsblatt aus"
                            name="independent_form_fill" required=true />
                    </x-forms.container>
                    <x-forms.container>
                        <x-forms.checkbox label="Keine Änderungen möglich nach Abschluss des Gesundheitsblattes"
                            name="closed_when_finished" required=true />
                    </x-forms.container>
                    <x-forms.container>
                        <x-forms.checkbox label="Die Namen der Teilnehmenden werden auch den Helfenden angezeigt"
                            name="show_names" required=true />
                    </x-forms.container>
                    <x-forms.container>
                        <x-forms.text label="Abteilung:" name="group_text" required=true
                            class="autocomplete_txt_group" />
                    </x-forms.container>
                    <x-forms.hidden name="group_id" class="autocomplete_txt" />
                    <x-forms.container>
                        <x-forms.button type="submit" class="btn btn-primary">
                            Lager erstellen
                        </x-forms.button>
                    </x-forms.container>
                </x-forms.form>
            </div>
            @endif
            <div class="col-sm-9">
                @if ($camps)
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Lagerleiter</th>
                            <th scope="col">Code</th>
                            <th scope="col">End-Datum</th>
                            <th scope="col">Abgeschlossen</th>
                            <th>Abschliessen?</th>
                        </tr>
                    </thead>
                    @foreach ($camps as $camp)
                    <tbody>
                        <tr>
                            <td><a href="{{route('dashboard.camps.edit',$camp->id)}}">{{$camp->name}}</a></td>
                            <td>{{$camp->user ? $camp->user['username'] : ''}}</a></td>
                            <td>{{$camp['code']}}</a></td>
                            <td>{{$camp->end_date > 1 ? date('d.m.Y', strtotime($camp->end_date)) : ''}}</a></td>
                            <td>{{$camp->finish ? 'Ja' : 'Nein'}}</td>
                            <td>
                                @if (!$camp->finish && Auth::user()->camp['id'] === $camp['id'])
                                <a href="{{route('dashboard.camps.export', $camp)}}"
                                    class="focus:outline-hidden text-white bg-success hover:bg-success-strong focus:ring-4 focus:ring-success-medium font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2"
                                    role="button">Excel exportieren</a>
                                <x-forms.form :action="route('dashboard.camps.destroy', $camp)" method="DELETE"
                                    :model="$camp" id="DeleteForm">
                                    <x-forms.button type="submit" name="submit-delete"
                                        class="confirm-finish focus:outline-hidden text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
                                        Lager abschliessen?
                                    </x-forms.button>
                                </x-forms.form>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                    @endforeach
                </table>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="module">
    window.addEventListener("load", function() {
           
            $('.confirm-finish').on('click', function(e){
                e.preventDefault(); //cancel default action

                Swal.fire({
                    title: "{{$title_modal}}",
                    text: "{{$text_modal}}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ja',
                    cancelButtonText: 'Abbrechen',
                    confirmButtonColor: 'blue',
                    cancelButtonColor: 'red',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById("DeleteForm").submit();
                    }
                });
            });

        });
</script>
@endpush