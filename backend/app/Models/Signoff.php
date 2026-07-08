<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signoff extends Model
{
    public const ROLE_PREPARED = 'prepared';

    public const ROLE_REVIEWED = 'reviewed';

    public const ROLE_FINAL_APPROVAL = 'final_approval';

    protected $fillable = [
        'project_id',
        'role',
        'user_id',
        'signature_name',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
