<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'trip_date',
        'status',
        'started_at',
        'completed_at',
        'driver_notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function stationProgress(): HasMany
    {
        return $this->hasMany(TripStationProgress::class)->orderBy('stop_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'schedule_id', 'schedule_id')
            ->whereDate('travel_date', $this->trip_date);
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('trip_date')->isFuture();
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->whereHas('schedule', function ($q) use ($driverId) {
            $q->where('driver_id', $driverId);
        });
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('trip_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Helper Methods
     */
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getTotalPassengersAttribute(): int
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('number_of_seats');
    }

    public function getCheckedInPassengersAttribute(): int
    {
        return $this->bookings()
            ->whereIn('passenger_status', ['checked_in', 'boarded', 'completed'])
            ->count();
    }

    public function getBoardedPassengersAttribute(): int
    {
        return $this->bookings()
            ->whereIn('passenger_status', ['boarded', 'completed'])
            ->count();
    }
}
