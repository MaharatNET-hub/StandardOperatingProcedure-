<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaReviewItem extends Model
{
    public const VERDICT_PASS = 'pass';

    public const VERDICT_FAIL = 'fail';

    protected $fillable = [
        'qa_review_id',
        'project_checklist_item_id',
        'verdict',
        'comment',
    ];

    public function qaReview(): BelongsTo
    {
        return $this->belongsTo(QaReview::class);
    }

    public function projectChecklistItem(): BelongsTo
    {
        return $this->belongsTo(ProjectChecklistItem::class);
    }
}
