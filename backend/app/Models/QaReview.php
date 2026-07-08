<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaReview extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'project_id',
        'reviewer_id',
        'status',
        'overall_notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QaReviewItem::class);
    }
}
