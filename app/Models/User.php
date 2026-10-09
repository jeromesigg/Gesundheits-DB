<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Helper\Helper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Nicolaslopezj\Searchable\SearchableTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    use SearchableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password', 'slug',
        'role_id', 'camp_id', 'demo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'demo' => 'boolean',
        'role_id' => UserRole::class,
    ];

    protected $searchable = [
        'columns' => [
            'username' => 1,
        ],
    ];

    public function isAdmin(): bool
    {
        return $this->role_id === UserRole::Administrator;
    }

    public function isManager(): bool
    {
        return $this->role_id?->canManage() ?? false;
    }

    public function isHelper(): bool
    {
        return $this->role_id?->canHelp() ?? false;
    }

    public function camp_user()
    {
        $camp = Auth::user()->camp;

        return CampUser::where('camp_id', '=', $camp['id'])->where('user_id', '=', $this['id']);
    }

    public function camp_users()
    {
        return $this->hasMany(CampUser::class)
            ->where('camp_users.role_id', '<>', UserRole::Administrator)
            ->where('camp_users.active', '=', true);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function camp()
    {
        return $this->belongsTo('App\Models\Camp');
    }

    public function camps()
    {
        return $this->belongsToMany('App\Models\Camp', 'camp_users')->where('finish', '=', false);
    }

    public function getAvatar()
    {
        $camp = Auth::user()->camp;
        $camp_user = CampUser::where('user_id', $this->id)->where('camp_id', $camp->id)->first();
        $path = null;
        if ($camp_user) {
            $path = Helper::getAvatarPath($camp_user->avatar);
        }
        if ($path === null) {
            $path = Helper::getAvatarPath($this->avatar);
        }
        if ($path === null) {
            $path = '/img/default_avatar.svg';
        }

        return $path;
    }
}
