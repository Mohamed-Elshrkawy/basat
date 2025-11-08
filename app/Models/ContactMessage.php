<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'reference_number',
        'name',
        'phone',
        'type',
        'subject',
        'message',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeSuggestions($query)
    {
        return $query->where('type', 'suggestion');
    }

    public function scopeComplaints($query)
    {
        return $query->where('type', 'complaint');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Methods
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    public function markAsUnread()
    {
        if ($this->is_read) {
            $this->update([
                'is_read' => false,
                'read_at' => null
            ]);
        }
    }

    // Accessors
    public function getIsUnreadAttribute()
    {
        return !$this->is_read;
    }

    public function getIsSuggestionAttribute()
    {
        return $this->type === 'suggestion';
    }

    public function getIsComplaintAttribute()
    {
        return $this->type === 'complaint';
    }

    public function getMessagePreviewAttribute()
    {
        return strlen($this->message) > 100
            ? substr($this->message, 0, 100) . '...'
            : $this->message;
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            if (empty($message->reference_number)) {
                $message->reference_number = 'MSG-' . strtoupper(uniqid());
            }
        });
    }
}
