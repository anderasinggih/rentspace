<div class="min-h-screen pt-16 pb-12 px-4 sm:px-6 lg:px-8 bg-background flex flex-col justify-start items-center">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1
                class="text-3xl font-extrabold tracking-tight text-foreground transition-opacity duration-500 animate-in fade-in zoom-in-95">
                Login Affiliator</h1>

        </div>

        <div class="bg-background rounded-2xl shadow-sm border border-border p-8">
            <form wire:submit.prevent="login" class="space-y-6">

                @if ($errors->has('email'))
                    <div class="p-3 rounded bg-red-50 text-red-600 text-sm border border-red-200">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <div class="space-y-2">
                    <label for="email" class="text-sm font-medium leading-none">Email Address</label>
                    <input id="email" type="email" wire:model="email" placeholder="partner@rentspace.com"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        required autofocus>
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium leading-none">Password</label>
                    <input id="password" type="password" wire:model="password"
                        class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        required>
                </div>

                <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-md text-sm font-bold transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-foreground text-background shadow hover:opacity-90 h-11 active:scale-[0.98]">
                    Masuk ke Dashboard
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-border text-center">
                <p class="text-[11px] font-black text-muted-foreground  mb-2">Ingin Bergabung?
                </p>
                <a href="{{ route('affiliate.register') }}" wire:navigate
                    class="text-sm font-bold text-foreground hover:underline">Daftar Affiliator &rarr;</a>
            </div>
        </div>

        <p class="text-center text-xs text-muted-foreground mt-8 lowercase tracking-tight">
            &copy; {{ date('Y') }} RentSpace &mdash; Affiliator Program
        </p>
    </div>
</div>