<div class="py-12 px-4 sm:px-6 lg:px-8 bg-muted/20 min-h-[calc(100vh-4rem)]">
    
    <div class="max-w-3xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Formulir Penyewaan</h1>
            <a href="{{ route('public.timeline') }}" wire:navigate class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 text-sm font-medium shadow-sm w-full sm:w-auto text-center transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                Lihat Kalender Jadwal
            </a>
        </div>

        <div class="bg-background rounded-2xl shadow-sm border border-border p-6 sm:p-8">
            <form wire:submit.prevent="submit" class="space-y-8">
                
                <!-- Jadwal Sewa -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">1. Jadwal Peminjaman</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none">Waktu Mulai</label>
                            <input type="datetime-local" wire:model.live="waktu_mulai" class="mt-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('waktu_mulai') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Waktu Selesai</label>
                            <input type="datetime-local" wire:model.live="waktu_selesai" class="mt-2 flex h-10 w-full rounded-md border border-input bg-background px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('waktu_selesai') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Pilihan Unit -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">2. Pilih Unit Tersedia</h2>
                    @if($waktu_mulai && $waktu_selesai)
                        @if(count($available_units) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($available_units as $unit)
                                <label class="relative flex cursor-pointer rounded-lg border bg-background p-4 shadow-sm focus:outline-none">
                                    <input type="radio" wire:model.live="unit_id" value="{{ $unit->id }}" class="sr-only" aria-labelledby="unit-label-{{ $unit->id }}">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span id="unit-label-{{ $unit->id }}" class="block font-medium text-foreground">
                                                {{ $unit->seri }}
                                                @if($unit->kategori === 'gear')
                                                    <x-ui.badge variant="purple" class="ml-1 text-[10px] uppercase font-bold">ALAT</x-ui.badge>
                                                @endif
                                            </span>
                                            @if($unit->warna || $unit->memori)
                                                <span class="mt-1 flex items-center text-sm text-muted-foreground">{{ $unit->warna }}@if($unit->warna && $unit->memori) • @endif{{ $unit->memori }}</span>
                                            @endif
                                            <span class="mt-2 font-semibold text-primary">Rp {{ number_format($unit->harga_per_hari,0,',','.') }}/hari</span>
                                        </span>
                                    </span>
                                    @if($unit_id == $unit->id)
                                    <svg class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                            @error('unit_id') <span class="text-xs text-red-500 block mt-2">{{ $message }}</span> @enderror
                        @else
                            <div class="p-4 bg-red-50 border border-red-200 rounded-md text-red-600 text-sm">
                                Menyesal sekali, tidak ada unit yang tersedia untuk rentang waktu yang dipilih. Silakan coba waktu lain.
                            </div>
                        @endif
                    @else
                        <div class="p-4 bg-muted border border-border rounded-md text-muted-foreground text-sm">
                            Silakan isi Tanggal & Jam mulai serta selesai terlebih dahulu untuk mengecek ketersediaan.
                        </div>
                    @endif
                </div>

                <!-- Rincian Harga -->
                @if($unit_id && $waktu_mulai && $waktu_selesai)
                <div class="bg-primary/5 rounded-xl p-6 border border-primary/20">
                    <h3 class="font-bold text-lg mb-4 text-foreground">Rincian Tagihan Kalkulasi Otomatis</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal Sewa</span>
                            <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($potongan_diskon > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Diskon / Promo</span>
                            <span class="font-medium">- Rp {{ number_format($potongan_diskon, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-muted-foreground">
                            <span>Kode Unik <span class="text-[10px]">(Membantu konfirmasi transfer)</span></span>
                            <span class="font-medium">+ Rp {{ $kode_unik }}</span>
                        </div>
                        <div class="pt-4 border-t border-border flex justify-between items-center">
                            <span class="font-bold text-base text-foreground">Grand Total</span>
                            <span class="font-black text-2xl text-primary">Rp {{ number_format($grand_total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Data Diri -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">3. Data Diri Penyewa</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium leading-none">NIK (Nomor Induk Kependudukan)</label>
                            <input type="text" wire:model="nik" class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('nik') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Nama Lengkap Sesuai KTP</label>
                            <input type="text" wire:model="nama" class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('nama') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-sm font-medium leading-none">Nomor Telepon / WhatsApp</label>
                            <input type="text" wire:model="no_wa" class="mt-2 flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            @error('no_wa') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-sm font-medium leading-none">Alamat Domisili lengkap</label>
                            <textarea wire:model="alamat" rows="3" class="mt-2 flex w-full rounded-md border border-input bg-transparent px-3 py-2 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                            @error('alamat') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Persetujuan Pengguna -->
                <div class="rounded-xl border border-border bg-muted/30 p-5 space-y-3">
                    <h3 class="font-semibold text-sm text-foreground">Syarat & Ketentuan Penyewaan</h3>
                    @php
                        $defaultTerms = "1. Penyewa wajib menjaga iPhone yang disewa dan bertanggung jawab atas kerusakan atau kehilangan selama masa sewa.\n2. Pembayaran dilakukan di awal sebelum unit diserahkan, sesuai total tagihan yang tertera.\n3. Keterlambatan pengembalian melewati batas toleransi waktu akan dikenakan denda yang ditentukan oleh pengelola.\n4. Pengelola berhak menolak penyewaan apabila dokumen identitas (NIK/KTP) tidak valid atau tidak sesuai.\n5. Pemesanan yang sudah terkonfirmasi tidak dapat dibatalkan secara sepihak oleh penyewa.";
                        $termsRaw = \App\Models\Setting::getVal('terms_conditions', $defaultTerms);
                        $termLines = array_filter(explode("\n", $termsRaw));
                    @endphp
                    <ul class="text-xs text-muted-foreground space-y-1.5 list-disc list-inside leading-relaxed">
                        @foreach($termLines as $line)
                            <li>{{ trim($line) }}</li>
                        @endforeach
                    </ul>
                    <label class="flex items-start gap-3 cursor-pointer mt-1">
                        <input type="checkbox" wire:model="agree" id="agree_terms" class="mt-0.5 h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-primary">
                        <span class="text-sm font-medium text-foreground">Saya telah membaca dan <strong>menyetujui</strong> seluruh syarat & ketentuan penyewaan di atas.</span>
                    </label>
                    @error('agree') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-12 px-8.5 font-bold text-lg">
                    Sewa & Lanjut Pembayaran
                </button>
            </form>
        </div>
    </div>
</div>
