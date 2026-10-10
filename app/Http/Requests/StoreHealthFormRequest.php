<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHealthFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManager();
    }

    /**
     * Felder des Formulars "Gesundheitsblatt erstellen".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'ahv' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'group_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nickname.required' => 'Bitte einen Ceviname angeben.',
            'first_name.required' => 'Bitte einen Vornamen angeben.',
            'last_name.required' => 'Bitte einen Nachnamen angeben.',
        ];
    }
}
