<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'passenger_id',
        'schedule_id',
        'trip_direction',
        'seat_numbers',
        'total_seats',
        'fare_amount',
        'amenities_amount',
        'discount_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_transaction_id',
        'paid_at',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'selected_amenities',
        'notes',
    ];

    protected $casts = [
        'seat_numbers' => 'array',
        'selected_amenities' => 'array',
        'fare_amount' => 'decimal:2',
        'amenities_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (!$booking->booking_number) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });
    }

    // ==================== العلاقات ====================

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    // ==================== Scopes ====================

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeForPassenger($query, $passengerId)
    {
        return $query->where('passenger_id', $passengerId);
    }

    public function scopeForSchedule($query, $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    public function scopeOutbound($query)
    {
        return $query->where('trip_direction', 'outbound');
    }

    public function scopeReturn($query)
    {
        return $query->where('trip_direction', 'return');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereHas('schedule', function ($q) {
            $q->where('departure_date', '>=', now()->toDateString());
        });
    }

    // ==================== Helper Methods ====================

    /**
     * توليد رقم حجز فريد
     */
    public static function generateBookingNumber(): string
    {
        do {
            $number = 'BKG' . now()->format('Ymd') . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * هل الحجز مؤكد؟
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * هل الحجز ملغي؟
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * هل الحجز مكتمل؟
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * هل تم الدفع؟
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * هل الدفع معلق؟
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * هل يمكن إلغاء الحجز؟
     */
    public function canBeCancelled(): bool
    {
        if ($this->isCancelled() || $this->isCompleted()) {
            return false;
        }

        // لا يمكن الإلغاء قبل 24 ساعة من موعد الرحلة
        $schedule = $this->schedule;
        $departureDateTime = Carbon::parse($schedule->departure_date . ' ' . $schedule->departure_time);

        return now()->diffInHours($departureDateTime, false) >= 24;
    }

    /**
     * إلغاء الحجز
     */
    public function cancel(string $reason = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // إرجاع المقاعد للجدول
        $this->schedule->increment('available_seats', $this->total_seats);

        return true;
    }

    /**
     * تأكيد الدفع
     */
    public function confirmPayment(string $transactionId = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'payment_transaction_id' => $transactionId,
            'paid_at' => now(),
        ]);
    }

    /**
     * فشل الدفع
     */
    public function failPayment(): void
    {
        $this->update([
            'payment_status' => 'failed',
        ]);
    }

    /**
     * تنسيق أرقام المقاعد
     */
    public function getFormattedSeats(): string
    {
        return implode(', ', $this->seat_numbers);
    }

    /**
     * الحصول على نوع الرحلة بالعربي
     */
    public function getTripDirectionArabic(): string
    {
        return $this->trip_direction === 'outbound' ? 'ذهاب' : 'عودة';
    }

    /**
     * الحصول على حالة الحجز بالعربي
     */
    public function getStatusArabic(): string
    {
        return match($this->status) {
            'confirmed' => 'مؤكد',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            'no_show' => 'لم يحضر',
            default => $this->status,
        };
    }

    /**
     * الحصول على حالة الدفع بالعربي
     */
    public function getPaymentStatusArabic(): string
    {
        return match($this->payment_status) {
            'pending' => 'معلق',
            'paid' => 'مدفوع',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            default => $this->payment_status,
        };
    }

    /**
     * الحصول على طريقة الدفع بالعربي
     */
    public function getPaymentMethodArabic(): string
    {
        return match($this->payment_method) {
            'cash' => 'نقداً',
            'credit_card' => 'بطاقة ائتمان',
            'apple_pay' => 'Apple Pay',
            'stc_pay' => 'STC Pay',
            'mada' => 'مدى',
            default => $this->payment_method,
        };
    }

    /**
     * معلومات كاملة عن الحجز
     */
    public function getFullInfo(): array
    {
        return [
            'booking_number' => $this->booking_number,
            'passenger' => $this->passenger->name,
            'route' => $this->schedule->route->getTranslation('name', 'ar'),
            'trip_direction' => $this->getTripDirectionArabic(),
            'seats' => $this->getFormattedSeats(),
            'total_seats' => $this->total_seats,
            'total_amount' => $this->total_amount,
            'payment_method' => $this->getPaymentMethodArabic(),
            'payment_status' => $this->getPaymentStatusArabic(),
            'status' => $this->getStatusArabic(),
        ];
    }
}
