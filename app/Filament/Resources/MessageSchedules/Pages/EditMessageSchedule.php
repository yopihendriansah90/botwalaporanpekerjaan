<?php

namespace App\Filament\Resources\MessageSchedules\Pages;

use App\Filament\Resources\MessageSchedules\MessageScheduleResource;
use App\Models\MessageScheduleSlot;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMessageSchedule extends EditRecord
{
    protected static string $resource = MessageScheduleResource::class;

    protected static ?string $title = 'Edit Jadwal Pengiriman';

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Simpan Perubahan'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }

    protected array $groupIds = [];
    protected array $slots = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['whatsapp_group_ids'] = $this->record->whatsappGroups()->pluck('whatsapp_groups.id')->all();
        $data['slots'] = $this->record->slots()->orderBy('weekday')->get(['weekday', 'send_time'])->map(fn ($slot): array => [
            'weekday' => $slot->weekday,
            'send_time' => substr((string) $slot->send_time, 0, 5),
        ])->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->groupIds = array_map('intval', $data['whatsapp_group_ids'] ?? []);
        $this->slots = $data['slots'] ?? [];
        unset($data['whatsapp_group_ids'], $data['slots']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->whatsappGroups()->syncWithPivotValues($this->groupIds, [
            'tenant_id' => $this->record->tenant_id,
        ]);
        $this->record->slots()->delete();

        foreach ($this->slots as $slot) {
            MessageScheduleSlot::create([
                'message_schedule_id' => $this->record->id,
                'weekday' => $slot['weekday'],
                'send_time' => $slot['send_time'],
                'is_active' => true,
            ]);
        }

        Notification::make()->title('Jadwal berhasil diperbarui')->success()->send();
    }

    protected function getRedirectUrl(): ?string
    {
        return MessageScheduleResource::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }
}
