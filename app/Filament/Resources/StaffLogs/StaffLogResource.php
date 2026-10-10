<?php

namespace App\Filament\Resources\StaffLogs;

use App\Filament\Resources\StaffLogs\Pages\CreateStaffLog;
use App\Filament\Resources\StaffLogs\Pages\EditStaffLog;
use App\Filament\Resources\StaffLogs\Pages\ListStaffLogs;
use App\Filament\Resources\StaffLogs\Pages\ViewStaffLog;
use App\Filament\Resources\StaffLogs\Schemas\StaffLogForm;
use App\Filament\Resources\StaffLogs\Schemas\StaffLogInfolist;
use App\Filament\Resources\StaffLogs\Tables\StaffLogsTable;
use App\Models\StaffLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffLogResource extends Resource
{
    protected static ?string $model = StaffLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StaffLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaffLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffLogs::route('/'),
            'create' => CreateStaffLog::route('/create'),
            'view' => ViewStaffLog::route('/{record}'),
            'edit' => EditStaffLog::route('/{record}/edit'),
        ];
    }
}
