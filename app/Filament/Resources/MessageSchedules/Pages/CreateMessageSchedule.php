<?php

namespace App\Filament\Resources\MessageSchedules\Pages;

use App\Filament\Resources\MessageSchedules\MessageScheduleResource;
use App\Models\MessageScheduleSlot;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMessageSchedule extends CreateRecord
{
    protected static string $resource = MessageScheduleResource::class;

    protected static ?string $title = 'Membuat Jadwal Pengiriman';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Simpan Jadwal'),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->groupIds = array_map('intval', $data['whatsapp_group_ids'] ?? []);
        $this->slots = $data['slots'] ?? [];
        unset($data['whatsapp_group_ids'], $data['slots']);

        return $data;
    }

    protected array $groupIds = [];
    protected array $slots = [];

    protected function afterCreate(): void
    {
        $this->record->whatsappGroups()->sync($this->groupIds);

        foreach ($this->slots as $slot) {
            MessageScheduleSlot::create([
                'message_schedule_id' => $this->record->id,
                'weekday' => $slot['weekday'],
                'send_time' => $slot['send_time'],
                'is_active' => true,
            ]);
        }

        Notification::make()->title('Jadwal berhasil disimpan')->success()->send();
    }

    protected function getRedirectUrl(): string
    {
        return MessageScheduleResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
