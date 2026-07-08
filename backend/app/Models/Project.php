<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'name',
        'client_name',
        'envato_preview_url',
        'status',
        'current_phase_id',
        'created_by',
        'content_deadline',
        'revision_rounds_allowed',
    ];

    protected function casts(): array
    {
        return [
            'content_deadline' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(Phase::class, 'current_phase_id');
    }

    public function developers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function projectPhases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectChecklistItem::class);
    }

    public function pluginRequests(): HasMany
    {
        return $this->hasMany(PluginRequest::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function performanceReports(): HasMany
    {
        return $this->hasMany(PerformanceReport::class);
    }

    public function qaReviews(): HasMany
    {
        return $this->hasMany(QaReview::class);
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(Signoff::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function checklistProgress(): array
    {
        $total = $this->checklistItems()->count();
        $done = $this->checklistItems()->where('status', 'done')->count();

        return [
            'total' => $total,
            'done' => $done,
            'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
        ];
    }
}
