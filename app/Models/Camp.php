<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        // here assign this team to a global user with global default role
        // Alle Admins des Global-Camps werden Admin im neuen Lager.
        self::created(function (Camp $camp) {
            $globalCamp = Camp::where('global_camp', true)->whereKeyNot($camp->id)->first();
            if ($globalCamp === null) {
                return;
            }
            $adminIds = CampUser::where('camp_id', $globalCamp->id)
                ->where('role_id', UserRole::Administrator)
                ->pluck('user_id');
            foreach ($adminIds as $adminId) {
                CampUser::firstOrCreate(
                    ['camp_id' => $camp->id, 'user_id' => $adminId],
                    ['role_id' => UserRole::Administrator],
                );
            }
        });
    }

    protected $fillable = [
        'name', 'user_id', 'independent_form_fill', 'global_camp', 'finish', 'code', 'end_date', 'counter', 'group_id', 'group_text', 'closed_when_finished',
        'forms_finished', 'show_names', 'konekta', 'max_serial_number',
    ];

    protected $casts = [
        'independent_form_fill' => 'boolean',
        'global_camp' => 'boolean',
        'demo' => 'boolean',
        'finish' => 'boolean',
        'closed_when_finished' => 'boolean',
        'show_names' => 'boolean',
        'konekta' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function allUsers()
    {
        return $this->belongsToMany('App\Models\User', 'camp_users')
            ->where('camp_users.role_id', '<>', UserRole::Administrator);
    }

    public function camp_users_all()
    {
        return $this->hasMany(CampUser::class)
            ->where('camp_users.role_id', '<>', UserRole::Administrator);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->where('questions.active', '=', true);
    }

    public function interventions()
    {
        return $this->hasManyThrough(Intervention::class, HealthInformation::class);
    }

    public function health_infos()
    {
        return $this->hasMany(HealthInformation::class);
    }

    public function health_forms()
    {
        return HealthForm::where('camp_id', $this['id']);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
