<?php

namespace App\Filament\Resources\StaffLogs\Schemas;

use App\Models\StaffLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Aktivitas')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('formatted_actor')
                                    ->label('Pelaku Aksi'),
                                TextEntry::make('formatted_role')
                                    ->label('Peran Akun')
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label('Waktu Kejadian')
                                    ->dateTime('d F Y, H:i:s')
                                    ->suffix(' WIB'),
                            ]),

                        TextEntry::make('action')
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

                        TextEntry::make('human_description')
                            ->label('Ringkasan Narasi Kegiatan'),

                        TextEntry::make('ip_address')
                            ->label('Alamat IP Perangkat')
                            ->placeholder('Tidak tercatat'),
                    ]),

                Section::make('Rincian Perubahan Data')
                    ->description('Hanya menampilkan field data yang nilainya diubah oleh staf.')
                    ->schema([
                        TextEntry::make('changed_fields')
                            ->label('Daftar Perubahan Nilai')
                            ->formatStateUsing(function (StaffLog $record) {
                                $changes = $record->changed_fields;
                                if (empty($changes)) {
                                    return 'Tidak ada perubahan nilai pada rincian data ini.';
                                }

                                $lines = [];
                                foreach ($changes as $c) {
                                    $lines[] = "• {$c['label']}: {$c['before']} → {$c['after']}";
                                }
                                return implode("\n", $lines);
                            })
                            ->lineClamp(10),
                    ]),
            ]);
    }
}
