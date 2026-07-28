<?php

namespace App\Services;

use App\Models\MessageSchedule;
use App\Models\WhatsAppConnection;
use App\Models\WhatsAppGroup;
use App\Models\WhatsAppMessageLog;
use App\Models\WorkReport;
use App\Models\WorkReportDelivery;
use RuntimeException;

class WhatsAppTenantIntegrity
{
    public function assertReport(WorkReport $report, int $tenantId): void
    {
        $this->assertTenant('laporan', $report->tenant_id, $tenantId);
    }

    public function assertSchedule(MessageSchedule $schedule, int $tenantId): void
    {
        $this->assertTenant('jadwal', $schedule->tenant_id, $tenantId);
    }

    public function assertGroup(WhatsAppGroup $group, int $tenantId): void
    {
        $this->assertTenant('grup WhatsApp', $group->tenant_id, $tenantId);

        $connection = WhatsAppConnection::query()->find($group->whatsapp_connection_id);

        if (! $connection) {
            throw new RuntimeException('Koneksi WhatsApp grup tidak ditemukan.');
        }

        $this->assertTenant('koneksi WhatsApp', $connection->tenant_id, $tenantId);
    }

    public function assertDelivery(WorkReportDelivery $delivery): void
    {
        $tenantId = (int) $delivery->tenant_id;
        $this->assertTenant('delivery', $delivery->tenant_id, $tenantId);

        $report = $delivery->report;
        $group = $delivery->group;
        $connection = WhatsAppConnection::query()->find($delivery->whatsapp_connection_id);

        if (! $report || ! $group || ! $connection) {
            throw new RuntimeException('Data delivery WhatsApp tidak lengkap.');
        }

        $this->assertReport($report, $tenantId);
        $this->assertGroup($group, $tenantId);
        $this->assertTenant('koneksi WhatsApp', $connection->tenant_id, $tenantId);

        if ((int) $group->whatsapp_connection_id !== (int) $delivery->whatsapp_connection_id) {
            throw new RuntimeException('Grup delivery tidak sesuai dengan koneksinya.');
        }
    }

    public function assertLog(WhatsAppMessageLog $log): void
    {
        $tenantId = (int) $log->tenant_id;
        $this->assertTenant('log WhatsApp', $log->tenant_id, $tenantId);

        $group = $log->group;
        $connection = WhatsAppConnection::query()->find($log->whatsapp_connection_id);
        $report = $log->report;

        if (! $group || ! $connection) {
            throw new RuntimeException('Data log WhatsApp tidak lengkap.');
        }

        $this->assertGroup($group, $tenantId);
        $this->assertTenant('koneksi WhatsApp', $connection->tenant_id, $tenantId);

        if ((int) $group->whatsapp_connection_id !== (int) $log->whatsapp_connection_id) {
            throw new RuntimeException('Grup log tidak sesuai dengan koneksinya.');
        }

        if ($report) {
            $this->assertReport($report, $tenantId);
        }

        if ((string) $log->recipient_jid !== (string) $group->jid) {
            throw new RuntimeException('Tujuan log WhatsApp tidak sesuai dengan grup tenant.');
        }
    }

    private function assertTenant(string $label, mixed $actualTenantId, int $expectedTenantId): void
    {
        if ((int) $actualTenantId !== $expectedTenantId || ! $expectedTenantId) {
            throw new RuntimeException("Data {$label} tidak berada pada tenant yang sama.");
        }
    }
}
