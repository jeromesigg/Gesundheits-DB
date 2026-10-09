<div id="{{'intervention_new_'.$index}}">
    <hr class="h-0.5 mx-auto my-4 bg-gray-300 border-0 rounded-sm md:my-10 dark:bg-gray-700">
    <x-forms.row class="ml-10">
        <x-forms.container class="col-xl-2 col-lg-12">
            <x-forms.hidden name="intervention_new['.$index.'][health_information_id]" :value="$intervention['health_information_id']"/>
            <x-forms.hidden name="intervention_new['.$index.'][intervention_id]" :value="null"/>
            <br>
            <x-forms.row>
                <x-forms.container class="col-xl-6 col-lg-12">
                    <x-forms.text label="Datum:" name="intervention_new['.$index.'][date]" type="date" required=true/>
                </x-forms.container>
                <x-forms.container class="col-xl-6 col-lg-12">
                    <x-forms.text label="Zeit:" name="intervention_new['.$index.'][time]" type="time" required=true/>
                </x-forms.container>
            </x-forms.row>
            <br>
            <x-forms.text label="Erfasser:" name="intervention_new['.$index.'][user_erf]" required=true/>
            <br>
            <x-forms.select label="Dringlichkeit:" name="intervention_new['.$index.'][health_status_id]" :collection="$healthstatus" required=true/>
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
            <x-forms.container id="intervention_picture">
                <x-forms.file label="Bild:" name="intervention_new['.$index.'][file]" accept="image/*" capture="camera"/>
            </x-forms.container>
            <br>
            <a href="#" id="newDelete_{{$index}}"><i class="fa-solid fa-trash-can fa-2xl" style="color:red"></i></a>
        </x-forms.container>
    </x-forms.row>
</div>