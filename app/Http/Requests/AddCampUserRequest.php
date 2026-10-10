<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCampUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManager();
    }

    /**
     * Bestehende Person ins Lager aufnehmen; Admins nicht.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists('users', 'id')->whereNot('role_id', UserRole::Administrator->value)],
            'role_id_add' => ['required', 'integer', Rule::in(UserRole::assignableValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Bitte eine Person aus der Suche auswählen.',
            'user_id.exists' => 'Diese Person kann nicht hinzugefügt werden.',
            'role_id_add.in' => 'Diese Rolle kann nicht vergeben werden.',
        ];
    }
}
