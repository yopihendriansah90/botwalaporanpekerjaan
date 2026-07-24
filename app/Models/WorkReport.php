<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'officer_name',
        'tasks',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'tasks' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toWhatsappMessage(): string
    {
        $date = $this->work_date?->locale('id')->translatedFormat('d F Y');
        $lines = [
            'LAPORAN PEKERJAAN',
            "Tanggal: {$date}",
            "Nama: {$this->officer_name}",
            '',
            'Pekerjaan selesai:',
        ];

        foreach ($this->tasks ?? [] as $task) {
            $description = trim((string) ($task['description'] ?? ''));

            if ($description === '') {
                continue;
            }

            $lines[] = "- {$description}";

            if (filled($task['media_url'] ?? null)) {
                $lines[] = "  {$task['media_url']}";
            }
        }

        return implode("\n", $lines);
    }
}
