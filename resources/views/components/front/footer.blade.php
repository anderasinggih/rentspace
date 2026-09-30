<footer class="border-t border-border py-8 bg-muted/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-muted-foreground">
        <p class="mb-3 font-medium">{{ \App\Models\Setting::getVal('admin_address', 'Jl. Jendral Sudirman, Purwokerto') }}</p>
        @php
            $ig_url = \App\Models\Setting::getVal('social_ig_url', '');
            $ig_name = \App\Models\Setting::getVal('social_ig_name', '');
            $tt_url = \App\Models\Setting::getVal('social_tiktok_url', '');
            $tt_name = \App\Models\Setting::getVal('social_tiktok_name', '');
        @endphp
        @if($ig_url || $tt_url)
            <div class="flex items-center justify-center gap-4 mb-4">
                @if($ig_url)
                    <a href="{{ $ig_url }}" target="_blank" rel="noopener"
                        class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="group-hover:text-pink-500 transition-colors">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
                        </svg>
                        <span class="text-xs">{{ $ig_name ?: 'Instagram' }}</span>
                    </a>
                @endif
                @if($tt_url)
                    <a href="{{ $tt_url }}" target="_blank" rel="noopener"
                        class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors group">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="currentColor" class="group-hover:text-foreground transition-colors">
                            <path
                                d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.28 6.28 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.15a8.16 8.16 0 0 0 4.77 1.52V7.22a4.85 4.85 0 0 1-1-.53z" />
                        </svg>
                        <span class="text-xs">{{ $tt_name ?: 'TikTok' }}</span>
                    </a>
                @endif
            </div>
        @endif
        &copy; {{ date('Y') }} RENT SPACE. All Rights Reserved.
    </div>
</footer>
