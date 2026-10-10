<?php

namespace App\Filament\Resources\StaffLogs\Pages;

use App\Filament\Resources\StaffLogs\StaffLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffLog extends EditRecord
{
    protected static string $resource = StaffLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
