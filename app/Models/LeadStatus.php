<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadStatus extends Model
{
    protected $fillable = [
        'name',
        'color',
        'position',
        'is_final',
        'is_system',
        'is_start',
        'is_win',
        'is_loose',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_system' => 'boolean',
        'is_start' => 'boolean',
        'is_win' => 'boolean',
        'is_loose' => 'boolean',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
