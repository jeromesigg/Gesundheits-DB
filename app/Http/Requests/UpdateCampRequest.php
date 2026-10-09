<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('camp'));
    }

    /**
     * Nur die Felder des Formulars; Verantwortliche Person muss Lagerleitung dieses Lagers sein.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('camps', 'name')->ignore($this->route('camp'))],
            'user_id' => [
                'nullable',
                Rule::exists('camp_users', 'user_id')
                    ->where('camp_id', $this->route('camp')->id)
                    ->where('role_id', UserRole::CampLeader->value),
            ],
            'end_date' => ['nullable', 'date'],
            'independent_form_fill' => ['nullable'],
            'closed_when_finished' => ['nullable'],
            'show_names' => ['nullable'],
            'group_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Es gibt bereits ein Lager mit diesem Namen.',
            'user_id.exists' => 'Verantwortlich kann nur eine Lagerleitung dieses Lagers sein.',
        ];
    }
}
