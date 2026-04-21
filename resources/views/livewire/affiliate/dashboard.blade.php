<div class="min-h-screen bg-background pb-12" x-data="{ activeTab: @entangle('tab').live }">
    {{-- Header Section --}}
    <div class="bg-background">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-2">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl sm:text-2xl font-black text-foreground tracking-tight uppercase truncate">
                        {{ Auth::user()->name }}
                    </h1>
                </div>
                <button wire:click="logout" class="shrink-0 px-2.5 sm:px-4 py-1.5 sm:py-2 bg-red-600 hover:bg-red-700 text-white text-[10px] sm:text-xs font-black rounded-lg transition-all shadow-sm active:scale-95">
                    Logout
                </button>
                
                @if($profileCompleteness < 100)
                <div class="flex items-center gap-3">
                    <div class="flex flex-col md:items-end gap-1">
                        <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-wider">Lengkapi Profil</span>
                        <div class="w-32 h-1 bg-muted rounded-full overflow-hidden border border-border">
                            <div class="h-full bg-primary transition-all duration-700" style="width: {{ $profileCompleteness }}%"></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        {{-- Status Alerts --}}
        {{-- Status Alerts & Pending Lock --}}
        @if(auth()->user()->affiliateProfile?->status === 'pending')
            <div class="max-w-2xl mx-auto mt-12 mb-20">
                <div class="rounded-2xl border border-border bg-card shadow-xl overflow-hidden">
                    <div class="p-8 md:p-12 text-center space-y-6">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-yellow-50 border-4 border-yellow-100 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-600 animate-pulse"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        </div>
                        
                        <div class="space-y-3">
                            <h2 class="text-2xl font-bold text-foreground tracking-tight">Verifikasi Menunggu</h2>
                            <p class="text-sm text-muted-foreground leading-relaxed max-w-md mx-auto">
                                Akun Anda sedang dalam antrean peninjauan oleh tim Admin. Dashboard dan fitur komisi akan terbuka otomatis segera setelah akun Anda disetujui.
                            </p>
                        </div>

                        <div class="pt-6 space-y-4 max-w-xs mx-auto">
                            <a href="{{ $this->getRequestVerificationWaLink() }}" target="_blank"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-primary text-primary-foreground shadow-lg h-12 px-8 text-sm font-bold hover:scale-[1.02] transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Minta Verifikasi (WA)
                            </a>
                            <p class="text-[10px] text-muted-foreground font-medium flex items-center justify-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                Estimasi verifikasi: 1x24 jam kerja
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(auth()->user()->affiliateProfile?->status === 'rejected')
            <div class="max-w-2xl mx-auto mt-12 mb-20">
                <div class="rounded-2xl border border-red-200 bg-red-50/30 shadow-xl overflow-hidden">
                    <div class="p-8 md:p-12 text-center space-y-6">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-red-100 border-4 border-red-200 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-700"><path d="m15 9-6 6"/><path d="m9 9 6 6"/><circle cx="12" cy="12" r="10"/></svg>
                        </div>
                        
                        <div class="space-y-3">
                            <h2 class="text-2xl font-bold text-red-800 tracking-tight">Akun Belum Disetujui</h2>
                            <p class="text-sm text-red-700/80 leading-relaxed max-w-md mx-auto font-medium">
                                Mohon maaf, pendaftaran Anda sebagai Affiliator tidak dapat kami setujui saat ini. Hal ini mungkin dikarenakan data yang kurang lengkap atau tidak sesuai.
                            </p>
                        </div>

                        <div class="pt-6 space-y-4 max-w-xs mx-auto">
                            <a href="{{ $this->getContactAdminWaLink() }}" target="_blank"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-red-800 text-white shadow-lg h-12 px-8 text-sm font-bold hover:bg-red-900 transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Hubungi Admin
                            </a>
                            <p class="text-[10px] text-red-600/70 font-medium">
                                Silakan hubungi admin untuk informasi lebih lanjut.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(auth()->user()->affiliateProfile?->status === 'inactive')
            <div class="max-w-2xl mx-auto mt-12 mb-20">
                <div class="rounded-2xl border border-border bg-card shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="p-8 md:p-12 text-center space-y-6">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 border-4 border-slate-100 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-900"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        
                        <div class="space-y-3">
                            <h2 class="text-2xl font-bold text-foreground tracking-tight">Akun Dinonaktifkan</h2>
                            <div class="p-4 bg-muted/30 border border-border rounded-lg max-w-md mx-auto">
                                <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-2">Alasan Deaktivasi:</p>
                                <p class="text-sm text-foreground leading-relaxed font-medium italic">
                                    "{{ auth()->user()->affiliateProfile?->status_note ?? 'Akun Anda telah dinonaktifkan oleh Admin.' }}"
                                </p>
                            </div>
                        </div>

                        <div class="pt-6 space-y-4 max-w-xs mx-auto">
                            <a href="{{ $this->getContactAdminWaLink() }}" target="_blank"
                                class="w-full flex items-center justify-center gap-2 rounded-xl bg-foreground text-background shadow-lg h-12 px-8 text-sm font-bold hover:bg-foreground/90 transition-all active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Hubungi Admin
                            </a>
                            <p class="text-[10px] text-muted-foreground font-medium leading-relaxed">
                                Fitur referral dan komisi tidak tersedia sementara akun nonaktif.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @else

        {{-- Core Stats Section --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">
            {{-- Referral Code Card --}}
            <div class="md:col-span-7">
                <div class="h-full rounded-lg border border-border bg-card p-5 md:p-6 shadow-sm">
                    <div class="flex flex-col h-full justify-between">
                        <div>
                            <span class="text-[9px] font-bold text-primary uppercase tracking-widest block mb-1">Kode Referral</span>
                            <div class="flex items-center gap-3">
                                <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-foreground">{{ auth()->user()->affiliateProfile->referral_code ?? 'N/A' }}</h2>
                                <button onclick="navigator.clipboard.writeText('{{ auth()->user()->affiliateProfile->referral_code ?? '' }}'); alert('Kode disalin!')" 
                                    class="p-1.5 text-muted-foreground hover:text-foreground hover:bg-muted rounded transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/></svg>
                                </button>
                            </div>
                            
                            <div class="mt-4 flex items-center gap-2">
                                <div class="flex-1 px-3 py-2 bg-muted/30 border border-border rounded-lg truncate">
                                    <span class="text-[11px] font-mono text-muted-foreground">
                                        {{ url('/?ref=' . (auth()->user()->affiliateProfile->referral_code ?? '')) }}
                                    </span>
                                </div>
                                <button onclick="navigator.clipboard.writeText('{{ url('/?ref=' . (auth()->user()->affiliateProfile->referral_code ?? '')) }}'); alert('Link disalin!')" 
                                    class="inline-flex items-center justify-center gap-2 h-10 px-6 rounded-lg bg-primary text-primary-foreground text-xs font-bold shadow hover:bg-primary/90 transition-all active:scale-95 whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    Salin
                                </button>
                            </div>

                            <p class="text-xs text-muted-foreground mt-6 leading-relaxed">Gunakan kode ini atau link untuk komisi <strong>{{ auth()->user()->affiliateProfile->commission_rate }}%</strong> setiap transaksi.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Balance Card --}}
            <div class="md:col-span-5">
                <div class="bg-card border border-border shadow-sm rounded-2xl overflow-hidden p-6 relative">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Saldo Affiliator</span>
                        <span class="text-2xl font-black text-foreground tracking-tighter">Rp {{ number_format($this->wallet_balance, 0, ',', '.') }}</span>
                        <div class="mt-4">
                            <span class="text-[10px] text-muted-foreground">Total Komisi: Rp {{ number_format($totalCommissionEarned, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('affiliate.payout') }}" wire:navigate
                            class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow h-9 px-4 text-xs font-bold hover:bg-primary/90 transition-colors">
                            Ajukan Payout
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Small Grid Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
            <div class="p-3 rounded-lg border border-border bg-card shadow-sm">
                <p class="text-[9px] font-bold text-muted-foreground uppercase opacity-70">Total Sewa</p>
                <p class="text-lg font-bold text-foreground mt-0.5">{{ auth()->user()->affiliateRentals()->count() }}</p>
            </div>
            <div class="p-3 rounded-lg border border-border bg-card shadow-sm">
                <p class="text-[9px] font-bold text-muted-foreground uppercase opacity-70">Sewa Selesai</p>
                <p class="text-lg font-bold text-foreground mt-0.5">{{ $completedRentalsCount }}</p>
            </div>
            <div class="p-3 rounded-lg border border-border bg-card shadow-sm">
                <p class="text-[9px] font-bold text-muted-foreground uppercase opacity-70">Penarikan</p>
                <p class="text-lg font-bold text-foreground mt-0.5">Rp {{ number_format($totalWithdrawn, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 rounded-lg border border-border bg-card shadow-sm">
                <p class="text-[9px] font-bold text-primary uppercase opacity-70">Hari Ini</p>
                <p class="text-lg font-bold text-primary mt-0.5">{{ auth()->user()->affiliateRentals()->where('created_at', '>=', now()->startOfDay())->count() }}</p>
            </div>
            
        </div>

        @if($tab !== 'payout_request')
            <div class="mt-6">
                <div class="flex items-center gap-1 p-1 bg-muted rounded-xl w-full border border-border mb-6">
                    <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        class="flex-1 px-4 py-2 text-[11px] font-bold rounded-lg transition-all text-center">
                        Aktivitas
                    </button>
                    <button @click="activeTab = 'payouts'" :class="activeTab === 'payouts' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        class="flex-1 px-4 py-2 text-[11px] font-bold rounded-lg transition-all text-center">
                        Payout
                    </button>
                    <button @click="activeTab = 'promos'" :class="activeTab === 'promos' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        class="flex-1 px-4 py-2 text-[11px] font-bold rounded-lg transition-all text-center">
                        Promo
                    </button>
                    <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        class="flex-1 px-4 py-2 text-[11px] font-bold rounded-lg transition-all text-center">
                        Settings
                    </button>
                </div>

                {{-- Tab Content --}}
                <div class="space-y-6">
                    <!-- Activity Table -->
                    <div x-show="activeTab === 'overview'" x-transition class="rounded-lg border border-border bg-card overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr class="text-[9px] font-bold text-muted-foreground uppercase opacity-70">
                                        <th class="px-4 py-2">Booking / Tgl</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2 text-right">Komisi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @forelse(auth()->user()->affiliateRentals()->latest()->take(15)->get() as $rental)
                                        <tr class="hover:bg-muted/10 transition-colors">
                                            <td class="px-4 py-2">
                                                <p class="text-[11px] font-medium text-foreground">{{ $rental->nama }}</p>
                                                <p class="text-[9px] text-muted-foreground">{{ $rental->created_at->format('d M Y, H:i') }}</p>
                                            </td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-medium border border-border
                                                    @if($rental->status == 'completed') bg-green-50 text-green-700 @else bg-yellow-50 text-yellow-700 @endif capitalize">
                                                    {{ $rental->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right text-[11px] font-medium text-green-600">
                                                @php 
                                                    $commission = auth()->user()->commissions()->where('rental_id', $rental->id)->first();
                                                    $amount = $commission ? $commission->amount : ($rental->subtotal_harga * (auth()->user()->affiliateProfile->commission_rate / 100));
                                                @endphp
                                                Rp {{ number_format($amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-muted-foreground text-[10px]">Belum ada aktivitas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payout Log Table -->
                    <div x-show="activeTab === 'payouts'" x-transition x-cloak class="rounded-lg border border-border bg-card overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-muted/50 border-b border-border">
                                    <tr class="text-[9px] font-bold text-muted-foreground uppercase opacity-70">
                                        <th class="px-4 py-2">REF</th>
                                        <th class="px-4 py-2">Tgl Pengajuan</th>
                                        <th class="px-4 py-2">Tgl Bayar</th>
                                        <th class="px-4 py-2">Nominal</th>
                                        <th class="px-4 py-2 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @forelse($payoutHistory as $payout)
                                        <tr class="hover:bg-muted/10">
                                            <td class="px-4 py-2 text-[11px] font-medium text-foreground">PAY-{{ $payout->id }}</td>
                                            <td class="px-4 py-2 text-[9px] text-muted-foreground">{{ $payout->created_at->format('d/m/Y') }}</td>
                                            <td class="px-4 py-2 text-[9px] text-muted-foreground">
                                                @if($payout->status === 'processed')
                                                    {{ $payout->updated_at->format('d/m/Y') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-[11px] font-medium">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <span class="px-2 py-0.5 rounded text-[9px] font-medium border border-border
                                                    @if($payout->status === 'pending') bg-yellow-50 text-yellow-700
                                                    @elseif($payout->status === 'processed') bg-green-50 text-green-700
                                                    @else bg-red-50 text-red-700 @endif capitalize">
                                                    {{ $payout->status }}
                                                </span>
                                                @if($payout->note)
                                                    <p class="text-[9px] text-muted-foreground italic mt-1 leading-tight">{{ $payout->note }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground text-[10px] font-medium">Belum ada payout.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Setting / Profile -->
                    <div x-show="activeTab === 'profile'" x-transition x-cloak class="max-w-3xl space-y-6 pb-12">
                        {{-- Section: Data Diri --}}
                        <div class="rounded-lg border border-border bg-card shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-border bg-muted/30">
                                <h3 class="text-sm font-bold flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Data Diri & Profil
                                </h3>
                            </div>
                            <form wire:submit="updateProfile" class="p-6 space-y-5">
                                @if(session()->has('profile_success'))
                                    <div class="p-3 bg-green-50 border border-green-200 text-green-700 text-[11px] font-bold rounded-md mb-4">{{ session('profile_success') }}</div>
                                @endif
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Nama Lengkap</label>
                                        <input type="text" wire:model="name" oninput="this.value = this.value.toUpperCase()" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none">
                                        @error('name') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Email Address (Read Only)</label>
                                        <input type="email" wire:model="email" disabled class="w-full h-9 px-3 rounded-md border border-input bg-muted/50 text-muted-foreground text-sm outline-none cursor-not-allowed">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">NIK (No. KTP)</label>
                                        <input type="text" wire:model="nik" disabled class="w-full h-9 px-3 rounded-md border border-input bg-muted/50 text-muted-foreground text-sm outline-none cursor-not-allowed" placeholder="NIK">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">WhatsApp / No. HP</label>
                                        <input type="text" wire:model="no_hp" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none" placeholder="08xxxxxxxxxx">
                                        @error('no_hp') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Alamat</label>
                                    <textarea wire:model="alamat" disabled rows="2" class="w-full p-3 rounded-md border border-input bg-muted/50 text-muted-foreground text-sm outline-none cursor-not-allowed resize-none" placeholder="Alamat"></textarea>
                                </div>

                                {{-- Section: Rekening (Inside Profile for combined save) --}}
                                <div class="pt-6 border-t border-border mt-6">
                                    <h4 class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-4">Pengaturan Rekening Payout</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Nama Bank / Wallet</label>
                                            <input type="text" wire:model="bank_name" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none" placeholder="BCA / Mandiri / Dana">
                                            @error('bank_name') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">No. Rekening</label>
                                            <input type="text" wire:model="bank_account_number" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none">
                                            @error('bank_account_number') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Atas Nama</label>
                                            <input type="text" wire:model="bank_account_name" oninput="this.value = this.value.toUpperCase()" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none">
                                            @error('bank_account_name') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="pt-4 flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-foreground text-background shadow h-9 px-8 text-xs font-bold hover:opacity-90 transition-colors">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Section: Ganti Password --}}
                        <div class="rounded-lg border border-border bg-card shadow-sm overflow-hidden" x-data="{ showConfirmModal: false }">
                            <div class="px-6 py-4 border-b border-border bg-muted/30">
                                <h3 class="text-sm font-bold flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                                    Keamanan Akun
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                @if(session()->has('password_success'))
                                    <div class="p-3 bg-green-50 border border-green-200 text-green-700 text-[11px] font-bold rounded-md">{{ session('password_success') }}</div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Password Baru</label>
                                        <input type="password" wire:model="new_password" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none">
                                        @error('new_password') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Konfirmasi Password Baru</label>
                                        <input type="password" wire:model="new_password_confirmation" class="w-full h-9 px-3 rounded-md border border-input bg-background text-sm focus:ring-1 focus:ring-primary outline-none">
                                    </div>
                                </div>

                                <div class="pt-4 flex justify-end">
                                    <button type="button" @click="showConfirmModal = true" class="inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow h-9 px-8 text-xs font-bold hover:bg-red-700 transition-colors">
                                        Update Password
                                    </button>
                                </div>
                            </div>

                            {{-- Confirmation Modal --}}
                            <div x-show="showConfirmModal" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm" x-cloak>
                                <div @click.away="showConfirmModal = false" class="bg-background border border-border rounded-xl shadow-2xl max-w-sm w-full p-6 space-y-4 animate-in zoom-in-95 duration-200">
                                    <div class="text-center space-y-2">
                                        <div class="mx-auto w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-600 mb-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                        </div>
                                        <h4 class="text-base font-bold text-foreground">Konfirmasi Ganti Password</h4>
                                        <p class="text-xs text-muted-foreground leading-relaxed">Apakah Anda yakin ingin mengubah password akun Anda? Pastikan password baru sudah dicatat.</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 pt-2">
                                        <button @click="showConfirmModal = false" class="h-9 rounded-md border border-input bg-background text-xs font-bold hover:bg-muted transition-colors">Batal</button>
                                        <button wire:click="changePassword" @click="showConfirmModal = false" class="h-9 rounded-md bg-red-600 text-white text-xs font-bold hover:bg-red-700 shadow-sm transition-colors">Ya, Update</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Promos -->
                    <!-- Promos & General Referral Link -->
                    <div x-show="activeTab === 'promos'" x-transition x-cloak class="space-y-8">
                        {{-- General Referral Link Section --}}
                        <div class="rounded-2xl border border-border bg-card shadow-lg p-8 relative overflow-hidden">
                            <div class="absolute top-0 right-0 -m-4 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                            <div class="absolute bottom-0 left-0 -m-4 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                            
                            <div class="relative z-10 text-center max-w-xl mx-auto">
                                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary/10 text-primary mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                </div>
                                <h3 class="text-xl font-black text-foreground">Link Referral Utama</h3>
                                <p class="text-xs text-muted-foreground mt-2 leading-relaxed">
                                    Bagikan link ini ke media sosial atau teman Anda. Setiap pesanan yang masuk melalui link ini akan otomatis tercatat sebagai komisi Anda.
                                </p>

                                <div class="mt-8 space-y-4 text-left max-w-lg mx-auto" x-data="{ 
                                    copied: null,
                                    copy(text, id) {
                                        navigator.clipboard.writeText(text);
                                        this.copied = id;
                                        setTimeout(() => this.copied = null, 2000);
                                    }
                                }">
                                    {{-- Option 1: Homepage --}}
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest pl-1">1. Link Promosi Beranda</label>
                                        <div class="flex gap-2 items-stretch">
                                            <div class="flex-1 px-4 py-2.5 bg-muted/50 border border-border rounded-lg text-xs font-mono text-foreground truncate select-all">
                                                {{ url('/?ref=' . auth()->user()->affiliateProfile->referral_code) }}
                                            </div>
                                            <button @click="copy('{{ url('/?ref=' . auth()->user()->affiliateProfile->referral_code) }}', 'home')" 
                                                :class="copied === 'home' ? 'bg-green-500 border-green-500 text-white' : 'bg-foreground text-background'"
                                                class="px-4 py-2 rounded-lg font-bold text-[10px] transition-all flex items-center gap-2 min-w-[110px] justify-center">
                                                <span x-text="copied === 'home' ? 'Berhasil!' : 'Salin'"></span>
                                                <svg x-show="copied !== 'home'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                <svg x-show="copied === 'home'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Option 2: Booking Form --}}
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-muted-foreground uppercase tracking-widest pl-1">2. Link Langsung Form Booking</label>
                                        <div class="flex gap-2 items-stretch">
                                            <div class="flex-1 px-4 py-2.5 bg-muted/50 border border-border rounded-lg text-xs font-mono text-foreground truncate select-all">
                                                {{ route('public.booking', ['ref' => auth()->user()->affiliateProfile->referral_code]) }}
                                            </div>
                                            <button @click="copy('{{ route('public.booking', ['ref' => auth()->user()->affiliateProfile->referral_code]) }}', 'booking')" 
                                                :class="copied === 'booking' ? 'bg-green-500 border-green-500 text-white' : 'bg-foreground text-background'"
                                                class="px-4 py-2 rounded-lg font-bold text-[10px] transition-all flex items-center gap-2 min-w-[110px] justify-center">
                                                <span x-text="copied === 'booking' ? 'Berhasil!' : 'Salin'"></span>
                                                <svg x-show="copied !== 'booking'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                <svg x-show="copied === 'booking'" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 p-4 bg-primary/5 rounded-xl border border-primary/10">
                                    <p class="text-[10px] text-primary font-bold italic leading-relaxed">
                                        💡 Info: Begitu salah satu link di atas diklik, sistem akan langsung "menandai" browser calon penyewa. Affiliator tetap akan mendapatkan komisi meskipun penyewa pindah-pindah menu atau baru melakukan booking beberapa hari kemudian.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Active Promos List --}}
                        <div class="pt-4">
                            <h4 class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-4">Voucher & Promo Aktif</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @forelse($activePromos as $promo)
                                    <div class="rounded-lg border border-border bg-card p-5 shadow-sm">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-[9px] font-bold bg-primary/10 text-primary px-2 py-0.5 rounded border border-primary/20 uppercase tracking-tighter">Active Promo</span>
                                        </div>
                                        <h4 class="text-sm font-bold text-foreground">{{ $promo->nama_promo }}</h4>
                                        <p class="text-[10px] text-muted-foreground mt-1">Min. durasi: {{ $promo->syarat_minimal_durasi }} {{ $promo->syarat_tipe_durasi }}</p>
                                        <div class="mt-4 p-3 bg-muted/50 rounded border border-border border-dashed flex items-center justify-between">
                                            <span class="text-[9px] font-bold text-muted-foreground uppercase">Voucher Code</span>
                                            <span class="text-sm font-bold text-foreground font-mono">{{ $promo->kode_promo }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-12 border border-border border-dashed rounded-lg text-center bg-muted/20">
                                        <p class="text-xs text-muted-foreground">Belum ada promo.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
