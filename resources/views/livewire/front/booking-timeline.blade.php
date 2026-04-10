<div class="min-h-screen pt-24 pb-12 px-4 sm:px-6 lg:px-8 bg-muted/20">
    <livewire:navbar />
    
    <div class="max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Jadwal Sewa iPhone</h1>
                <p class="mt-2 text-muted-foreground">Lihat ketersediaan unit untuk 7 hari ke depan.</p>
            </div>
            <a href="{{ route('public.booking') }}" wire:navigate class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8 py-2 font-medium">
                Sewa Sekarang
            </a>
        </div>

        <div class="bg-background rounded-2xl shadow-sm border border-border p-6 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="p-3 border-b border-border font-medium text-foreground min-w-[200px]">Unit</th>
                            <th class="p-3 border-b border-border font-medium text-muted-foreground text-center">Status 1-7 Hari ke Depan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $unit)
                        <tr class="border-b border-border last:border-b-0 relative">
                            <td class="p-3 align-middle bg-background z-10 w-[200px] border-r border-border/50">
                                <div class="font-semibold text-foreground">{{ $unit->seri }}</div>
                                <div class="text-xs text-muted-foreground">{{ $unit->warna }} • Rp {{ number_format($unit->harga_per_hari,0,',','.') }}/hari</div>
                            </td>
                            <td class="p-3 relative align-middle min-w-[500px]">
                                <!-- Dummy visual representation: simply list the active rentals for this unit -->
                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                    @if($unit->rentals->isEmpty())
                                        <div class="text-sm text-green-600 bg-green-50 border border-green-200 px-3 py-1 rounded-md whitespace-nowrap">
                                            🟢 Full Available
                                        </div>
                                    @else
                                        @foreach($unit->rentals as $rental)
                                            <div class="text-xs px-3 py-1.5 rounded-md border whitespace-nowrap flex flex-col 
                                                {{ $rental->status === 'pending' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 'bg-blue-50 border-blue-200 text-blue-800' }}">
                                                <span class="font-medium"> Booked: {{ $rental->nama }}</span>
                                                <span class="text-[10px] opacity-70">{{ \Carbon\Carbon::parse($rental->waktu_mulai)->format('d/m H:i') }} - {{ \Carbon\Carbon::parse($rental->waktu_selesai)->format('d/m H:i') }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
