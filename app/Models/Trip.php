<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Trip extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'pickup_lat' => 'decimal:8', 'pickup_lng' => 'decimal:8',
        'dropoff_lat' => 'decimal:8', 'dropoff_lng' => 'decimal:8',
        'distance' => 'float', 'duration' => 'integer',
        'trip_datetime' => 'datetime', 'is_round_trip' => 'boolean',
        'return_datetime' => 'datetime', 'base_fare' => 'float',
        'amenities_fare' => 'float', 'selected_amenities' => 'array', 'tax_percentage' => 'float',
        'tax_amount' => 'float', 'app_fee_percentage' => 'float',
        'app_fee' => 'float', 'driver_earning' => 'float', 'total_fare' => 'float',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('responsible_person_id')
             ->singleFile();
    }

    public function getResponsiblePersonIdPhotoUrlAttribute()
    {
        return $this->getFirstMediaUrl('responsible_person_id');
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tripable()
    {
        return $this->morphTo();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function schoolSubscriptions()
    {
        // هذه العلاقة صحيحة للرحلة الرئيسية (العقد)
        return $this->hasMany(SchoolSubscription::class);
    }

    public function schoolSubscription()
    {
        // هذه العلاقة خاصة بالرحلات اليومية
        // حيث أن tripable_type سيكون SchoolSubscription::class
        return $this->tripable();
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function problemReports()
    {
        return $this->hasMany(ProblemReport::class);
    }

    public function selectedAmenities()
    {
        if (!$this->selected_amenities) {
            return collect();
        }

        return Amenity::whereIn('id', $this->selected_amenities)->get();
    }
}
