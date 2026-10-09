@extends('layouts.layout')

@section('content')
    <x-page-title :title="$title" :help="$help"/>
    @if (Session::has('deleted_healthform'))
        <p class="bg-danger">{{session('deleted_healthform')}}</p>
    @endif
    <section>
        <div class="container-fluid">
            <!-- Page Header-->
            <div class="row">
                <div class="col-lg-4">
                    <a href="{{route('healthforms.create')}}" class="btn btn-primary" role="button">Gesundsheitsblatt erstellen</a>
                </div>
                <div class="col-lg-4">
                    @if (Auth::user()->isManager())
                        <a href="files/vorlage.xlsx" target="blank" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Vorlage herunterladen</a>
                        <x-forms.form :action="route('dashboard.healthforms.uploadFile')" accept-charset="UTF-8" method="POST" enctype="multipart/form-data">
                            <x-forms.container>
                                <x-forms.file name="file" required=true/>
                            </x-forms.container>
                            <x-forms.container>
                                <x-forms.button type="submit" class="btn btn-primary">
                                    Teilnehmerliste hochladen
                                </x-forms.button>
                            </x-forms.container> 
                        </x-forms.form>
                        <br>
                    @endif
                    <a href="{{route('healthforms.downloadFile')}}" class="btn btn-primary" role="button">Gesundsheitsblätter herunterladen</a>
                </div>
            </div>
            <br>
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
            <div class="row">
                <div class="col-lg-4">
                    <a href="{{route('healthforms.create')}}" class="btn btn-primary" role="button">Gesundsheitsblatt erstellen</a>
                </div>
            </div>
        </div>
    </section>
@endsection
@push('scripts')
<script type="module">
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
