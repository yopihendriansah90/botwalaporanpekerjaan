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
        // Menghapus delivery terjadwal lama agar laporan yang dikirim manual
        // tidak ikut terkirim lagi oleh scheduler.
        $report->deliveries()->whereIn('status', ['pending', 'queued'])->delete();

        $groups = $report->whatsappGroups()->where('is_active', true)->get();

        if ($groups->isEmpty() && $report->message_schedule_id) {
            $groups = $report->messageSchedule
                ->whatsappGroups()
                ->where('whatsapp_groups.is_active', true)
                ->get();
        }

        if ($groups->isEmpty()) {
            throw new RuntimeException('Laporan belum memiliki grup tujuan aktif.');
        }

        $message = $report->toWhatsappMessage();

        $report->update([
            'status' => 'pending',
            'delivery_mode' => 'manual',
            'sent_at' => null,
            'send_error' => null,
        ]);

        foreach ($groups as $group) {
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
