<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'registered_email',
        'expiry_date',
        'renewal_responsible_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function renewalResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewal_responsible_id');
    }
}
