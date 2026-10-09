@extends('layouts.layout')

@section('content')
    <x-page-title :title="$title" :help="$help" :subtitle="$subtitle"/>
    <section>
        <div class="container-fluid">
            <!-- Page Header-->
            <div class="row">
                <div class="col-md-10">
                    <h3>{{isset($intervention['id']) ? 'Intervention Nr. ' . $intervention->number() . ' aktualisieren' : 'Intervention erstellen'}}</h3>
                    
                    <x-forms.form :action="route('interventions.store')" accept-charset="UTF-8" method="POST" :model="$intervention" fullWidth=true files=true>
                        <x-forms.row>
                            <x-forms.container class="col-xl-2 col-lg-12">
                                <x-forms.hidden name="health_information_id" :value="$intervention['health_information_id']"/>
                                <x-forms.hidden name="intervention_id" :value="$intervention['id']"/>
                                @if(!isset($intervention['id']) || isset($intervention['intervention_master_id']))
                                    <x-forms.select label="Übergeordnete Intervention:" name="intervention_master_id" :collection="$intervention_masters"/>
                                @endif
                                <br>
                                <x-forms.row>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text label="Datum:" name="date" type="date" required=true/>
                                    </x-forms.container>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text label="Zeit:" name="time" type="time" required=true/>
                                    </x-forms.container>
                                </x-forms.row>
                                <br>
                                <x-forms.text label="Erfasser:" name="user_erf" required=true/>
                                <br>
                                <x-forms.select label="Dringlichkeit:" name="health_status_id" required=true :collection="$health_status"/>
                            </x-forms.container>
                            <x-forms.container class="col-xl-8 col-lg-12">

                                <x-forms.row>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text-area label="Parameter / Symptom:" name="parameter" required=true rows=3 id="parameter_value"/>
                                    </x-forms.container>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text-area label="Wert:" name="value" rows=3/>
                                    </x-forms.container>
                                </x-forms.row>
                                <x-forms.row>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text-area label="Intervention / Medikation:" name="medication" rows=4/>
                                    </x-forms.container>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text-area label="Bemerkung:" name="comment" rows=4/>
                                    </x-forms.container>
                                </x-forms.row>
                            </x-forms.container>
                            <x-forms.container class="col-xl-2 col-lg-12">
                                <a href="#" class="intervention_image"> <img src="/img/xabcde.jpg" alt="" id="intervention_file" width="40%"></a>
                                <x-forms.container id="intervention_picture">
                                    <x-forms.file label="Bild:" name="file" accept="image/*" capture="camera"/>
                                </x-forms.container>
                            </x-forms.container>
                        </x-forms.row>
                        @foreach($interventions as $key => $intervention_sub)
                            <hr class="h-0.5 mx-auto my-4 bg-gray-300 border-0 rounded-sm md:my-10 dark:bg-gray-700">
                            <p>Intervention Nr. {{$intervention_sub->number()}}</p>
                            <x-forms.row class="ml-10">
                                <x-forms.container class="col-xl-2 col-lg-12">
                                    <x-forms.hidden name="intervention_sub['.$key.'][health_information_id]" :value="$intervention_sub['health_information_id']"/>
                                    <x-forms.hidden name="intervention_sub['.$key.'][intervention_id]" :value=" $intervention_sub['id']"/>
                                    <br>
                                    <x-forms.row>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text label="Datum:" name="intervention_sub['.$key.'][date]" type="date" required=true/>
                                    </x-forms.container>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text label="Zeit:" name="intervention_sub['.$key.'][time]" type="time" required=true/>
                                    </x-forms.container>                                    
                                    </x-forms.row>
                                    <br>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.text label="ZErfasser:" name="intervention_sub['.$key.'][user_erf]" required=true/>
                                    </x-forms.container>          
                                    <br>
                                    <x-forms.container class="col-xl-6 col-lg-12">
                                        <x-forms.select label="Dringlichkeit:" name="intervention_sub['.$key.'][health_status_id]" :collection="$health_status" required=true/>
                                    </x-forms.container>          
                                </x-forms.container>
                                <x-forms.container class="col-xl-8 col-lg-12">
                                    <x-forms.row>
                                        <x-forms.container class="col-xl-6 col-lg-12">
                                            <x-forms.text-area label="Parameter / Symptom:" name="intervention_sub['.$key.'][parameter]" required=true rows=3 id="parameter_value"/>
                                        </x-forms.container>
                                        <x-forms.container class="col-xl-6 col-lg-12">
                                            <x-forms.text-area label="Wert:" name="intervention_sub['.$key.'][value]" rows=3/>
                                        </x-forms.container>
                                    </x-forms.row>
                                    <x-forms.row>
                                        <x-forms.container class="col-xl-6 col-lg-12">
                                            <x-forms.text-area label="Intervention / Medikation:" name="intervention_sub['.$key.'][medication]" rows=4/>
                                        </x-forms.container>
                                        <x-forms.container class="col-xl-6 col-lg-12">
                                            <x-forms.text-area label="Bemerkung:" name="intervention_sub['.$key.'][comment]" rows=4/>
                                        </x-forms.container>
                                    </x-forms.row>
                                </x-forms.container>
                                <x-forms.container class="col-xl-2 col-lg-12">
                                    <x-forms.container class="col-xl-6 col-lg-12" id="intervention_picture">
                                    <x-forms.file label="Bild:" name="intervention_sub['.$key.'][file]" accept="image/*" capture="camera"/>
                                    </x-forms.container>
                                </x-forms.container>
                            </x-forms.row>
                        @endforeach
                        <div id="container_new_interventions">
                        </div>
                        <x-intervention-close :close="$intervention_close"/>
                        
                        <div class="form-group">
                        
                            <x-forms.button type="submit" class="btn text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-hidden dark:focus:ring-blue-800" id="submit_btn">
                                @isset($intervention['id']) {{ __('Intervention aktualisieren') }} @else {{ __('Intervention speichern') }} @endisset
                            </x-forms.button>
                            @if(!isset($intervention['intervention_master_id']))
                                <a href="#" class="btn focus:outline-hidden text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900" role="button" id="addIntervention">Untergeordnete Intervention hinzufügen</a>
                            @endif
                            @if(!isset($intervention['date_close']) && !$intervention['to_close'])
                                <a href="#" class="btn focus:outline-hidden text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900" role="button" id="closeIntervention">Intervention abschliessen</a>
                            @endif
                        </div>
                    </x-forms.form>
                </div>
                <div class="col-md-2">
                    @if(!$camp['konekta'])
                            <a href="files/Notfallblatt.pdf" target="blank" class="btn btn-primary">J+S-Notfallblatt herunterladen</a>
                        @if(Auth::user()->isManager())
                            <br>
                            <br>
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <x-forms.form :action="route('uploadProtocol', $healthinformation)" accept-charset="UTF-8" method="POSH" :model="$healthinformation" files=true>

                                <x-forms.container>
                                    <x-forms.file name="file_protocol"/>
                                </x-forms.container>
                                <x-forms.container>
                                    <x-forms.button type="submit" class="btn btn-primary">
                                        J+S-Notfallblatt hochladen
                                    </x-forms.button>
                                </x-forms.container>
                            </x-forms.form>
                            <br>
                            @if ($healthinformation['file_protocol'])
                                <a href={{$healthinformation['file_protocol'] ? route('downloadProtocol',$healthinformation) : '#'}}>Protokoll herunterladen</a>
                            @endif
                            <br>
                        @endif
                        <a href={{route('healthinformation.print',$healthinformation)}} class="btn btn-primary" target="blank">Druckversion</a>
                    @endif
                </div>
            </div>
            <br>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h3>Informationen</h3>
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th scope="col" style="width:30%">Was</th>
                            <th scope="col" style="width:70%">Bemerkung</th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width:30%">kürzliche Unfälle / Krankheiten abgeschlossen?</td>
                                <td>{{$healthinformation['recent_issues']}}</td>
                            </tr>
                            <tr>
                                <td style="width:30%">behandelnder Arzt: Name, Telefon</td>
                                <td>{{$healthinformation['recent_issues_doctor']}}</td>
                            </tr>
                            <tr>
                                <td style="width:30%">Dauermedikation: Medikament, Dosis, Zeitpunkt</td>
                                <td>{{$healthinformation['drug_longterm']}}</td>
                            </tr>
                            <tr>
                                <td style="width:30%">Bei Bedarf: Medikament, Dosis</td>
                                <td>{{$healthinformation['drug']}}</td>
                            </tr>
                            <tr>
                                <td style="width:30%">Notfallmedikation: Medikament, Dosis, Zeitpunkt</td>
                                <td>{{$healthinformation['drug_emergency']}}</td>
                            </tr>
                            <tr>
                                <td style="width:30%">Bemerkungen (chronische Leiden, Bettnässer usw.)</td>
                                <td>{!! nl2br(e($healthinformation['chronicle_diseases'])) !!}</td>
                            </tr>
                            <tr style="{{$healthinformation['ointment_only_contact'] ? '' : 'color:red'}}">
                                <td style="width:30%">Salben ohne Rückfragen?</td>
                                <td>{{$healthinformation['ointment_only_contact'] ? 'Ja' : 'Nein'}}</td>
                            </tr>
                            <tr style="{{$healthinformation['drugs_only_contact'] ? '' : 'color:red'}}">
                                <td style="width:30%">Medikamente ohne Rückfragen?</td>
                                <td>{{$healthinformation['drugs_only_contact'] ? 'Ja' : 'Nein'}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h3>Allergien</h3>
                    <p>{!! nl2br(e($healthinformation['allergy'])) !!}</p>
                </div>
            </div>
            <hr>
            <x-intervention-table :healthinformation="$healthinformation"/>
        </div>
    </section>
@endsection

@push('scripts')
    <x-filter-buttons-javascript :healthinformation="$healthinformation"/>
    <script type="module">
        var new_interventions = 0;
        $('#addIntervention').click(function($e) {
            $e.preventDefault();
            var health_information = @json($healthinformation);
            var intervention = @json($intervention);
            $.ajax({
                url: '{{ route('interventions.addNew') }}',
                type: 'GET',
                data: {
                    healthInformation_id: health_information['id'],
                    index: new_interventions,
                },
                success: function (response) {
                    $('#container_new_interventions').append(response);
                    let el = document.getElementById('newDelete_' + new_interventions);
                    el.addEventListener("click", function(event) {
                        event.preventDefault();
                        this.parentElement.parentElement.parentElement.remove();
                    });
                    new_interventions++;
                },
            });
        });
        $('#closeIntervention').click(function($e) {
            $e.preventDefault();
            var intervention = @json($intervention);
            var intervention_id = intervention['id'] ?? 0;
            console.log(intervention_id);
            $.ajax({
                url: '{{ route('interventions.closeAjax') }}',
                type: 'GET',
                data: {
                    intervention_id: intervention_id,
                },
                success: function (response) {
                    $('#container_intervention_close').empty();
                    $('#closeIntervention').remove();
                    $('#container_intervention_close').append(response);
                    let date =  new Date();
                    let dstrings = getHTML5DateTimeStringsFromDate(date);
                    document.querySelector('#date_close').value = dstrings[0];
                    document.querySelector('#time_close').value = dstrings[1];
                },
            });
        });
        function getHTML5DateTimeStringsFromDate(d) {
            // Date string
            let ds = d.getFullYear().toString().padStart(4, '0') + '-' + (d.getMonth()+1).toString().padStart(2, '0') + '-' + d.getDate().toString().padStart(2, '0');

            // Time string
            let ts = d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');

            // Return them in array
            return [ds, ts];
        }
    </script>
@endpush


