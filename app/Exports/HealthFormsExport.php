<?php

namespace App\Exports;

use App\Models\HealthForm;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HealthFormsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection
     */
    public function collection()
    {
        return HealthForm::all();
    }

    public function map($healthform): array
    {
        return [
            $healthform->id,
            $healthform->group ? $healthform->group->short_name : '',
            $healthform->code,
            $healthform->nickname,
            $healthform->last_name,
            $healthform->first_name,
            $healthform->ahv,
            Carbon::parse($healthform->birthday)->format('d.m.Y'),
            $healthform->finish ? 'Ja' : 'Nein',
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Abt',
            'Code',
            'Ceviname',
            'Nachname',
            'Vorname',
            'AHV',
            'Geburtstag',
            'Ausgefüllt',
        ];
    }
}
