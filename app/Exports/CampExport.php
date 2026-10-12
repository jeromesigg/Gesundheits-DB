<?php

namespace App\Exports;

use App\Models\Camp;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Gesamtexport eines Lagers: Teilnehmende und Interventionen in je einem Tabellenblatt.
 */
class CampExport implements WithMultipleSheets
{
    public function __construct(public Camp $camp) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new HealthFormsExport($this->camp),
            new InterventionsExport($this->camp),
        ];
    }
}
