<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Child extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Accessor to calculate the child's age from birth_date.
     * @return int|null
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->birth_date) {
            return \Carbon\Carbon::parse($this->birth_date)->age;
        }
        return null;
    }
}
