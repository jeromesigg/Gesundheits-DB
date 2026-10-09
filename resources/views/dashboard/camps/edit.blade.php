@extends('layouts.layout')

@section('content')
    <x-page-title :title="$title" :help="$help"/>
    <section>
        <div class="container-fluid">
            <!-- Page Header-->
            <header>
                <h1 class="h3 display">Lager</h1>
            </header>
            <x-forms.form :action="route('dashboard.camps.update', $camp->id)" accept-charset="UTF-8" method="PATCH" :model="$camp">
                <x-forms.container>
                    <x-forms.text label="Name:" name="name" required=true/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.select label="Lagerleiter:" name="user_id" required=true :collection="$users"/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.text label="Schlussdatum:" name="end_date" type="date" required=true/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.checkbox label="Teilnehmer füllen selber Gesundheitsblatt aus" name="independent_form_fill" class="healthform__checkbox" value="{{$camp['independent_form_fill']}}"/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.checkbox label="Bei Abschluss des Gesundheitsblattes sind keine Änderungen mehr möglich" name="closed_when_finished" class="healthform__checkbox" value="{{$camp['closed_when_finished']}}"/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.checkbox label="Die Namen der Teilnehmenden werden auch den Helfenden angezeigt" name="show_names" class="healthform__checkbox" value="{{$camp['show_names']}}"/>
                </x-forms.container>
                <x-forms.container>
                    <x-forms.text label="Abteilung:" name="group_text" required=true class="autocomplete_txt_group"/>
                </x-forms.container>
                <x-forms.hidden name="group_id" required=true class="autocomplete_txt_group"/>
                <x-forms.container>
                    <x-forms.button type="submit" class="btn btn-primary">
                        Lager aktualisieren
                    </x-forms.button>
                </x-forms.container> 
            </x-forms.form>
            <x-forms.form :action="route('dashboard.camps.destroy', $camp)" method="DELETE" :model="$camp" id="DeleteForm">
                <x-forms.button type="submit" name="submit-delete" class="confirm-finish focus:outline-hidden text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
                    Lager abschliessen?
                </x-forms.button>
            </x-forms.form>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module">
        window.addEventListener("load", function() {
            $(document).on('focus','.autocomplete_txt_group',function(){
                var type = $(this).attr('name');

                var autoType='name';
                if(type =='group_id')autoType='id';

                $(this).autocomplete({
                    minLength: 3,
                    highlight: true,
                    source: function( request, response ) {
                        $.ajax({
                            url: "{{ route('searchajaxgroups') }}",
                            dataType: "json",
                            data: {
                                term : request.term,
                                type : type,
                            },
                            success: function(data) {
                                var array = $.map(data, function (item) {
                                    return {
                                        label: item['short_name'] + ' ' + item['name'],
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
                        $("[name='group_text']").val(data.name);
                        $("[name='group_id']").val(data.id);
                    }
                });
            });
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
