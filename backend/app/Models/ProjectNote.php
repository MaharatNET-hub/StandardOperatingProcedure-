<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ملاحظة واحدة ضمن سجل المشروع (Project Notes). كل ملاحظة سجل مستقل بتاريخه
 * وصاحبه، ولا يتم استبدال الملاحظات القديمة عند إضافة ملاحظة جديدة (BR-11).
 */
class ProjectNote extends Model
{
    /** @var array<int, string> Note - Type (SRS §20) */
    public const TYPES = ['client_feedback', 'client_update', 'internal_note', 'decision', 'meeting'];

    /** @var array<int, string> Note - Status (SRS §20) */
    public const STATUSES = ['new', 'in_progress', 'done'];

    protected $fillable = ['project_id', 'title', 'type', 'body', 'status', 'created_by'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
