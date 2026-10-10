@extends('layouts.app')

@section('content')
<div class="container">
    <x-page-title :title="$title" :help="$help" :header="false" :subtitle="$subtitle"/>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="justify-content-center">
        <div class="card">
            @if(!Auth::user()->camp['global_camp'] && Auth::user()->isHelper())
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6">
                            <x-forms.form :action="route('healthinformation.search')" accept-charset="UTF-8" method="GET">
                                <x-forms.container>
                                    <x-forms.text label="Persönliche Nummer:" name="code" required=true class="autocomplete_txt"/>
                                </x-forms.container>
                                <x-forms.hidden name="healthinformation_id" class="autocomplete_txt"/>
                                <x-forms.container>
                                    <x-forms.button type="submit" class="btn btn-primary">
                                        Patientenakte öffnen
                                    </x-forms.button>
                                </x-forms.container> 
                            </x-forms.form>

                        </div>
                        <div class="col-lg-6">
                            @if(!$camp['independent_form_fill'] && Auth::user()->isManager())
                                <a href="{{route('healthforms.create')}}" class="btn btn-primary" role="button">Gesundsheitsblatt erstellen</a>
                                <br>
                                <br>
                            @endif
                            <a href="files/Notfallblatt.pdf" target="blank" class="btn btn-primary">J+S-Notfallblatt herunterladen</a>
                        </div>
                    </div>
                </div>
                
                @if($camp['konekta'])
                    <hr class="h-px my-8 bg-neutral-quaternary border-0">
                    <div >
                        <table class="table table-striped table-bordered" style="width:100%" id="datatable">
                            <thead>
                                <tr>
                                    <th scope="col">Code</th>
                                    <th scope="col">Ceviname</th>
                                    <th scope="col">Vorname</th>
                                    <th scope="col">Nachname</th>
                                    <th scope="col" >Geburtsdatum</th>
                                    <th scope="col">Abteilung</th>
                                    <th scope="col">Ort</th>
                                    <th scope="col">Ausgefüllt</th>
                                    <th scope="col">Öffnen</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endif
            @elseif(!Auth::user()->camp['global_camp'])
                <div class="container p-4">
                    <h4><b>Lager "{{ $camp['name'] }}"</b></h4>
                    <p>Du bist in diesem Lager als Teilnehmer*in eingetragen und hast keinen Zugriff auf Gesundheitsdaten.</p>
                </div>
            @else
                <div class="container p-4">
                    <h4><b>Kein Lager zugewiesen</b></h4>
                    <div class="row">
                        <div class="col-lg-12">
                        Du kannst <a  href="{{ route('camps.create') }}" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">hier</a> ein Lager erstellen.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection



@push('scripts')
    <script type="module">

        //autocomplete script
        $(document).on('focus','.autocomplete_txt',function(){
            $(this).autocomplete({
                minLength: 2,
                highlight: true,
                source: function( request, response ) {
                    $.ajax({
                        url: "{{ route('searchajaxcode') }}",
                        dataType: "json",
                        data: {
                            term : request.term,
                        },
                        success: function(data) {
                            var array = $.map(data, function (item) {
                                return {
                                    label: item['code'],
                                    data : item
                                }
                            });
                            response(array)
                        }
                    });
                },
                select: function( event, ui ) {
                    var data = ui.item.data;
                    $("[name='code']").val(data.code);
                    $("[name='healthinformation_id']").val(data.id);
                }
            });
        });
        $(document).ready(function(){
            const table = $('#datatable').DataTable({
                responsive: true,
                processing: true,
                serverSide: true,
                pageLength: 25,
                language: {
                    "url": "/lang/Datatables.json"
                },
                ajax: "{!! route('healthforms.CreateDataTables') !!}",
                columns: [
                    { data: 'code', name: 'code' },
                    { data: 'nickname', name: 'nickname' },
                    { data: 'first_name', name: 'first_name' },
                    { data: 'last_name', name: 'last_name' },
                    { data: 'birthday', name: 'birthday' },
                    { data: 'group', name: 'group' },
                    { data: 'city', name: 'city' },
                    { data: 'finish', name: 'finish' },
                    { data: 'Actions', name: 'Actions', orderable: false, searchable: false }
                ]
            });
            
            initFlowbite();
        });
    </script>
@endpush
