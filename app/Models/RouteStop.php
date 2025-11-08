<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'stop_id',
        'order'
    ];

    protected $casts = [
        'order' => 'integer'
    ];

    // العلاقات
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }

    // Methods مساعدة
    public function isFirstStop(): bool
    {
        return $this->order === 1;
    }

    public function isLastStop(): bool
    {
        return $this->order === $this->route->routeStops()->max('order');
    }
}
