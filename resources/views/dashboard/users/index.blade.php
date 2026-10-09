@extends('layouts.layout')

@section('page')
    <x-page-title :title="$title" :help="$help"/>
    @if (Session::has('deleted_user'))
        <p class="bg-danger">{{session('deleted_user')}}</p>
    @endif
    <section>
        <div class="container-fluid">
            <!-- Page Header-->
            <header>
                <h1 class="h3 display">Personen</h1>
            </header>
            <div class="row">
                <div class="col-lg-3">
                    <p>Person Suchen:</p>                    
                    <x-forms.form :action="route('users.add')" accept-charset="UTF-8" method="POST">
                        <x-forms.container>
                            <x-forms.text label="Name:" name="username_add" required=true class="autocomplete_txt" placeholder="name@abt.ch"/>
                        </x-forms.container>
                        <x-forms.container>
                            <x-forms.select label="Rolle:" name="role_id_add" required=true :collection="$roles"/>
                        </x-forms.container>
                        <x-forms.hidden name="user_id" class="autocomplete_txt"/>
                        <x-forms.container>
                            <x-forms.button type="submit" class="btn btn-primary">
                                Person Hinzufügen
                            </x-forms.button>
                        </x-forms.container> 
                    </x-forms.form>
                </div>
                <div class="col-lg-3">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <p>Person Erstellen:</p>
                    <x-forms.form :action="route('dashboard.users.store')" accept-charset="UTF-8" method="POST">
                        <x-forms.container>
                            <x-forms.text label="Name:" name="username" required=true placeholder="name@abt.ch" autocomplete="username"/>
                        </x-forms.container>
                        <x-forms.container>
                            <x-forms.text label="E-Mail:" name="email" type="email" required=true placeholder="name@abt.ch" autocomplete="email"/>
                        </x-forms.container>
                        <x-forms.container>
                            <x-forms.select label="Rolle:" name="role_id" required=true :collection="$roles"/>
                        </x-forms.container>
                        <x-forms.container>
                            <x-forms.text label="Passwort:" name="password" required=true type="password" autocomplete="new-password"/>
                        </x-forms.container>
                        <x-forms.container>
                            <x-forms.text label="Passwort bestätigen:" name="password_confirmation" required=true type="password" autocomplete="new-password"/>
                        </x-forms.container>
                        <x-forms.hidden name="user_id" class="autocomplete_txt"/>
                        <x-forms.container>
                            <x-forms.button type="submit" class="btn btn-primary">
                                Person Erstellen
                            </x-forms.button>
                        </x-forms.container> 
                    </x-forms.form>
                    
                </div>
    {{--                <div class="col-lg-4">--}}
    {{--                    {!! Html::link('files/vorlage.xlsx', 'Vorlage herunterladen') !!}--}}
    {{--                    {!! Form::open(['action' => 'AdminUsersController@uploadFile', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}--}}
    {{--                    <div class="form-group">--}}
    {{--                        {{ Form::file('csv_file',['class' => 'dropify'])}}--}}
    {{--                    </div>--}}
    {{--                    {{ Form::submit('Teilnehmerliste hochladen', ['class' => 'btn btn-primary']) }}--}}
    {{--                    {!! Form::close() !!}--}}
    {{--                </div>--}}

                <div class="col-lg-6">
                    <table class="table table-striped table-bordered hover" style="width:100%" id="datatable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Rolle</th>
                                <th scope="col">Letztes Login</th>
                                <th scope="col">Aktiv</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script type="module">
    $(document).ready(function(){
        $('#datatable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            buttons: [],
            language: {
            "url": "/lang/Datatables.json"
            },
            ajax: "{!! route('users.CreateDataTables') !!}",
            columns: [
                {data: 'username', name: 'username' },
                {
                    data: {
                        _: 'role.display',
                        sort: 'role.sort'
                    },
                    name: 'role',
                },
                {data: 'last_login_at', name: 'last_login_at'},
                {data: 'active', name: 'active' },
                {data: 'Actions', name: 'Actions', orderable:false,serachable:false,sClass:'text-center'},
            ]
            });
        });
        $(document).on('focus','.autocomplete_txt',function(){
            var autoType = $(this).attr('name');
            var type='username';

            $(this).autocomplete({
                minLength: 3,
                highlight: true,
                source: function( request, response ) {
                    $.ajax({
                        url: "{{ route('searchajaxuser') }}",
                        dataType: "json",
                        data: {
                            term : request.term,
                            type : type,
                        },
                        success: function(data) {
                            var array = $.map(data, function (item) {
                                return {
                                    label: item['username'] + ' - ' +  item['email'],
                                    value: item[autoType],
                                    data : item
                                }
                            });
                            response(array)
                        }
                    });
                },
                select: function( event, ui ) {
                    var data = ui.item.data;
                    $("[name='username']").val(data.username);
                    $("[name='user_id']").val(data.id);
                }
            });
    });
    $('#datatable').on('click', '.link--delete[data-remote]', function (e) {
        e.preventDefault();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        var url = $(this).data('remote');
        // confirm then
        $.ajax({
            url: url,
            type: 'DELETE',
            dataType: 'json',
            data: {method: 'POST', submit: true}
        }).always(function (data) {
            $('#datatable').DataTable().draw(false);
        });
    });
    </script>
@endpush
