<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Pengaturan Web</h1>
        <p class="mt-2 text-sm text-muted-foreground">Kelola konfigurasi website seperti QRIS Pembayaran.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- QRIS Setting -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">QRIS Pembayaran</h2>
                <p class="text-xs text-muted-foreground">Unggah barcode / gambar QRIS yang akan ditampilkan di halaman
                    pembayaran.</p>
            </div>
            <div class="p-4">
                @if (session()->has('message'))
                    <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200"
                        role="alert">
                        {{ session('message') }}
                    </div>
                @endif

                <div x-data="{ photoName: null, photoPreview: null }" class="w-full">
                    <div class="mb-4 flex items-center justify-center">
                        <div
                            class="w-48 h-48 bg-muted border border-dashed border-border rounded-lg flex items-center justify-center relative overflow-hidden">
                            <!-- New Photo Preview -->
                            <div x-show="photoPreview" style="display: none;" class="absolute inset-0 z-20">
                                <span class="block w-full h-full bg-cover bg-no-repeat bg-center"
                                    x-bind:style="'background-image: url(\'' + photoPreview + '\');'"></span>
                            </div>

                            <!-- Current Photo -->
                            <div x-show="!photoPreview" class="absolute inset-0 z-10">
                                <img src="/uploads/{{ $qris }}?t={{ time() }}"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    class="w-full h-full object-cover">
                                <div style="display:none;"
                                    class="w-full h-full flex items-center justify-center bg-muted text-muted-foreground text-xs font-medium">
                                    Logo Kosong</div>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveQris" class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Unggah Foto (Rekomendasi 1:1, Max 2MB)</label>
                            <input type="file" x-ref="photo" x-on:change="
        photoName = $refs.photo.files[0].name;
        const reader = new FileReader();
        reader.onload = (e) => { photoPreview = e.target.result; };
        reader.readAsDataURL($refs.photo.files[0]);
    " wire:model="qris_photo" accept="image/*"
                                class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">

                            <div wire:loading wire:target="qris_photo"
                                class="text-xs text-blue-600 font-semibold mt-1 animate-pulse">
                                Sedang memproses file ke server... Jangan klik simpan dulu.
                            </div>
                            @error('qris_photo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                            wire:loading.attr="disabled" {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="saveQris">Simpan QRIS Baru</span>
                            <span wire:loading wire:target="saveQris">Menyimpan...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hero Photo Setting -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">1:1 Foto Beranda (Hero)</h2>
                <p class="text-xs text-muted-foreground">Unggah foto 1:1 yang akan diletakkan di bagian belakang/samping
                    judul publik web.</p>
            </div>
            <div class="p-4">
                @if (session()->has('hero_message'))
                    <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200"
                        role="alert">
                        {{ session('hero_message') }}
                    </div>
                @endif

                <div x-data="{ heroName: null, heroPreview: null }" class="w-full">
                    <div class="mb-4 flex items-center justify-center">
                        <div
                            class="w-48 h-48 bg-muted border border-dashed border-border rounded-xl flex items-center justify-center relative overflow-hidden shadow-inner">
                            <div x-show="heroPreview" style="display: none;" class="absolute inset-0 z-20">
                                <span class="block w-full h-full bg-cover bg-no-repeat bg-center"
                                    x-bind:style="'background-image: url(\'' + heroPreview + '\');'"></span>
                            </div>

                            <div x-show="!heroPreview"
                                class="absolute inset-0 z-10 bg-muted flex items-center justify-center bg-cover bg-center"
                                style="background-image: url('/uploads/{{ $hero }}?t={{ time() }}')">
                            </div>
                        </div>
                    </div>

                    <form wire:submit="saveHero" class="flex flex-col gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Unggah Foto (Resolusi 1:1, Max 3MB)</label>
                            <input type="file" x-ref="hero" x-on:change="
        heroName = $refs.hero.files[0].name;
        const reader = new FileReader();
        reader.onload = (e) => { heroPreview = e.target.result; };
        reader.readAsDataURL($refs.hero.files[0]);
    " wire:model="hero_photo" accept="image/*"
                                class="w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">

                            <div wire:loading wire:target="hero_photo"
                                class="text-xs text-blue-600 font-semibold mt-1 animate-pulse">
                                Sedang memproses file ke server... Jangan klik simpan dulu.
                            </div>
                            @error('hero_photo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                            wire:loading.attr="disabled" {{ auth()->user()->role !== 'admin' ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="saveHero">Simpan Foto Beranda</span>
                            <span wire:loading wire:target="saveHero">Menyimpan...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kelola Akun Admin -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">Kelola Akun Admin</h2>
                <p class="text-xs text-muted-foreground">Tambah atau hapus akses masuk dasbor admin (Role). {{
    auth()->user()->role !== 'admin' ? 'Fitur dikunci untuk Viewer.' : '' }}</p>
            </div>
            <div class="p-4 flex-1">
                @if (session()->has('user_message'))
                            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{
                    session('user_message') }}</div>
                @endif
                @if (session()->has('user_error'))
                            <div class="p-3 mb-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{
                    session('user_error') }}</div>
                @endif

                @if(auth()->user()->role === 'admin')
                    <form wire:submit="createUser" class="mb-6 grid grid-cols-1 gap-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <input type="text" wire:model="name" placeholder="Nama Baru"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('name') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <input type="email" wire:model="email" placeholder="Email Baru"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('email') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <input type="password" wire:model="password" placeholder="Password"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                @error('password') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <select wire:model="role"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                    <option value="admin">Admin</option>
                                    <option value="viewer">Viewer (View Only)</option>
                                </select>
                                @error('role') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <button type="submit"
                            class="h-9 w-full rounded-md bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 text-sm font-semibold">Tambah
                            Akun</button>
                    </form>
                @endif

                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-muted/50 text-xs text-muted-foreground uppercase border-b border-border">
                            <tr>
                                <th class="px-4 py-2 font-medium">Nama/Email</th>
                                <th class="px-4 py-2 text-right"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($users as $user)
                                                    <tr class="hover:bg-muted/30">
                                                        <td class="px-4 py-3">
                                                            <div class="font-medium text-foreground">{{ $user->name }}</div>
                                                            <div class="text-xs text-muted-foreground">{{ $user->email }} • <span
                                                                    class="font-bold border px-1 rounded bg-muted">{{ $user->role === 'viewer' ?
                                'Viewer' : 'Admin' }}</span></div>
                                                        </td>
                                                        <td class="px-4 py-3 text-right">
                                                            @if(auth()->id() !== $user->id && auth()->user()->role === 'admin')
                                                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Hapus admin ini?"
                                                                    class="text-xs text-red-500 hover:text-red-700 hover:underline">Hapus</button>
                                                            @else
                                                                <span class="text-xs text-muted-foreground italic">Anda</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pengaturan Umum -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm md:col-span-2">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">Pengaturan Umum</h2>
                <p class="text-xs text-muted-foreground">Konfigurasi teks beranda dan toleransi keterlambatan denda.</p>
            </div>
            <div class="p-4">
                @if (session()->has('general_message'))
                            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{
                    session('general_message') }}</div>
                @endif
                <form wire:submit="saveGeneralSettings" class="flex flex-col gap-4 max-w-2xl">
                    <div>
                        <label class="block text-sm font-medium mb-1">Judul Beranda (Mendukung Enter)</label>
                        <textarea wire:model="home_title" rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Deskripsi Beranda</label>
                        <textarea wire:model="home_description" rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Batas Toleransi Keterlambatan Pengembalian
                            (Menit)</label>
                        <input type="number" wire:model="late_tolerance_minutes"
                            class="h-9 w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <p class="text-xs text-muted-foreground mt-1">Jika *customer* telat melebihi batas menit yang
                            ditentukan setelah jadwal selesai, Anda akan diminta memasukkan opsi Denda saat
                            menyelesaikan transaksi.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Minimal Penarikan Komisi Affiliate (Rp)</label>
                        <input type="number" wire:model="min_payout"
                            class="h-9 w-48 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                        <p class="text-xs text-muted-foreground mt-1">Jumlah minimum saldo komisi yang harus dimiliki mitra sebelum mereka dapat mengajukan penarikan dana.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nomor WhatsApp Pelayanan Admin (format
                            628...)</label>
                        <input type="text" wire:model="admin_wa"
                            class="h-9 w-full sm:w-1/2 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Alamat Offline Toko (tampil di Footer Web
                            Publik)</label>
                        <textarea wire:model="admin_address" rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Metode Pembayaran Aktif <span
                                class="text-xs text-muted-foreground">(Ditampilkan kepada penyewa saat
                                checkout)</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label
                                class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                <input type="checkbox" wire:model="payment_methods.qris"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium">QRIS</p>
                                    <p class="text-xs text-muted-foreground">Scan QR bayar langsung</p>
                                </div>
                            </label>
                            <label
                                class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                <input type="checkbox" wire:model="payment_methods.cash"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium">Tunai (Cash)</p>
                                    <p class="text-xs text-muted-foreground">Bayar langsung di tempat</p>
                                </div>
                            </label>
                            <label
                                class="flex items-center gap-3 p-3 rounded-lg border border-input bg-background cursor-pointer hover:bg-muted/40 transition-colors">
                                <input type="checkbox" wire:model="payment_methods.transfer"
                                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                <div>
                                    <p class="text-sm font-medium">Transfer Bank</p>
                                    <p class="text-xs text-muted-foreground">Transfer rekening admin</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Link Media Sosial <span
                                class="text-xs text-muted-foreground">(Tampil di Footer)</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Instagram
                                    URL</label>
                                <input type="url" wire:model="social_ig_url"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="https://instagram.com/namaakun">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Nama
                                    Akun IG</label>
                                <input type="text" wire:model="social_ig_name"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="@namaakun">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">TikTok
                                    URL</label>
                                <input type="url" wire:model="social_tiktok_url"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="https://tiktok.com/@namaakun">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-foreground uppercase mb-1">Nama
                                    Akun TikTok</label>
                                <input type="text" wire:model="social_tiktok_name"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="@namaakun">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Syarat & Ketentuan Penyewaan <span
                                class="text-xs text-muted-foreground">(Ditampilkan di form booking, satu baris = satu
                                poin)</span></label>
                        <textarea wire:model="terms_conditions" rows="8"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring font-mono"
                            placeholder="1. Penyewa wajib..."></textarea>
                    </div>
                    <button type="submit"
                        class="mt-4 self-start inline-flex items-center justify-center rounded-md bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80 h-9 px-4 py-2 text-sm font-medium transition-colors cursor-pointer"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveGeneralSettings">Simpan Pengaturan</span>
                        <span wire:loading wire:target="saveGeneralSettings">Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Kelola FAQ Halaman Tentang -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm md:col-span-2">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">Kelola FAQ Halaman Tentang</h2>
                <p class="text-xs text-muted-foreground">Tambah atau edit tanya jawab yang akan muncul di halaman
                    "Tentang Kami".</p>
            </div>
            <div class="p-4">
                @if (session()->has('faq_message'))
                            <div class="p-3 mb-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{
                    session('faq_message') }}</div>
                @endif

                <div class="space-y-4">
                    @foreach($about_faq_items as $index => $faq)
                        <div class="p-4 border border-border rounded-lg bg-muted/10 relative group">
                            <button type="button" wire:click="removeFaq({{ $index }})"
                                class="absolute top-2 right-2 p-1 text-muted-foreground hover:text-red-500 hover:bg-red-50 rounded-md transition-all opacity-0 group-hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg>
                            </button>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-muted-foreground mb-1">Pertanyaan
                                        #{{ $index + 1 }}</label>
                                    <input type="text" wire:model="about_faq_items.{{ $index }}.question"
                                        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="Misal: Berapa lama waktu sewa?">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold uppercase text-muted-foreground mb-1">Jawaban</label>
                                    <textarea wire:model="about_faq_items.{{ $index }}.answer" rows="3"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        placeholder="Masukkan jawaban di sini..."></textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if(count($about_faq_items) === 0)
                        <div class="text-center py-8 border-2 border-dashed border-border rounded-xl">
                            <p class="text-sm text-muted-foreground">Belum ada FAQ yang ditambahkan.</p>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-border">
                        <button type="button" wire:click="addFaq"
                            class="inline-flex items-center justify-center rounded-md border border-input bg-background shadow-sm hover:bg-muted h-9 px-4 py-2 text-sm font-medium transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-2">
                                <path d="M5 12h14" />
                                <path d="M12 5v14" />
                            </svg>
                            Tambah Item FAQ
                        </button>
                        <button type="button" wire:click="saveFaqSettings"
                            class="inline-flex items-center justify-center rounded-md bg-primary text-primary-foreground shadow hover:bg-primary/90 h-9 px-6 py-2 text-sm font-bold transition-colors">
                            Simpan Perubahan FAQ
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Sistem Cadangan & Pemulihan -->
        <div class="bg-background rounded-xl border border-border overflow-hidden shadow-sm md:col-span-2">
            <div class="p-4 border-b border-border bg-muted/30">
                <h2 class="text-lg font-semibold">Sistem Cadangan & Pemulihan</h2>
                <p class="text-xs text-muted-foreground">Ekspor semua data sistem (Unit, Promo, Transaksi, Pengaturan)
                    ke file JSON atau pulihkan dari file cadangan.</p>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Export Section -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-tight text-foreground">Ekspor Data (Backup)</h3>
                    <p class="text-xs text-muted-foreground leading-relaxed">Gunakan fitur ini untuk mendownload seluruh
                        data sistem Anda saat ini. Simpan file ini di tempat yang aman sebagai cadangan.</p>
                    <button wire:click="exportData"
                        class="inline-flex items-center justify-center rounded-md bg-sky-600 text-white shadow hover:bg-sky-700 h-10 px-6 text-sm font-bold transition-colors w-full sm:w-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="mr-2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" x2="12" y1="15" y2="3" />
                        </svg>
                        Ekspor ke .JSON
                    </button>
                </div>

                <!-- Import Section -->
                <div class="space-y-4 border-t md:border-t-0 md:border-l border-border pt-6 md:pt-0 md:pl-8">
                    <h3 class="text-sm font-bold uppercase tracking-tight text-foreground">Pulihkan Data (Restore)</h3>
                    <p class="text-xs text-red-500 font-semibold leading-relaxed">PERINGATAN: Mengimpor data akan
                        menimpa data yang ada saat ini. Pastikan file backup valid dan sesuai.</p>

                    @if (session()->has('import_message'))
                        <div class="p-3 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">
                            {{ session('import_message') }}</div>
                    @endif
                    @if (session()->has('import_error'))
                        <div class="p-3 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">
                            {{ session('import_error') }}</div>
                    @endif

                    <form wire:submit="importData" class="space-y-3">
                        <div class="relative">
                            <input type="file" wire:model="importFile" accept=".json"
                                class="w-full text-xs text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-colors">
                            <div wire:loading wire:target="importFile"
                                class="text-[10px] text-primary animate-pulse mt-1">Mengunggah file...</div>
                        </div>
                        @error('importFile') <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror

                        <button type="submit" wire:click="importData"
                            wire:confirm="Apakah Anda yakin ingin melakukan import? Data yang ada saat ini akan ditimpa."
                            class="inline-flex items-center justify-center rounded-md bg-red-600 text-white shadow hover:bg-red-700 h-10 px-6 text-sm font-bold transition-colors w-full sm:w-auto"
                            wire:loading.attr="disabled">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="mr-2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="17 8 12 3 7 8" />
                                <line x1="12" x2="12" y1="3" y2="15" />
                            </svg>
                            <span wire:loading.remove wire:target="importData">Pulihkan Data</span>
                            <span wire:loading wire:target="importData">Memproses...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>