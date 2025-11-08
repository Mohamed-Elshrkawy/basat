<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'driver_id',
        'trip_type',
        'departure_time',
        'arrival_time',
        'return_departure_time',
        'return_arrival_time',
        'fare',
        'return_fare',
        'round_trip_discount',
        'days_of_week',
        'available_seats',
        'is_active'
    ];

    protected $casts = [
        'departure_time' => 'datetime:H:i',
        'arrival_time' => 'datetime:H:i',
        'return_departure_time' => 'datetime:H:i',
        'return_arrival_time' => 'datetime:H:i',
        'fare' => 'decimal:2',
        'return_fare' => 'decimal:2',
        'round_trip_discount' => 'decimal:2',
        'days_of_week' => 'array',
        'available_seats' => 'integer',
        'is_active' => 'boolean'
    ];

    // ==================== العلاقات ====================

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scheduleStops(): HasMany
    {
        return $this->hasMany(ScheduleStop::class)->orderBy('order');
    }

    public function outboundStops(): HasMany
    {
        return $this->scheduleStops()->where('direction', 'outbound')->orderBy('order');
    }

    public function returnStops(): HasMany
    {
        return $this->scheduleStops()->where('direction', 'return')->orderBy('order');
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOneWay($query)
    {
        return $query->where('trip_type', 'one_way');
    }

    public function scopeRoundTrip($query)
    {
        return $query->where('trip_type', 'round_trip');
    }

    public function scopeToday($query)
    {
        $today = Carbon::now()->locale('en')->dayName;
        return $query->whereJsonContains('days_of_week', $today);
    }

    public function scopeOnDay($query, $day)
    {
        return $query->whereJsonContains('days_of_week', $day);
    }

    public function scopeForRoute($query, $routeId)
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeWithDriver($query)
    {
        return $query->whereNotNull('driver_id');
    }

    public function scopeWithoutDriver($query)
    {
        return $query->whereNull('driver_id');
    }

    // ==================== Helper Methods ====================

    public function isOneWay(): bool
    {
        return $this->trip_type === 'one_way';
    }

    public function isRoundTrip(): bool
    {
        return $this->trip_type === 'round_trip';
    }

    public function isAvailableToday(): bool
    {
        $today = Carbon::now()->locale('en')->dayName;
        return in_array($today, $this->days_of_week ?? []);
    }

    public function isAvailableOnDay(string $day): bool
    {
        return in_array($day, $this->days_of_week ?? []);
    }

    public function hasSeatsAvailable(): bool
    {
        return $this->available_seats > 0;
    }

    // ==================== Duration Methods ====================

    public function getOutboundDuration(): ?string
    {
        if (!$this->departure_time || !$this->arrival_time) {
            return null;
        }

        $departure = Carbon::parse($this->departure_time);
        $arrival = Carbon::parse($this->arrival_time);

        return $departure->diff($arrival)->format('%H:%I');
    }

    public function getReturnDuration(): ?string
    {
        if (!$this->return_departure_time || !$this->return_arrival_time) {
            return null;
        }

        $departure = Carbon::parse($this->return_departure_time);
        $arrival = Carbon::parse($this->return_arrival_time);

        return $departure->diff($arrival)->format('%H:%I');
    }

    public function getTotalDuration(): ?string
    {
        if ($this->isOneWay()) {
            return $this->getOutboundDuration();
        }

        $outbound = $this->getOutboundDuration();
        $return = $this->getReturnDuration();

        if (!$outbound || !$return) {
            return null;
        }

        list($outH, $outM) = explode(':', $outbound);
        list($retH, $retM) = explode(':', $return);

        $totalMinutes = ($outH * 60 + $outM) + ($retH * 60 + $retM);
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // ==================== Pricing Methods ====================

    public function getRoundTripPrice(): float
    {
        if ($this->isOneWay()) {
            return (float) $this->fare;
        }

        $separatePrice = (float) $this->fare + (float) ($this->return_fare ?? 0);
        $discount = (float) ($this->round_trip_discount ?? 0);

        return $separatePrice - $discount;
    }

    public function getRoundTripOriginalPrice(): float
    {
        if ($this->isOneWay()) {
            return (float) $this->fare;
        }

        return (float) $this->fare + (float) ($this->return_fare ?? 0);
    }

    public function getDiscountPercentage(): float
    {
        if ($this->isOneWay() || !$this->round_trip_discount) {
            return 0;
        }

        $originalPrice = $this->getRoundTripOriginalPrice();
        $discount = (float) $this->round_trip_discount;

        if ($originalPrice == 0) {
            return 0;
        }

        return round(($discount / $originalPrice) * 100, 2);
    }

    public function getBestPrice(): float
    {
        if ($this->isOneWay()) {
            return (float) $this->fare;
        }

        return $this->getRoundTripPrice();
    }

    // ==================== Days Methods ====================

    public function getDaysOfWeekArabic(): array
    {
        $daysMap = [
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
        ];

        return array_map(fn($day) => $daysMap[$day] ?? $day, $this->days_of_week ?? []);
    }

    public function getDaysOfWeekShort(): string
    {
        $daysMap = [
            'Monday' => 'إثنين',
            'Tuesday' => 'ثلاثاء',
            'Wednesday' => 'أربعاء',
            'Thursday' => 'خميس',
            'Friday' => 'جمعة',
            'Saturday' => 'سبت',
            'Sunday' => 'أحد',
        ];

        $days = array_map(fn($day) => $daysMap[$day] ?? $day, $this->days_of_week ?? []);

        return implode('، ', $days);
    }

    // ==================== Display Methods ====================

    public function getFullScheduleInfo(): string
    {
        $route = $this->route;
        if (!$route) {
            return '';
        }

        $info = $route->getFullRouteName() . ' - ';
        $info .= $this->isRoundTrip() ? 'ذهاب وعودة' : 'ذهاب فقط';
        $info .= ' - ' . $this->departure_time->format('H:i');

        return $info;
    }

    public function getTripTypeArabic(): string
    {
        return $this->isRoundTrip() ? 'ذهاب وعودة' : 'ذهاب فقط';
    }

    public function getStatusBadge(): array
    {
        if (!$this->is_active) {
            return ['label' => 'غير نشط', 'color' => 'danger'];
        }

        if (!$this->hasSeatsAvailable()) {
            return ['label' => 'مكتمل', 'color' => 'warning'];
        }

        if ($this->driver_id) {
            return ['label' => 'جاهز', 'color' => 'success'];
        }

        return ['label' => 'بانتظار السائق', 'color' => 'info'];
    }
}
