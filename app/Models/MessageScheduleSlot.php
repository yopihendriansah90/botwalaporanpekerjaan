<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = ['message_schedule_id', 'weekday', 'send_time', 'is_active'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MessageSchedule::class, 'message_schedule_id');
    }
}
