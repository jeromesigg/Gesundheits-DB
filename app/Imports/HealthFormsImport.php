<?php

namespace App\Imports;

use App\Models\HealthForm;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HealthFormsImport implements ToModel, WithCalculatedFormulas, WithHeadingRow
{
    use Importable;

    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        return new HealthForm([
            'group' => $row[0],
            'last_name' => $row[4],
            'first_name' => $row[5],
            'nickname' => $row[6] ? $row[6] : $row[5],
            'street' => $row[7],
            'zip_code' => $row[8],
            'city' => $row[9],
            'birthday' => $row[10],
            'ahv' => $row[11],
            //
        ]);
    }
}
