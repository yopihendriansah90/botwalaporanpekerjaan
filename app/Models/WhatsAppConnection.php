<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConnection extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_connections';

    protected $fillable = [
        'name',
        'phone',
        'status',
        'last_connected_at',
    ];

    protected function casts(): array
    {
        return [
            'last_connected_at' => 'datetime',
        ];
    }

    public function groups(): HasMany
    {
        return $this->hasMany(WhatsAppGroup::class, 'whatsapp_connection_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(WhatsAppMessageLog::class, 'whatsapp_connection_id');
    }
}
