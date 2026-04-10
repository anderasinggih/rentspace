<div class="min-h-screen pt-24 pb-12 px-4 sm:px-6 lg:px-8 bg-muted/20 flex flex-col justify-center items-center">
    <!-- Back to public -->
    <a href="{{ route('public.timeline') }}" wire:navigate class="absolute top-8 left-8 text-sm font-medium text-muted-foreground hover:text-foreground flex items-center transition-colors">
        ← Kembali ke Web
    </a>

    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-foreground">Login Admin</h1>
            <p class="mt-2 text-sm text-muted-foreground">Masuk untuk mengelola data RentSpace.</p>
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
                    <input id="email" type="email" wire:model="email" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" required autofocus>
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-sm font-medium leading-none">Password</label>
                    <input id="password" type="password" wire:model="password" class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-1 shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" required>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10">
                    Masuk ke Dashboard
                </button>
            </form>
        </div>
        
        <p class="text-center text-xs text-muted-foreground mt-8">
            &copy; {{ date('Y') }} RentSpace. All rights reserved.
        </p>
    </div>
</div>
