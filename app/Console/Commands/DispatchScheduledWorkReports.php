<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessageLog;
use App\Models\WorkReportDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DispatchScheduledWorkReports extends Command
{
    protected $signature = 'reports:dispatch-scheduled';

    protected $description = 'Masukkan laporan terjadwal yang sudah waktunya ke antrean WhatsApp';

    public function handle(): int
    {
        $deliveries = WorkReportDelivery::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->with(['report', 'group'])
            ->limit(100)
            ->get();

        foreach ($deliveries as $delivery) {
            try {
                DB::transaction(function () use ($delivery): void {
                    $delivery->refresh();

                    if ($delivery->status !== 'pending') {
                        return;
                    }

                    $log = WhatsAppMessageLog::create([
                        'whatsapp_connection_id' => $delivery->whatsapp_connection_id,
                        'work_report_id' => $delivery->work_report_id,
                        'whatsapp_group_id' => $delivery->whatsapp_group_id,
                        'recipient_jid' => $delivery->group->jid,
                        'message' => $delivery->report->toWhatsappMessage(),
                        'status' => 'pending',
                    ]);

                    $delivery->update([
                        'status' => 'queued',
                        'dispatched_at' => now(),
                        'whatsapp_message_log_id' => $log->id,
                    ]);

                    $delivery->report->update(['status' => 'pending']);
                    SendWhatsAppMessage::dispatch($log);
                });
            } catch (Throwable $exception) {
                $delivery->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("{$deliveries->count()} delivery diproses.");

        return self::SUCCESS;
    }
}
