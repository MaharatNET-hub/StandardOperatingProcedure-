<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    protected $fillable = [
        'url',
        'client_name',
        'status',
        'detected_framework',
        'detected_signals',
        'meta_title',
        'meta_description',
        'business_type',
        'business_summary',
        'infrastructure',
        'recommended_platform',
        'recommendation_reason',
        'crawl_summary',
        'proposed_pages',
        'homepage_screenshot',
        'ux_score',
        'seo_score',
        'speed_score',
        'audit_recommendation',
        'project_summary',
        'technical_scope',
        'cost_items',
        'currency',
        'domain_cost',
        'hosting_cost',
        'hosting_cycle',
        'support_months',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'detected_signals' => 'array',
            'infrastructure' => 'array',
            'crawl_summary' => 'array',
            'proposed_pages' => 'array',
            'cost_items' => 'array',
            'domain_cost' => 'decimal:2',
            'hosting_cost' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
