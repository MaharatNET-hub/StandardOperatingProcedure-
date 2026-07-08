<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectChecklistItem extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_NA = 'na';

    protected $fillable = [
        'project_id',
        'checklist_item_id',
        'status',
        'checked_by',
        'checked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(QaReviewItem::class);
    }
}
