<div class="min-h-screen pt-24 pb-12 px-4 sm:px-6 lg:px-8 bg-base-200/50 flex flex-col justify-center items-center">
    <!-- Back to public -->
    <a href="{{ route('public.timeline') }}" wire:navigate class="btn btn-ghost btn-sm absolute top-6 left-6 gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Kembali ke Web
    </a>

    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-primary/10 text-primary mb-3 shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Login Admin</h1>
            <p class="mt-1 text-xs text-base-content/60">Masuk untuk mengelola operasional RentSpace</p>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body p-6 sm:p-8">
                <form wire:submit.prevent="login" class="space-y-4">
                    
                    @if ($errors->has('email'))
                        <div class="alert alert-error text-xs py-2 px-3 shadow-sm rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ $errors->first('email') }}</span>
                        </div>
                    @endif

                    <div class="form-control">
                        <label class="label pb-1.5" for="email">
                            <span class="label-text text-xs font-bold text-base-content/80">Email Address</span>
                        </label>
                        <input id="email" type="email" wire:model="email" class="input input-bordered input-md w-full bg-base-100" placeholder="admin@rentspace.com" required autofocus>
                    </div>

                    <div class="form-control">
                        <label class="label pb-1.5" for="password">
                            <span class="label-text text-xs font-bold text-base-content/80">Password</span>
                        </label>
                        <input id="password" type="password" wire:model="password" class="input input-bordered input-md w-full bg-base-100" placeholder="••••••••" required>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-neutral w-full shadow-md font-bold" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="login">Masuk ke Dashboard</span>
                            <span wire:loading wire:target="login" class="loading loading-spinner loading-sm"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <p class="text-center text-xs text-base-content/40 mt-8 font-medium">
            &copy; {{ date('Y') }} RentSpace Purwokerto. All rights reserved.
        </p>
    </div>
</div>
