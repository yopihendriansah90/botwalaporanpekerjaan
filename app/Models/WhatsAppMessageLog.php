<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToTenant;

class WhatsAppMessageLog extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'whatsapp_message_logs';

    protected $fillable = [
        'whatsapp_connection_id',
        'tenant_id',
        'work_report_id',
        'whatsapp_group_id',
        'recipient_jid',
        'message',
        'status',
        'provider_message_id',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConnection::class, 'whatsapp_connection_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(WorkReport::class, 'work_report_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(WorkReportDelivery::class, 'whatsapp_message_log_id');
    }
}
