<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessageLog;
use App\Models\WorkReport;
use RuntimeException;

class DispatchWorkReportToWhatsApp
{
    public function dispatch(WorkReport $report): void
    {
        $tenantId = (int) $report->tenant_id;
        $integrity = app(WhatsAppTenantIntegrity::class);
        $integrity->assertReport($report, $tenantId);

        $groups = $report->whatsappGroups()
            ->where('whatsapp_groups.tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        if ($groups->isEmpty() && $report->message_schedule_id) {
            $schedule = $report->messageSchedule;

            if (! $schedule) {
                throw new RuntimeException('Jadwal laporan tidak ditemukan.');
            }

            $integrity->assertSchedule($schedule, $tenantId);

            $groups = $schedule
                ->whatsappGroups()
                ->where('whatsapp_groups.tenant_id', $tenantId)
                ->where('whatsapp_groups.is_active', true)
                ->get();
        }

        if ($groups->isEmpty()) {
            throw new RuntimeException('Laporan belum memiliki grup tujuan aktif.');
        }

        $message = $report->toWhatsappMessage();

        app(CancelPendingWorkReportDeliveries::class)->cancel(
            $report,
            'Pengiriman lama dibatalkan karena laporan dikirim ulang secara manual.',
        );

        $report->update([
            'status' => 'pending',
            'delivery_mode' => 'manual',
            'sent_at' => null,
            'send_error' => null,
        ]);

        foreach ($groups as $group) {
            $integrity->assertGroup($group, $tenantId);

            $log = WhatsAppMessageLog::create([
                'whatsapp_connection_id' => $group->whatsapp_connection_id,
                'work_report_id' => $report->id,
                'whatsapp_group_id' => $group->id,
                'recipient_jid' => $group->jid,
                'message' => $message,
                'status' => 'pending',
            ]);

            SendWhatsAppMessage::dispatch($log);
        }
    }
}
