<div id="container_intervention_close">
    @if($close)
        <hr class="h-1 mx-auto my-4 bg-gray-300 border-0 rounded-sm md:my-10 dark:bg-gray-700">
        <x-forms.row>
            <x-forms.container class="col-xl-2 col-lg-12">
                    <x-forms.text label="Datum:" name="intervention_new['.$index.'][date]" type="date" required=true/>
                <x-forms.text label="Datum Ende Behandlung:" name="date_close" type="date" required=true/>
                <br>
                <x-forms.text label="Zeit Ende Behandlung:" name="time_close" type="time" required=true/>
                <br>
                <x-forms.text label="Erfasser Ende Behandlung:" name="user_close" required=true/>
            </x-forms.container>
             <x-forms.container class="col-xl-8 col-lg-12">
                <div class="form-group">
                    <x-forms.text-area label="Weiteres Prozedere:" name="further_treatment" required=true rows=3/>
                </div>
                <div class="form-group">
                    <x-forms.text-area label="Bemerkung Ende Behandlung:" name="comment_close" rows=3/>
                </div>
            </x-forms.container>
        </x-forms.row>
    @endif
</div>