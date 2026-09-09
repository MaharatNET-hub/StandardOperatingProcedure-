<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DELIVERED = 'delivered';

    /** @var array<int, string> مراحل خط سير المشروع التجاري (مستقلة عن status الخاص بسير عمل الجودة/QA) */
    public const PIPELINE_STAGES = [
        'new_project', 'information_collection', 'ready_to_start', 'in_progress',
        'waiting_client', 'waiting_payment', 'waiting_content', 'internal_review_qa', 'testing', 'client_review',
        'changes_requested', 'final_review', 'ready_for_launch', 'live', 'completed', 'paused', 'cancelled',
    ];

    /** @var array<int, string> المراحل المغلقة التي لا تُحتسب لها أولوية (BR-01) */
    public const CLOSED_STAGES = ['paused', 'cancelled', 'completed'];

    public const BLOCKERS = [
        'none', 'waiting_client', 'waiting_developer', 'waiting_payment_gateway', 'waiting_domain',
        'waiting_hosting', 'waiting_content', 'waiting_logo', 'waiting_product_images', 'waiting_shipping', 'other',
    ];

    /**
     * العوائق التي تجعل المشروع غير قابل للتنفيذ من قِبل المبرمج لأنها بانتظار
     * العميل أو جهة خارجية (BR-02 و BR-15).
     *
     * @var array<int, string>
     */
    public const NON_WORKABLE_BLOCKERS = [
        'waiting_client', 'waiting_domain', 'waiting_hosting', 'waiting_content',
        'waiting_logo', 'waiting_product_images', 'waiting_payment_gateway', 'waiting_shipping',
    ];

    public const FEEDBACK_STATUSES = ['none', 'new', 'in_progress', 'completed'];

    public const PROJECT_TYPES = [
        'ecommerce', 'corporate', 'landing_page', 'portfolio', 'blog',
        'booking', 'marketplace', 'custom', 'mobile_app', 'other',
    ];

    protected $fillable = [
        'name',
        'client_name',
        'project_type',
        'project_description',
        'current_task',
        'envato_preview_url',
        'site_url',
        'status',
        'pipeline_stage',
        'current_phase_id',
        'created_by',
        'primary_developer_id',
        'content_deadline',
        'start_date',
        'next_meeting_at',
        'paused_since',
        'revision_rounds_allowed',
        'has_domain', 'domain_name', 'domain_login_info', 'domain_purchaser',
        'has_hosting', 'hosting_provider', 'hosting_login_info', 'hosting_purchaser',
        'has_logo', 'needs_logo_design', 'design_style', 'website_languages_count', 'primary_language', 'secondary_language',
        'needs_payment_gateway', 'payment_gateway_type', 'payment_gateway_status', 'has_shipping_company', 'shipping_company_name',
        'content_ready', 'product_images_ready', 'seo_required', 'seo_status', 'google_analytics_connected', 'search_console_connected',
        'phone', 'whatsapp', 'social_media_available', 'social_media_login_info',
        'last_client_update_at', 'next_client_update_at', 'client_update_interval_days',
        'client_feedback_status', 'client_feedback_notes', 'client_feedback_at',
        'client_notes', 'internal_notes', 'blocker', 'website_uploaded',
    ];

    protected function casts(): array
    {
        return [
            'content_deadline' => 'date',
            'start_date' => 'date',
            'next_meeting_at' => 'datetime',
            'paused_since' => 'date',
            'last_client_update_at' => 'datetime',
            'next_client_update_at' => 'datetime',
            'client_feedback_at' => 'datetime',
            'has_domain' => 'boolean',
            'has_hosting' => 'boolean',
            'has_logo' => 'boolean',
            'needs_logo_design' => 'boolean',
            'needs_payment_gateway' => 'boolean',
            'has_shipping_company' => 'boolean',
            'content_ready' => 'boolean',
            'product_images_ready' => 'boolean',
            'seo_required' => 'boolean',
            'google_analytics_connected' => 'boolean',
            'search_console_connected' => 'boolean',
            'social_media_available' => 'boolean',
            'website_uploaded' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function primaryDeveloper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_developer_id');
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

    public function seoAudits(): HasMany
    {
        return $this->hasMany(SeoAudit::class);
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

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function requirementUpdates(): HasMany
    {
        return $this->hasMany(ProjectRequirementUpdate::class);
    }

    /** آخر ملاحظة مسجّلة — تُعرض على بطاقة المشروع في القائمة. */
    public function latestNote(): HasOne
    {
        return $this->hasOne(ProjectNote::class)->latestOfMany('id');
    }

    /** عدد الأيام التي قضاها المشروع متوقفاً (لوحة Paused — Monitor). */
    public function pausedDays(): ?int
    {
        if ($this->pipeline_stage !== 'paused' || ! $this->paused_since) {
            return null;
        }

        return (int) $this->paused_since->copy()->startOfDay()->diffInDays(now()->startOfDay());
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
