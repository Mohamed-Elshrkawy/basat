<?php

namespace App\Models;

use App\Services\General\QRCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Booking extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'travel_date' => 'date',
        'seat_numbers' => 'array',
        'outbound_fare' => 'decimal:2',
        'return_fare' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = ['qr_code_url'];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });

        static::created(function ($booking) {
            app(QRCodeService::class)->generateForBooking($booking);
        });

        static::deleting(function ($booking) {
            app(QRCodeService::class)->deleteQRCode($booking);
        });
    }

    /**
     * Generate unique booking number
     */
    public static function generateBookingNumber(): string
    {
        do {
            $number = 'BK' . strtoupper(substr(uniqid(), -6));
        } while (self::where('booking_number', $number)->exists());

        return $number;
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function outboundBoardingStop(): BelongsTo
    {
        return $this->belongsTo(ScheduleStop::class, 'outbound_boarding_stop_id');
    }

    public function outboundDroppingStop(): BelongsTo
    {
        return $this->belongsTo(ScheduleStop::class, 'outbound_dropping_stop_id');
    }

    public function returnBoardingStop(): BelongsTo
    {
        return $this->belongsTo(ScheduleStop::class, 'return_boarding_stop_id');
    }

    public function returnDroppingStop(): BelongsTo
    {
        return $this->belongsTo(ScheduleStop::class, 'return_dropping_stop_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

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

    /**
     * Helper methods
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Passenger Status Methods
     */
    public function checkIn()
    {
        $this->update([
            'passenger_status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
    }

    public function board($stopId = null)
    {
        $this->update([
            'passenger_status' => 'boarded',
            'boarded_at' => now(),
            'boarding_stop_id' => $stopId,
        ]);
    }

    public function markArrived()
    {
        $this->update([
            'passenger_status' => 'completed',
            'arrived_at' => now(),
            'status' => 'completed',
        ]);
    }

    public function markNoShow()
    {
        $this->update([
            'passenger_status' => 'no_show',
        ]);
    }

    public function isCheckedIn(): bool
    {
        return $this->passenger_status === 'checked_in';
    }

    public function isBoarded(): bool
    {
        return $this->passenger_status === 'boarded';
    }

    public function hasArrived(): bool
    {
        return $this->passenger_status === 'completed';
    }

    public function isNoShow(): bool
    {
        return $this->passenger_status === 'no_show';
    }

    /**
     * Get QR Code URL
     */
    public function getQrCodeUrlAttribute(): string
    {
        $filename = "qrcodes/booking_{$this->booking_number}.png";

        if (Storage::disk('public')->exists($filename)) {
            return Storage::disk('public')->url($filename);
        }

        return app(QRCodeService::class)->generateForBooking($this);
    }

    /**
     * Mark booking as paid
     */
    public function markAsPaid(string $transactionId = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'status' => 'confirmed',
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // إرجاع المقاعد للرحلة
        $this->schedule->increment('available_seats', $this->number_of_seats);
    }
}
