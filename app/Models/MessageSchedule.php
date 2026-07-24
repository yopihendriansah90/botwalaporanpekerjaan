<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageSchedule extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'timezone', 'is_active', 'slots'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function slots(): HasMany
    {
        return $this->hasMany(MessageScheduleSlot::class);
    }

    public function whatsappGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppGroup::class,
            'message_schedule_whatsapp_group',
            'message_schedule_id',
            'whatsapp_group_id',
        );
    }

    public function workReports(): HasMany
    {
        return $this->hasMany(WorkReport::class);
    }
}
