<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistCategory extends Model
{
    protected $fillable = [
        'group',
        'code',
        'name_ar',
        'rule_note',
        'order',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('order');
    }
}
