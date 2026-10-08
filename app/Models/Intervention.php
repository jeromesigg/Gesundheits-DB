<?php

namespace App\Models;

use App\Enums\TrafficLight;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Intervention extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $casts = [
        'health_status_id' => TrafficLight::class,
    ];

    protected $fillable = [
        'id', 'health_information_id', 'date', 'time', 'parameter', 'value', 'comment', 'user_id', 'user_erf', 'file',
        'further_treatment', 'user_close', 'date_close', 'time_close', 'comment_close', 'medication', 'health_status_id', 'intervention_master_id', 'serial_number', 'max_serial_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function health_information()
    {
        return $this->belongsTo(HealthInformation::class);
    }

    public function health_status()
    {
        return $this->belongsTo(HealthStatus::class);
    }

    public function intervention()
    {
        return $this->belongsTo(Intervention::class, 'intervention_master_id');
    }

    public function interventions()
    {
        return $this->hasMany(Intervention::class, 'intervention_master_id');
    }

    public function number()
    {
        return $this->intervention ? $this->intervention['serial_number'].'.'.$this['serial_number'] : $this['serial_number'];
    }

    protected function intervention_name(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['number'].' '.$attributes['parameter']
        );
    }
}
