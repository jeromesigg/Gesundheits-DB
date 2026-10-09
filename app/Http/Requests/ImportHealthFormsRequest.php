<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportHealthFormsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManager();
    }

    /**
     * Teilnehmerliste: nur Excel/CSV. PhpSpreadsheet würde sonst z. B. auch HTML-Dateien einlesen.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Bitte eine Teilnehmerliste auswählen.',
            'file.mimes' => 'Nur Excel- oder CSV-Dateien sind erlaubt.',
            'file.max' => 'Die maximale Dateigrösse beträgt 5 MB.',
        ];
    }
}
