<div class="max-w-full overflow-x-hidden relative">
    <div class="mb-4 flex flex-col gap-3">
        <div class="flex items-center justify-end">
            <div class="flex items-center gap-2">
                @if($connected)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Terhubung
                    </span>
                @else
                    <a href="{{ route('admin.settings', ['tab' => 'instagram']) }}" wire:navigate
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Belum dikonfigurasi
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(!$connected)
        <div class="mb-6 rounded-xl border border-amber-500/40 bg-amber-500/5 p-4">
            <p class="text-sm font-semibold text-amber-700 dark:text-amber-400">Access token Instagram belum diisi.</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Story hanya bisa dipublish lewat Instagram Business/Creator yang tertaut ke Facebook Page.
                Isi token di <a href="{{ route('admin.settings', ['tab' => 'instagram']) }}" wire:navigate
                    class="font-semibold text-primary underline underline-offset-2">Pengaturan → Instagram</a> dulu.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-border bg-background p-6 space-y-5">
            <h2 class="text-base font-semibold">1. Pilih Unit</h2>

            <div>
                <label class="text-sm font-medium leading-none">Unit aktif</label>
                <select wire:model.live="unit_id"
                    class="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <option value="">-- Pilih unit yang mau diiklankan --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}">{{ $u->nama_lengkap }}</option>
                    @endforeach
                </select>
                @error('unit_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                <p class="mt-1.5 text-xs text-muted-foreground">
                    Unit tanpa foto tetap bisa dibuat story-nya — dipakai logo usaha sebagai pengganti.
                </p>
            </div>

            <div>
                <label class="text-sm font-medium leading-none">Caption (opsional — kosongkan untuk pakai template)</label>
                <textarea wire:model="caption" rows="7"
                    class="mt-1 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    placeholder="Boleh diedit manual untuk story ini saja"></textarea>
                <p class="mt-1.5 text-xs text-muted-foreground">Maksimum 2.200 karakter.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="preview" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-md border border-input bg-background h-9 px-4 text-sm font-medium shadow-sm hover:bg-muted disabled:opacity-50">
                    <span wire:loading.remove wire:target="preview">Buat Preview</span>
                    <span wire:loading wire:target="preview">Merender...</span>
                </button>

                <button type="button" wire:click="publish" wire:loading.attr="disabled"
                    wire:confirm="Story yang sudah tayang tidak bisa dihapus lewat API. Tetap tayangkan?"
                    @disabled(!$unit_id || !$connected)
                    class="inline-flex items-center justify-center rounded-md bg-gradient-to-r from-fuchsia-500 to-pink-500 h-9 px-4 text-sm font-medium text-white shadow hover:opacity-90 disabled:opacity-50">
                    <span wire:loading.remove wire:target="publish">Tayangkan Story</span>
                    <span wire:loading wire:target="publish">Mengirim...</span>
                </button>

                <button type="button" wire:click="resetForm"
                    class="inline-flex items-center justify-center rounded-md text-muted-foreground h-9 px-3 text-sm font-medium hover:bg-muted">
                    Reset
                </button>
            </div>

            @if($lastError)
                <div class="rounded-lg border border-red-500/40 bg-red-500/5 p-3">
                    <p class="text-sm font-semibold text-red-600 dark:text-red-400">Gagal</p>
                    <p class="mt-0.5 text-sm text-muted-foreground break-words">{{ $lastError }}</p>
                </div>
            @endif

            @if($result)
                <div class="rounded-lg border p-3 {{ $result['ok'] ? 'border-emerald-500/40 bg-emerald-500/5' : 'border-red-500/40 bg-red-500/5' }}">
                    <p class="text-sm font-semibold {{ $result['ok'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $result['ok'] ? 'Story tayang' : 'Story gagal tayang' }}
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground break-words">{{ $result['message'] }}</p>
                    @if($result['post']->media_id)
                        <p class="mt-1 text-xs text-muted-foreground">Media ID: <span class="font-mono">{{ $result['post']->media_id }}</span></p>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-border bg-background p-6">
                <h2 class="text-base font-semibold mb-4">2. Preview (1080 × 1920)</h2>
                @if($previewUrl)
                    <div class="mx-auto" style="max-width: 300px;">
                        <img src="{{ $previewUrl }}?t={{ time() }}" alt="Preview story"
                            class="w-full rounded-xl border border-border shadow-lg">
                    </div>
                    <p class="mt-3 text-center text-xs text-muted-foreground break-all">
                        Instagram menarik gambar dari URL ini, jadi harus bisa diakses publik.
                    </p>
                @else
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
                        <p class="text-sm text-muted-foreground">Belum ada preview.</p>
                        <p class="mt-1 text-xs text-muted-foreground">Pilih unit lalu klik "Buat Preview".</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-border bg-background p-6">
        <h2 class="text-base font-semibold mb-4">Riwayat Story Terakhir</h2>
        @if($posts->isEmpty())
            <p class="text-sm text-muted-foreground">Belum ada story yang pernah dibuat.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border text-left text-xs uppercase tracking-wider text-muted-foreground">
                            <th class="py-2 pr-3 font-medium">Waktu</th>
                            <th class="py-2 pr-3 font-medium">Unit</th>
                            <th class="py-2 pr-3 font-medium">Status</th>
                            <th class="py-2 pr-3 font-medium">Media ID</th>
                            <th class="py-2 font-medium">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                            <tr class="border-b border-border/50">
                                <td class="py-2 pr-3 whitespace-nowrap text-muted-foreground">
                                    {{ $post->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="py-2 pr-3">{{ $post->unit?->nama_lengkap ?? '—' }}</td>
                                <td class="py-2 pr-3">
                                    @if($post->isPublished())
                                        <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">Tayang</span>
                                    @else
                                        <span class="rounded-full bg-red-500/10 px-2 py-0.5 text-xs font-semibold text-red-600 dark:text-red-400">Gagal</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 font-mono text-xs text-muted-foreground break-all">
                                    {{ $post->media_id ?? '—' }}
                                </td>
                                <td class="py-2 text-muted-foreground">
                                    {{ $post->error_message ?? ($post->insights ? 'reach: '.($post->insights['reach'] ?? 0) : '—') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-6 rounded-xl border border-border bg-muted/30 p-6">
        <h2 class="text-base font-semibold">Placeholder yang bisa dipakai di template</h2>
        <p class="mt-1 text-sm text-muted-foreground">
            Template diatur di <a href="{{ route('admin.settings', ['tab' => 'instagram']) }}" wire:navigate
                class="font-semibold text-primary underline underline-offset-2">Pengaturan → Instagram</a>.
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($placeholders as $token => $example)
                <span class="rounded-md border border-border bg-background px-2 py-1 font-mono text-xs">
                    {{ $token }}
                    <span class="text-muted-foreground">→ {{ $example ?: '(kosong)' }}</span>
                </span>
            @endforeach
        </div>
    </div>
</div>
