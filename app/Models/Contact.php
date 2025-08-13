<?php

namespace App\Models;

use App\QueryBuilders\ContactQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(ContactQueryBuilder::class)]
class Contact extends Model
{
    use SoftDeletes;

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

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
