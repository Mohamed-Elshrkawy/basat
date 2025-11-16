<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStationProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_instance_id',
        'schedule_stop_id',
        'stop_order',
        'direction',
        'status',
        'arrived_at',
        'departed_at',
        'passengers_boarded',
        'passengers_alighted',
        'notes',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'departed_at' => 'datetime',
        'passengers_boarded' => 'integer',
        'passengers_alighted' => 'integer',
    ];

    /**
     * Relations
     */
    public function tripInstance(): BelongsTo
    {
        return $this->belongsTo(TripInstance::class);
    }

    public function scheduleStop(): BelongsTo
    {
        return $this->belongsTo(ScheduleStop::class);
    }

    /**
     * Helper Methods
     */
    public function markArrived()
    {
        $this->update([
            'status' => 'arrived',
            'arrived_at' => now(),
        ]);
    }

    public function markDeparted()
    {
        $this->update([
            'status' => 'departed',
            'departed_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isArrived(): bool
    {
        return $this->status === 'arrived';
    }

    public function isDeparted(): bool
    {
        return $this->status === 'departed';
    }
}
