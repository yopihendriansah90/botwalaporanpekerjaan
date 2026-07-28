<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToTenant;

class WorkReport extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'message_schedule_id',
        'work_date',
        'officer_name',
        'tasks',
        'status',
        'delivery_mode',
        'sent_at',
        'send_error',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'tasks' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messageSchedule(): BelongsTo
    {
        return $this->belongsTo(MessageSchedule::class, 'message_schedule_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WorkReportDelivery::class, 'work_report_id');
    }

    public function whatsappGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppGroup::class,
            'work_report_whatsapp_group',
            'work_report_id',
            'whatsapp_group_id',
        )->withPivot('tenant_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(WhatsAppMessageLog::class, 'work_report_id');
    }

    public function toWhatsappMessage(): string
    {
        $date = $this->work_date?->locale('id')->translatedFormat('d F Y');
        $lines = [
            'LAPORAN PEKERJAAN',
            "Tanggal: {$date}",
            "Nama: {$this->officer_name}",
            '',
            'Pekerjaan:',
        ];

        foreach ($this->tasks ?? [] as $index => $task) {
            $description = trim((string) ($task['description'] ?? ''));

            if ($description === '') {
                continue;
            }

            $number = (int) ($task['number'] ?? ($index + 1));
            $lines[] = "{$number}. {$description}";

            if (filled($task['media_url'] ?? null)) {
                $lines[] = "  {$task['media_url']}";
            }
        }

        return implode("\n", $lines);
    }
}
