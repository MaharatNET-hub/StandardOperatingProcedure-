<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    public const TYPE_FILE = 'file';

    public const TYPE_LINK = 'link';

    protected $fillable = [
        'project_checklist_item_id',
        'type',
        'path',
        'original_name',
        'url',
        'uploaded_by',
    ];

    public function projectChecklistItem(): BelongsTo
    {
        return $this->belongsTo(ProjectChecklistItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
