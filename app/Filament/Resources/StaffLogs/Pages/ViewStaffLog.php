<?php

namespace App\Filament\Resources\StaffLogs\Pages;

use App\Filament\Resources\StaffLogs\StaffLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffLog extends ViewRecord
{
    protected static string $resource = StaffLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
