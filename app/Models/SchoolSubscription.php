<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolSubscription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'daily_status' => 'array',
    ];

    // هذه هي العلاقة مع الرحلة الرئيسية (العقد)
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function schoolPackage()
    {
        return $this->belongsTo(SchoolPackage::class);
    }

    /**
     * علاقة جديدة لجلب جميع الرحلات اليومية المرتبطة بهذا الاشتراك مباشرة.
     */
    public function dailyTrips()
    {
        return $this->morphMany(Trip::class, 'tripable');
    }
}
