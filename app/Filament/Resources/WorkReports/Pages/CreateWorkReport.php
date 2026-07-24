<?php

namespace App\Filament\Resources\WorkReports\Pages;

use App\Filament\Resources\WorkReports\WorkReportResource;
use App\Services\DispatchWorkReportToWhatsApp;
use App\Services\ScheduleWorkReportToWhatsApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Throwable;

class CreateWorkReport extends CreateRecord
{
    protected static string $resource = WorkReportResource::class;

    protected static ?string $title = 'Membuat Laporan';

    public static bool $formActionsAreSticky = false;

    public bool $shouldSend = false;
    public bool $shouldSchedule = false;

    protected function getFormActions(): array
    {
        return [
            Action::make('save_draft')
                ->label('Simpan Draf')
                ->color('gray')
                ->action('saveDraft'),
            Action::make('save_and_send')
                ->label('Simpan & Kirim')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->action('saveAndSend'),
            Action::make('save_and_schedule')
                ->label('Simpan & Jadwalkan')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->action('saveAndSchedule'),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return WorkReportResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    public function saveDraft(): void
    {
        $this->shouldSend = false;
        $this->shouldSchedule = false;
        $this->create();
    }

    public function saveAndSend(): void
    {
        $this->shouldSend = true;
        $this->shouldSchedule = false;
        $this->create();
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
        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->shouldSend && ! $this->shouldSchedule) {
            $this->record->deliveries()->whereIn('status', ['pending', 'queued'])->delete();

            Notification::make()
                ->title('Laporan berhasil disimpan sebagai draf')
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
