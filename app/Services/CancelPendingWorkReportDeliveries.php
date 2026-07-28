<?php

namespace App\Services;

use App\Models\WorkReport;

class CancelPendingWorkReportDeliveries
{
    public function cancel(WorkReport $report, string $reason = 'Pengiriman dibatalkan karena laporan diperbarui.'): void
    {
        $report->messageLogs()
            ->whereIn('status', ['pending', 'queued'])
            ->update([
                'status' => 'cancelled',
                'error_message' => $reason,
            ]);

        $report->deliveries()
            ->whereIn('status', ['pending', 'queued'])
            ->update([
                'status' => 'cancelled',
                'error_message' => $reason,
            ]);
    }
}
