<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'stop_id',
        'direction',
        'arrival_time',
        'departure_time',
        'order'
    ];

    protected $casts = [
        'arrival_time' => 'datetime:H:i',
        'departure_time' => 'datetime:H:i',
        'order' => 'integer'
    ];

    // العلاقات
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    // Scopes
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeReturn($query)
    {
        return $query->where('direction', 'return');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // Helper Methods
    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function isReturn(): bool
    {
        return $this->direction === 'return';
    }

    public function getDirectionArabic(): string
    {
        return $this->isOutbound() ? 'ذهاب' : 'عودة';
    }

    public function getStopName(): string
    {
        return $this->stop?->getTranslation('name', 'ar') ?? '';
    }

    public function getStopNameEn(): string
    {
        return $this->stop?->getTranslation('name', 'en') ?? '';
    }
}
