<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class HealthFormUpdateRequest extends FormRequest
{
    /**
     * Vor der Validierung: Zugriff nur mit Codes oder als Lagerleitung (HealthFormPolicy::access).
     */
    public function authorize(): bool
    {
        return Gate::allows('access', $this->route('healthform'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'submit_btn' => ['required', 'in:save,close'],
            'healthform' => ['nullable', 'array'],
            'healthform.file_allergies' => ['nullable', 'file', 'mimes:pdf', 'max:2000'],
            'healthinfo' => ['nullable', 'array'],
            'healthinfo.accept_privacy_agreement' => ['required_if:submit_btn,close'],
            'health_question' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'healthform.file_allergies.max' => 'Die maximale Dateigrösse beträgt 2 MB.',
            'healthform.file_allergies.mimes' => 'Nur PDF-Dateien sind erlaubt.',
            'healthinfo.accept_privacy_agreement.required_if' => 'Für den Abschluss brauchen wir deine Bestätigung.',
        ];
    }
}
