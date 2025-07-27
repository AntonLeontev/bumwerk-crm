<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contact extends Model
{
    protected $fillable = [
        'title',
        'name',
        'surname',
        'patronymic',
        'comment',
        'telegram',
    ];

    public function phone(): HasOne
    {
        return $this->hasOne(Phone::class);
    }

    public function email(): HasOne
    {
        return $this->hasOne(Email::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
}
