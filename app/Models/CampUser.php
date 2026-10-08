<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'camp_id', 'user_id', 'role_id', 'avatar', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'role_id' => UserRole::class,
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function camp()
    {
        return $this->belongsTo('App\Models\Camp');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }
}
