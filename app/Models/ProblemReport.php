<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProblemReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
