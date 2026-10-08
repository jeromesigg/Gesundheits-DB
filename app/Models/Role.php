<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $casts = [
        'is_admin' => 'boolean',
        'is_manager' => 'boolean',
        'is_helper' => 'boolean',
    ];

    /**
     * Rollen, die eine Lagerleitung vergeben darf (nie Administrator).
     *
     * @return array<int, int>
     */
    public static function assignableIds(): array
    {
        return [
            config('status.role_Lagerleiter'),
            config('status.role_Helfer'),
            config('status.role_Teilnehmer'),
        ];
    }
}
