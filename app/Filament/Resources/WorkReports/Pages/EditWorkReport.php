<?php

namespace App\Filament\Resources\WorkReports\Pages;

use App\Filament\Resources\WorkReports\WorkReportResource;
use App\Services\DispatchWorkReportToWhatsApp;
use App\Services\ScheduleWorkReportToWhatsApp;
use App\Services\CancelPendingWorkReportDeliveries;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditWorkReport extends EditRecord
{
    protected static string $resource = WorkReportResource::class;

    protected static ?string $title = 'Edit Laporan Pekerjaan';

    public static bool $formActionsAreSticky = false;

    public bool $shouldSend = false;
    public bool $shouldSchedule = false;

    protected function getFormActions(): array
    {
        $actions = [];

        if ($this->record?->status !== 'sent') {
            $actions[] = Action::make('save_draft')
                ->label('Simpan Draf')
                ->color('gray')
                ->action('saveDraft');
        }

        $actions[] = Action::make('save_and_send')
            ->label('Simpan & Kirim')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->action('saveAndSend');

        $actions[] = Action::make('save_and_schedule')
            ->label('Simpan & Jadwalkan')
            ->icon('heroicon-o-calendar-days')
            ->color('warning')
            ->action('saveAndSchedule');

        $actions[] = $this->getCancelFormAction();

        return $actions;
    }

    protected function getRedirectUrl(): ?string
    {
        return WorkReportResource::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function saveDraft(): void
    {
        $this->shouldSend = false;
        $this->shouldSchedule = false;
        $this->save();
    }

    public function saveAndSend(): void
    {
        $this->shouldSend = true;
        $this->shouldSchedule = false;
        $this->save();
    }

    public function saveAndSchedule(): void
    {
        if (blank($this->data['message_schedule_id'] ?? null)) {
            Notification::make()
                ->title('Jadwal pengiriman belum dipilih')
                ->body('Pilih jadwal pengiriman terlebih dahulu sebelum menyimpan laporan terjadwal.')
                ->warning()
                ->send();

            return;
        }

        $this->shouldSend = false;
        $this->shouldSchedule = true;
        $this->save();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldSend && ! $this->shouldSchedule) {
            app(CancelPendingWorkReportDeliveries::class)->cancel($this->record);

            Notification::make()
                ->title('Draf laporan berhasil diperbarui')
                ->success()
                ->send();

            return;
        }

        try {
            if ($this->shouldSchedule) {
                app(ScheduleWorkReportToWhatsApp::class)->schedule($this->record);
            } else {
                app(DispatchWorkReportToWhatsApp::class)->dispatch($this->record);
            }

            Notification::make()
                ->title('Laporan masuk antrean pengiriman')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $this->record->update([
                'status' => 'failed',
                'send_error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Laporan gagal masuk antrean')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
