<?php

namespace App\Filament\Resources\StaffLogs\Tables;

use App\Models\StaffLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StaffLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->description(fn (StaffLog $record): string => $record->created_at->diffForHumans())
                    ->sortable(),

                TextColumn::make('formatted_actor')
                    ->label('Pelaku Aksi')
                    ->description(fn (StaffLog $record): string => $record->formatted_role)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('action')
                    ->label('Jenis Kegiatan')
                    ->formatStateUsing(fn (StaffLog $record): string => $record->action_badge['label'])
                    ->badge()
                    ->color(fn (StaffLog $record): string => match (true) {
                        str_contains($record->action, 'paid') => 'success',
                        str_contains($record->action, 'handover') => 'info',
                        str_contains($record->action, 'complete') => 'primary',
                        str_contains($record->action, 'cancel') => 'danger',
                        str_contains($record->action, 'edit') || str_contains($record->action, 'update') => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('human_description')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        return $query->where('description', 'like', "%{$search}%");
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran Akun')
                    ->options([
                        'admin' => 'Administrator',
                        'staff' => 'Staf Kasir',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('user', function ($q) use ($data) {
                                $q->where('role', $data['value']);
                            });
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
