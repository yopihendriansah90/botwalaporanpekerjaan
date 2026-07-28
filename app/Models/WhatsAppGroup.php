<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\BelongsToTenant;

class WhatsAppGroup extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_groups';

    protected $fillable = [
        'whatsapp_connection_id',
        'jid',
        'name',
        'participants_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConnection::class, 'whatsapp_connection_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(WhatsAppMessageLog::class);
    }

    public function workReports(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkReport::class,
            'work_report_whatsapp_group',
            'whatsapp_group_id',
            'work_report_id',
        )->withPivot('tenant_id');
    }
}
