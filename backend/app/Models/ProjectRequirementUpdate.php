<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تغيير واحد على حالة متطلّب من متطلبات المشروع (جاهز / قيد التنفيذ / ناقص)
 * بتاريخ سريانه — يسمح بتسجيل أن الشعار صار جاهزاً بتاريخ سابق لا بتاريخ
 * إدخال البيانات.
 */
class ProjectRequirementUpdate extends Model
{
    protected $fillable = ['project_id', 'requirement_key', 'status', 'effective_date', 'note', 'changed_by'];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
