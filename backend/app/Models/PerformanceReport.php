<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReport extends Model
{
    public const STAGE_ORIGINAL_TEMPLATE = 'original_template';

    public const STAGE_FINAL_SITE = 'final_site';

    protected $fillable = [
        'project_id',
        'stage',
        'lighthouse_mobile',
        'lighthouse_desktop',
        'pagespeed_url',
        'screaming_frog_report_url',
        'plugin_count',
        'notes',
        'measured_by',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function measurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
