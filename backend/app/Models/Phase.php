<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phase extends Model
{
    protected $fillable = [
        'number',
        'name_ar',
        'description',
        'estimated_duration',
        'order',
    ];

    public function projectPhases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }
}
