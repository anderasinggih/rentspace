<?php

namespace App\Filament\Resources\StaffLogs\Pages;

use App\Filament\Resources\StaffLogs\StaffLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffLogs extends ListRecords
{
    protected static string $resource = StaffLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
