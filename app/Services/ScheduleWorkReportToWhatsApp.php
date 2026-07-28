<?php

namespace App\Services;

use App\Models\MessageSchedule;
use App\Models\WorkReport;
use Carbon\Carbon;
use RuntimeException;

class ScheduleWorkReportToWhatsApp
{
    public function schedule(WorkReport $report): void
    {
        $schedule = $report->messageSchedule;

        if (! $schedule) {
            $schedule = MessageSchedule::query()
                ->where('is_active', true)
                ->whereHas('slots', fn ($query) => $query
                    ->where('weekday', $report->work_date->dayOfWeekIso)
                    ->where('is_active', true))
                ->first();
        }

        if (! $schedule) {
            throw new RuntimeException('Belum ada jadwal aktif untuk hari laporan ini.');
        }

        if (! $schedule->is_active) {
            throw new RuntimeException('Jadwal yang dipilih sedang nonaktif. Pilih jadwal aktif terlebih dahulu.');
        }

        $slot = $schedule->slots()
            ->where('weekday', $report->work_date->dayOfWeekIso)
            ->where('is_active', true)
            ->first();

        if (! $slot) {
            throw new RuntimeException("Jadwal {$schedule->name} belum memiliki jam untuk hari laporan ini.");
        }

        $groups = $schedule->whatsappGroups()->where('is_active', true)->get();

        if ($groups->isEmpty()) {
            throw new RuntimeException('Jadwal belum memiliki grup WhatsApp aktif.');
        }

        $scheduledAt = Carbon::parse(
            $report->work_date->format('Y-m-d') . ' ' . $slot->send_time,
            $schedule->timezone,
        )->utc();

        $report->update([
            'message_schedule_id' => $schedule->id,
            'delivery_mode' => 'scheduled',
            'status' => 'scheduled',
            'sent_at' => null,
            'send_error' => null,
        ]);

        app(CancelPendingWorkReportDeliveries::class)->cancel(
            $report,
            'Pengiriman lama dibatalkan karena laporan dijadwalkan ulang.',
        );

        foreach ($groups as $group) {
            $report->deliveries()->create([
                'whatsapp_group_id' => $group->id,
                'whatsapp_connection_id' => $group->whatsapp_connection_id,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
            ]);
        }
    }
}
