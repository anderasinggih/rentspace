<div class="min-h-[100dvh] bg-base-200/50 flex flex-col items-center justify-center px-4 pb-20 sm:pb-32">

    <div class="w-full max-w-sm">
        {{-- Logo / Brand --}}
        <div class="text-center mb-6">
            <a href="{{ route('public.home') }}" wire:navigate class="inline-block transition-transform hover:scale-105">
                <span class="text-2xl font-black tracking-tight text-base-content">RENT<span class="text-primary">SPACE</span></span>
            </a>
            <p class="text-xs text-base-content/60 mt-1 font-medium">Purwokerto Gadget & iPhone Rental</p>
        </div>

        {{-- Card --}}
        <div class="card bg-base-100 border border-base-300 shadow-xl">
            <div class="card-body p-6 sm:p-8">
                <div class="mb-4 text-center">
                    <h1 class="text-xl font-black text-base-content">Masuk Pelanggan</h1>
                    <p class="text-xs text-base-content/60 mt-1">Gunakan No. WhatsApp & Email Anda</p>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                    <div class="alert alert-error text-xs py-2 px-3 shadow-sm rounded-lg mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div class="space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form wire:submit.prevent="login" class="space-y-4">
                    {{-- WhatsApp --}}
                    <div class="form-control">
                        <label for="no_wa" class="label pb-1">
                            <span class="label-text text-xs font-bold text-base-content/80">Nomor WhatsApp</span>
                        </label>
                        <div class="relative">
                            <input id="no_wa" type="text" wire:model="no_wa"
                                placeholder="08xxxxxxxxxx"
                                class="input input-bordered input-md w-full bg-base-100 pl-10"
                                autocomplete="off">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/40 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="form-control">
                        <label for="email" class="label pb-1">
                            <span class="label-text text-xs font-bold text-base-content/80">Email</span>
                        </label>
                        <div class="relative">
                            <input id="email" type="email" wire:model="email"
                                placeholder="nama@email.com"
                                class="input input-bordered input-md w-full bg-base-100 pl-10"
                                autocomplete="off">
                            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/40 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2.5 py-1">
                            <input type="checkbox" id="remember" wire:model="remember" class="checkbox checkbox-primary checkbox-sm">
                            <span class="label-text text-xs font-medium text-base-content/70">Ingat Saya (24 Jam)</span>
                        </label>
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2">
                        <button type="submit" class="btn btn-neutral w-full shadow-md font-bold" wire:loading.attr="disabled">
                            <span wire:loading.remove>Masuk ke Akun</span>
                            <span wire:loading class="inline-flex items-center gap-2">
                                <span class="loading loading-spinner loading-xs"></span>
                                Memverifikasi...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Footer links --}}
        <div class="mt-6 text-center space-y-3">
            <p class="text-xs text-base-content/70">
                Belum punya pesanan?
                <a href="{{ route('public.booking') }}" wire:navigate
                    class="link link-primary font-bold"> Booking sekarang</a>
            </p>
            <div class="pt-2">
                <a href="{{ route('public.home') }}" wire:navigate class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content font-bold">
                    ← KEMBALI KE BERANDA
                </a>
            </div>
        </div>
    </div>
</div>