<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReportDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id', 'whatsapp_group_id', 'whatsapp_connection_id',
        'scheduled_at', 'status', 'dispatched_at', 'whatsapp_message_log_id', 'error_message',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'dispatched_at' => 'datetime'];
    }

    public function report(): BelongsTo { return $this->belongsTo(WorkReport::class, 'work_report_id'); }
    public function group(): BelongsTo { return $this->belongsTo(WhatsAppGroup::class, 'whatsapp_group_id'); }
    public function log(): BelongsTo { return $this->belongsTo(WhatsAppMessageLog::class, 'whatsapp_message_log_id'); }
}
