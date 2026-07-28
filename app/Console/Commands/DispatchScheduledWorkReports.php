<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessageLog;
use App\Models\WorkReportDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Services\TenantContext;

class DispatchScheduledWorkReports extends Command
{
    protected $signature = 'reports:dispatch-scheduled';

    protected $description = 'Masukkan laporan terjadwal yang sudah waktunya ke antrean WhatsApp';

    public function handle(): int
    {
        $deliveries = WorkReportDelivery::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now('UTC'))
            ->limit(100)
            ->get();

        foreach ($deliveries as $delivery) {
            try {
                app(TenantContext::class)->run((int) $delivery->tenant_id, function () use ($delivery): void {
                    DB::transaction(function () use ($delivery): void {
                        $lockedDelivery = WorkReportDelivery::query()
                            ->whereKey($delivery->id)
                            ->lockForUpdate()
                            ->with(['report', 'group'])
                            ->first();

                        if (! $lockedDelivery || $lockedDelivery->status !== 'pending') {
                            return;
                        }

                        $log = WhatsAppMessageLog::create([
                            'whatsapp_connection_id' => $lockedDelivery->whatsapp_connection_id,
                            'work_report_id' => $lockedDelivery->work_report_id,
                            'whatsapp_group_id' => $lockedDelivery->whatsapp_group_id,
                            'recipient_jid' => $lockedDelivery->group->jid,
                            'message' => $lockedDelivery->report->toWhatsappMessage(),
                            'status' => 'pending',
                        ]);

                        $lockedDelivery->update([
                            'status' => 'queued',
                            'dispatched_at' => now(),
                            'whatsapp_message_log_id' => $log->id,
                        ]);

                        $lockedDelivery->report->update(['status' => 'pending']);
                        SendWhatsAppMessage::dispatch($log)->afterCommit();
                    });
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
