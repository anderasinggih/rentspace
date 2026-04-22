<div>
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Manajemen Affiliate</h1>
            <p class="mt-2 text-sm text-muted-foreground">Kelola pendaftaran mitra dan permintaan pencairan komisi.</p>
        </div>
        
        <div class="flex bg-muted p-1 rounded-xl">
            <button wire:click="$set('tab', 'request')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $tab === 'request' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                Permission ({{ \App\Models\AffiliatorProfile::count() }})
            </button>
            <button wire:click="$set('tab', 'account')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $tab === 'account' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                Account
            </button>
            <button wire:click="$set('tab', 'payouts')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $tab === 'payouts' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                Payouts ({{ \App\Models\AffiliatePayout::where('status', 'pending')->count() }})
            </button>
            <button wire:click="$set('tab', 'history')" 
                class="px-4 py-2 text-xs font-bold rounded-lg transition-all {{ $tab === 'history' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                History
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="p-4 mb-6 text-sm text-green-800 rounded-xl bg-green-100 border border-green-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span class="font-bold">{{ session('success') }}</span>
            </div>
            @php 
                // Check if we just approved/rejected a specific ID
                $lastActionId = session('last_action_id'); 
            @endphp
            @if($lastActionId)
                <a href="{{ $this->getAffiliateStatusWaLink($lastActionId) }}" target="_blank" 
                   class="px-4 py-1.5 bg-[#25D366] text-white text-[10px] font-black rounded-lg shadow-lg hover:opacity-90 transition-all flex items-center gap-2">
                   <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                   Kirim Konfirmasi WA
                </a>
            @endif
        </div>
    @endif

    {{-- Review Mode Deep Link --}}
    @if($review_id)
        @php $reviewProfile = \App\Models\AffiliatorProfile::find($review_id); @endphp
        @if($reviewProfile)
            <div class="mb-8 p-6 rounded-xl border border-border bg-card shadow-lg animate-in fade-in slide-in-from-top-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-border pb-6">
                    <div class="flex items-start gap-4">
                        <div class="h-10 w-10 rounded-lg bg-muted flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-foreground"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold tracking-tight text-foreground">
                                    @if($reviewProfile->status === 'pending') Review Calon Affiliator @elseif($reviewProfile->status === 'inactive') Manajemen Akun @else Detail Data Affiliator @endif
                                </h2>
                                @if($reviewProfile->status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-yellow-100 text-yellow-700 border border-yellow-200">Verifikasi Baru</span>
                                @elseif($reviewProfile->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-700 border border-red-200">Status Ditolak</span>
                                @elseif($reviewProfile->status === 'inactive')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">Status Nonaktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700 border border-green-200">Status Aktif</span>
                                @endif
                            </div>
                            <p class="text-xs text-muted-foreground">
                                @if($reviewProfile->status === 'pending')
                                    Tinjau kelayakan data mitra sebelum memberikan akses penuh.
                                @elseif($reviewProfile->status === 'inactive')
                                    Akun ini dinonaktifkan sementara. Tinjau data untuk mengaktifkan kembali.
                                @else
                                    Informasi lengkap terkait pendaftaran dan data diri mitra affiliator.
                                @endif
                            </p>
                        </div>
                    </div>
                    <button wire:click="$set('review_id', null)" class="text-xs font-bold text-muted-foreground hover:text-foreground inline-flex items-center gap-1">
                        Tutup Detail <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                    {{-- Data Personal --}}
                    <div class="space-y-4">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest block">Data Pribadi</span>
                        <div class="space-y-3">
                            <div>
                                <p class="text-[10px] text-muted-foreground">Nama Lengkap</p>
                                <p class="text-sm font-semibold">{{ $reviewProfile->user->name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-muted-foreground">NIK / KTP</p>
                                <p class="text-sm font-mono font-medium tracking-wider">{{ $reviewProfile->nik }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-muted-foreground">WhatsApp</p>
                                <p class="text-sm font-medium">{{ $reviewProfile->no_hp }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div class="space-y-4">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest block">Domisili</span>
                        <p class="text-sm leading-relaxed font-medium">{{ $reviewProfile->alamat }}</p>
                    </div>

                    {{-- Rekening --}}
                    <div class="space-y-4">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest block">Perbankan</span>
                        <div class="space-y-3">
                            <div>
                                <p class="text-[10px] text-muted-foreground">Bank</p>
                                <p class="text-sm font-semibold">{{ $reviewProfile->bank_name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-muted-foreground">No. Rekening</p>
                                <p class="text-sm font-medium">{{ $reviewProfile->bank_account_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-muted-foreground">Atas Nama</p>
                                <p class="text-sm font-medium underline underline-offset-4 decoration-muted-foreground/30">{{ $reviewProfile->bank_account_name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 pt-6 border-t border-border">
                    @if(auth()->user()->role === 'admin')
                        @if($reviewProfile->status === 'pending' || $reviewProfile->status === 'rejected' || $reviewProfile->status === 'inactive')
                            <div class="flex items-center gap-3">
                                <button wire:click="approve({{ $reviewProfile->id }})" class="h-9 px-6 bg-foreground text-background text-xs font-bold rounded-lg shadow hover:opacity-90 transition-all">
                                    {{ $reviewProfile->status === 'inactive' ? 'Aktifkan Kembali' : 'Setujui Akun' }}
                                </button>
                                @if($reviewProfile->status === 'pending')
                                    <button wire:click="reject({{ $reviewProfile->id }})" class="h-9 px-6 bg-background border border-input text-foreground text-xs font-bold rounded-lg hover:bg-muted transition-all">Tolak</button>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-3 text-xs font-medium">
                                <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                                <span class="capitalize">Status: Active</span>
                            </div>
                        @endif
                    @else
                        <div class="flex items-center gap-3">
                             <span class="px-3 py-1.5 rounded-lg border border-dashed border-border text-[10px] font-bold text-muted-foreground uppercase">Mode Viewer (Read Only)</span>
                        </div>
                    @endif

                    @if($reviewProfile->status !== 'pending')
                        <a href="{{ $this->getAffiliateStatusWaLink($reviewProfile->id) }}" target="_blank" 
                           class="h-9 px-6 bg-foreground text-background text-xs font-bold rounded-lg shadow hover:opacity-90 transition-all inline-flex items-center gap-2">
                           <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                           Kirim Konfirmasi WA
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-xl border border-red-200 text-xs font-bold">
                Affiliator dengan ID #{{ $review_id }} tidak ditemukan atau sudah dihapus.
                <button wire:click="$set('review_id', null)" class="underline ml-2">Tutup</button>
            </div>
        @endif
    @endif

    <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
        @if($tab === 'request' || $tab === 'account')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs text-muted-foreground uppercase border-b border-border">
                        <tr>
                            <th class="px-6 py-3">Affiliator / Referral Code</th>
                            <th class="px-6 py-3 text-center">NIK</th>
                            <th class="px-6 py-3">Kontak & Alamat</th>
                            <th class="px-6 py-3">Rekening Bank</th>
                            @if($tab === 'request')
                                <th class="px-6 py-3">Status</th>
                            @endif
                            @if($tab === 'account')
                                <th class="px-6 py-3">Saldo</th>
                                <th class="px-6 py-3">Total Komisi</th>
                                <th class="px-6 py-3">Total Penarikan</th>
                            @endif
                            <th class="px-6 py-3">Komisi</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($profiles as $profile)
                            <tr class="hover:bg-muted/30">
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-between group">
                                        <div>
                                            <div class="font-bold text-foreground">{{ $profile->user->name }}</div>
                                            @if($profile->referral_code)
                                                <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-primary/10 text-primary border border-primary/20 uppercase tracking-wider">{{ $profile->referral_code }}</div>
                                            @else
                                                <div class="text-[10px] text-muted-foreground italic">Belum dibuat</div>
                                            @endif
                                        </div>
                                        <button wire:click="$set('review_id', {{ $profile->id }})" class="p-2 opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-primary transition-all rounded-lg hover:bg-primary/5 shadow-sm border border-transparent hover:border-primary/20" title="Review Detail">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-[10px] font-mono text-muted-foreground uppercase">{{ $profile->nik }}</div>
                                </td>
                                <td class="px-6 py-4 text-nowrap">
                                    <div class="flex items-center justify-between group">
                                        <div>
                                            <div class="text-xs font-medium">{{ $profile->no_hp }}</div>
                                            <div class="text-[10px] text-muted-foreground leading-tight mt-1 max-w-[150px] truncate">{{ $profile->alamat }}</div>
                                        </div>
                                        <a href="https://wa.me/{{ strpos($profile->no_hp, '0') === 0 ? '62'.substr($profile->no_hp, 1) : $profile->no_hp }}" target="_blank" class="p-1.5 opacity-0 group-hover:opacity-100 text-green-600 border border-transparent hover:border-green-200 hover:bg-green-50 rounded-md transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-nowrap">
                                    <div class="text-xs font-medium">{{ $profile->bank_name }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ $profile->bank_account_number }} a/n {{ $profile->bank_account_name }}</div>
                                </td>
                                @if($tab === 'request')
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border w-fit 
                                                {{ $profile->status === 'approved' ? 'bg-green-50 text-green-700 border-green-200' : ($profile->status === 'pending' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-red-50 text-red-700 border-red-200') }}">
                                                {{ $profile->status === 'inactive' ? 'Nonaktif' : ucfirst($profile->status) }}
                                            </span>
                                            @if($profile->status_note)
                                                <span class="text-[9px] text-muted-foreground mt-1 leading-tight italic">"{{ $profile->status_note }}"</span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                @if($tab === 'account')
                                    <td class="px-6 py-4 font-black text-foreground">Rp {{ number_format($profile->balance, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-xs font-bold text-green-600">Rp {{ number_format($profile->total_earned ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-xs font-bold text-red-500">Rp {{ number_format($profile->total_withdrawn ?? 0, 0, ',', '.') }}</td>
                                @endif
                                <td class="px-6 py-4 text-nowrap">
                                    @if(auth()->user()->role === 'admin')
                                        @if($editingAffiliatorId === $profile->id)
                                            <div class="flex items-center gap-2">
                                                <input type="number" wire:model="commission_rate" class="w-16 h-8 text-xs rounded border border-input px-2 bg-background">
                                                <button wire:click="saveCommission" class="text-xs text-primary font-bold">Simpan</button>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2 group">
                                                <span class="font-bold text-foreground">{{ $profile->commission_rate }}%</span>
                                                <button wire:click="editCommission({{ $profile->id }})" class="opacity-0 group-hover:opacity-100 text-muted-foreground hover:text-primary transition-opacity">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <span class="font-bold text-foreground">{{ $profile->commission_rate }}%</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col gap-2 items-end">
                                        @if($tab === 'request')
                                            <button wire:click="$set('review_id', {{ $profile->id }})" class="h-8 px-4 bg-foreground text-background text-[10px] font-bold rounded-lg shadow hover:opacity-90 transition-all flex items-center gap-2">
                                                Detail
                                            </button>
                                        @endif
                                        
                                        @if(auth()->user()->role === 'admin')
                                            @if($profile->status === 'pending')
                                                <div class="flex items-center gap-2">
                                                    <button wire:click="approve({{ $profile->id }})" class="text-[10px] font-bold text-primary hover:underline">Approve</button>
                                                    <button wire:click="reject({{ $profile->id }})" class="text-[10px] text-red-500 hover:underline">Tolak</button>
                                                </div>
                                            @elseif($profile->status === 'rejected' || $profile->status === 'inactive')
                                                <div class="flex items-center gap-2">
                                                    <button wire:click="approve({{ $profile->id }})" class="text-[10px] font-bold text-primary hover:underline">
                                                        {{ $profile->status === 'inactive' ? 'Aktifkan' : 'Approve' }}
                                                    </button>
                                                    <span class="text-[10px] font-bold uppercase {{ $profile->status === 'inactive' ? 'text-gray-500' : 'text-red-500' }}">
                                                        {{ $profile->status === 'inactive' ? 'Nonaktif' : 'Ditolak' }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ $this->getAffiliateStatusWaLink($profile->id) }}" target="_blank" title="Kirim Ulang Status WA" class="text-[#25D366] hover:scale-110 transition-transform flex items-center gap-1 text-[9px] font-bold">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                                        Resend WA
                                                    </a>
                                                    <button wire:click="syncPartnerBalance({{ $profile->id }})" class="text-[9px] font-bold text-muted-foreground hover:text-primary flex items-center gap-1 transition-colors">
                                                        Sync
                                                    </button>
                                                    <button wire:click="confirmDeactivation({{ $profile->id }})" class="text-[9px] font-bold text-red-500 hover:underline">
                                                        Nonaktifkan
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-muted-foreground italic">Tidak ada data mitra ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-border">
                {{ $profiles->links() }}
            </div>
        @else
            <!-- Payouts Tab -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs text-muted-foreground uppercase border-b border-border">
                        <tr>
                            <th class="px-6 py-3 cursor-pointer hover:bg-muted transition-colors group" wire:click="sortBy('name')">
                                <div class="flex items-center gap-1">
                                    Affiliator
                                    @if($sortField === 'name')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $sortDirection === 'asc' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6' }}"/></svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 cursor-pointer hover:bg-muted transition-colors group" wire:click="sortBy('amount')">
                                <div class="flex items-center gap-1">
                                    Jumlah Payout
                                    @if($sortField === 'amount')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $sortDirection === 'asc' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6' }}"/></svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3">Rekening Tujuan</th>
                            <th class="px-6 py-3 cursor-pointer hover:bg-muted transition-colors group" wire:click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Tanggal Pengajuan
                                    @if($sortField === 'created_at')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $sortDirection === 'asc' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6' }}"/></svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($payouts as $payout)
                            <tr class="hover:bg-muted/30">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-foreground">{{ $payout->affiliator->name }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ $payout->affiliator->email }}</div>
                                </td>
                                <td class="px-6 py-4 font-black text-foreground">Rp {{ number_format($payout->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium">{{ $payout->affiliator->affiliatorProfile->bank_name }}</div>
                                    <div class="text-[10px] text-muted-foreground">{{ $payout->affiliator->affiliatorProfile->bank_account_number }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-muted-foreground">{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if(auth()->user()->role === 'admin')
                                        @if($payout->status === 'pending')
                                            <button wire:click="$set('processingPayoutId', {{ $payout->id }})" class="px-3 py-1 bg-foreground text-background text-xs font-bold rounded-md hover:opacity-90 transition-all">Proses Transfer</button>
                                        @elseif($payout->status === 'processed')
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ $this->getWaNotificationLink($payout) }}" target="_blank" 
                                                   class="px-2 py-1 bg-foreground text-background text-[10px] font-bold rounded-md hover:opacity-90 transition-all text-nowrap">
                                                    Kirim Konfirmasi
                                                </a>
                                                <span class="text-[10px] uppercase font-bold text-green-500">SUKSES</span>
                                                <button wire:click="deletePayout({{ $payout->id }})" 
                                                    wire:confirm="Hapus data payout ini?"
                                                    class="p-1 text-muted-foreground hover:text-red-500 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                </button>
                                            </div>
                                        @elseif($payout->status === 'rejected')
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ $this->getWaNotificationLink($payout) }}" target="_blank" 
                                                   class="px-2 py-1 bg-foreground text-background text-[10px] font-bold rounded-md hover:opacity-90 transition-all text-nowrap">
                                                    Kirim Konfirmasi
                                                </a>
                                                <span class="text-[10px] uppercase font-bold text-red-500">DITOLAK</span>
                                                <button wire:click="deletePayout({{ $payout->id }})" 
                                                    wire:confirm="Hapus data payout ini?"
                                                    class="p-1 text-muted-foreground hover:text-red-500 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-[10px] font-bold text-muted-foreground uppercase">Read Only</span>
                                    @endif
                                </td>
                            </tr>
 @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-muted-foreground italic">Tidak ada permintaan payout pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-border">
                {{ $payouts->links() }}
            </div>
        @endif
    </div>

    {{-- Deactivation Modal/Overlay --}}
    @if($deactivatingProfileId)
        <div class="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-card border border-border rounded-xl shadow-2xl max-w-md w-full animate-in zoom-in-95" @click.away="$wire.cancelDeactivation()">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-foreground">Nonaktifkan Akun</h3>
                    <p class="text-xs text-muted-foreground mt-1">Berikan alasan mengapa akun ini dinonaktifkan. Alasan ini akan terlihat oleh affiliator.</p>
                    
                    <div class="mt-6">
                        <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70">Alasan Deaktivasi</label>
                        <textarea wire:model="status_note" 
                            class="w-full mt-1.5 p-3 text-sm bg-background border border-input rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all min-h-[100px]"
                            placeholder="Contoh: Pelanggaran kebijakan konten atau aktivitas mencurigakan..."></textarea>
                        @error('status_note') <span class="text-[10px] text-red-500 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-8 flex items-center gap-3">
                        <button wire:click="deactivate" class="flex-1 h-10 bg-foreground text-background text-xs font-bold rounded-lg shadow hover:opacity-90 transition-all">Nonaktifkan Sekarang</button>
                        <button wire:click="cancelDeactivation" class="h-10 px-6 bg-background border border-input text-foreground text-xs font-bold rounded-lg hover:bg-muted transition-all">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Payout Processing Modal --}}
    @if($processingPayoutId)
        @php $activePayout = \App\Models\AffiliatePayout::find($processingPayoutId); @endphp
        @if($activePayout)
            <div class="fixed inset-0 bg-background/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
                <div class="bg-card border border-border rounded-2xl shadow-2xl max-w-lg w-full animate-in zoom-in-95 duration-200" @click.away="$wire.closeProcessing()">
                    <div class="p-8">
                        @if(!$payoutActionSuccess)
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <h3 class="text-xl font-bold text-foreground">Proses Pencairan Komisi</h3>
                                    <p class="text-xs text-muted-foreground mt-1">Lakukan transfer dana ke rekening mitra berikut.</p>
                                </div>
                                <button wire:click="closeProcessing" class="p-2 hover:bg-muted rounded-full transition-colors text-muted-foreground hover:text-foreground">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>

                            <div class="space-y-6">
                                {{-- Target Info Card --}}
                                <div class="bg-muted/50 rounded-xl p-5 border border-border">
                                    <div class="flex flex-col gap-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-1">Affiliator</p>
                                                <p class="text-sm font-bold">{{ $activePayout->affiliator->name }}</p>
                                            </div>
                                            <div class="text-right space-y-1">
                                                <div class="flex justify-end gap-4 text-[10px]">
                                                    <span class="text-muted-foreground uppercase font-bold">Nominal Tarik:</span>
                                                    <span class="font-mono">Rp {{ number_format($activePayout->amount, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="flex justify-end gap-4 text-[10px]">
                                                    <span class="text-muted-foreground uppercase font-bold text-red-500">Biaya Admin:</span>
                                                    <span class="font-mono text-red-500">- Rp {{ number_format($activePayout->admin_fee, 0, ',', '.') }}</span>
                                                </div>
                                                <div class="pt-1 border-t border-border mt-1">
                                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-0.5">Total Ditransfer</p>
                                                    <p class="text-xl font-black text-foreground font-mono">Rp {{ number_format($activePayout->amount - $activePayout->admin_fee, 0, ',', '.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pt-4 border-t border-border/50 flex justify-between items-center">
                                            <div>
                                                <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest mb-1">Rekening Tujuan</p>
                                                <p class="text-sm font-bold">{{ $activePayout->affiliator->affiliatorProfile->bank_name }} - {{ $activePayout->affiliator->affiliatorProfile->bank_account_number }}</p>
                                                <p class="text-[10px] text-muted-foreground">a.n {{ $activePayout->affiliator->affiliatorProfile->bank_account_name }}</p>
                                            </div>
                                            <button onclick="navigator.clipboard.writeText('{{ $activePayout->affiliator->affiliatorProfile->bank_account_number }}'); alert('Nomor rekening disalin!')" 
                                                class="px-3 py-1.5 bg-background border border-input text-[10px] font-bold rounded-lg hover:bg-muted transition-colors flex items-center gap-1.5 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                                Salin Rekening
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold text-muted-foreground uppercase opacity-70 tracking-widest">Catatan / Bukti Transfer</label>
                                    <textarea wire:model="payout_note" 
                                        class="w-full mt-2 p-4 text-sm bg-background border border-input rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all min-h-[100px]"
                                        placeholder="Tuliskan nomor referensi transfer atau alasan jika ditolak..."></textarea>
                                </div>
                            </div>

                            <div class="mt-10 flex items-center gap-3">
                                <button wire:click="processPayout({{ $activePayout->id }})" wire:confirm="Konfirmasi bahwa dana sudah ditransfer?" 
                                    class="flex-1 h-12 bg-foreground text-background text-sm font-bold rounded-xl shadow-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 uppercase tracking-wide">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    Konfirmasi Transfer
                                </button>
                                <button wire:click="rejectPayout({{ $activePayout->id }})" wire:confirm="Tolak permintaan ini? Saldo akan dikembalikan ke mitra." 
                                    class="h-12 px-6 bg-red-50 text-red-600 border border-red-200 text-xs font-bold rounded-xl hover:bg-red-100 transition-all uppercase tracking-wide">
                                    Tolak
                                </button>
                            </div>
                        @else
                            <div class="text-center py-10 space-y-6">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100 border-4 border-green-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-green-600"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="text-2xl font-bold text-foreground tracking-tight">Transfer Berhasil!</h3>
                                    <p class="text-sm text-muted-foreground italic">"Status pencairan telah diperbarui ke sistem."</p>
                                </div>
                                <div class="pt-8 flex flex-col gap-3">
                                    <a href="{{ $this->getWaNotificationLink($activePayout) }}" target="_blank" 
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#25D366] text-white shadow-xl h-14 px-8 text-sm font-black hover:opacity-90 transition-all active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        KIRIM KONFIRMASI WA
                                    </a>
                                    <button wire:click="closeProcessing" class="h-12 w-full text-xs font-bold text-muted-foreground hover:text-foreground transition-colors">Tutup Jendela</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
