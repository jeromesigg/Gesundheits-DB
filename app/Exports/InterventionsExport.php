<?php

namespace App\Exports;

use App\Models\Camp;
use App\Models\HealthForm;
use App\Models\Intervention;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class InterventionsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
     * Ceviname pro Code (Klartext); der Code ist auf dem Gesundheitsblatt verschlüsselt.
     *
     * @var Collection<string, string|null>|null
     */
    private ?Collection $nicknames = null;

    public function __construct(public Camp $camp) {}

    /**
     * Alle Interventionen dieses Lagers, nach Teilnehmenden und Nummer sortiert.
     *
     * @return Collection<int, Intervention>
     */
    public function collection()
    {
        return $this->camp->interventions()
            ->with(['health_information', 'intervention'])
            ->reorder()
            ->orderBy('health_information.code')
            ->orderBy('interventions.date')
            ->orderBy('interventions.time')
            ->get();
    }

    /**
     * @param  Intervention  $intervention
     */
    public function map($intervention): array
    {
        $code = $intervention->health_information?->code;

        return [
            $intervention->number(),
            $code,
            $this->nicknames()->get((string) $code, ''),
            $intervention->date ? Carbon::parse($intervention->date)->format('d.m.Y') : '',
            $intervention->time,
            $intervention->parameter,
            $intervention->value,
            $intervention->medication,
            $intervention->comment,
            $intervention->user_erf,
            $intervention->date_close ? '' : $intervention->health_status_id?->label(),
            $intervention->date_close ? 'Ja' : 'Nein',
            $intervention->date_close ? Carbon::parse($intervention->date_close)->format('d.m.Y') : '',
            $intervention->time_close,
            $intervention->user_close,
            $intervention->further_treatment,
            $intervention->comment_close,
        ];
    }

    public function headings(): array
    {
        return [
            'Nr.',
            'Code',
            'Ceviname',
            'Datum',
            'Zeit',
            'Parameter / Symptom',
            'Wert',
            'Intervention / Medikation',
            'Bemerkung',
            'Erfasst von',
            'Status',
            'Abgeschlossen',
            'Datum Abschluss',
            'Zeit Abschluss',
            'Abgeschlossen von',
            'Weiteres Prozedere',
            'Bemerkung Ende Behandlung',
        ];
    }

    public function title(): string
    {
        return 'Interventionen';
    }

    /**
     * @return Collection<string, string|null>
     */
    private function nicknames(): Collection
    {
        return $this->nicknames ??= HealthForm::where('camp_id', $this->camp->id)
            ->get()
            ->mapWithKeys(fn (HealthForm $healthForm) => [(string) $healthForm->code => $healthForm->nickname]);
    }
}
