{{-- Stats Section (Clean Apple / Shadcn Metrics Card) --}}
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 mt-8 sm:mt-10 mb-12">
    <div class="grid grid-cols-3 divide-x divide-border bg-card border border-border shadow-sm rounded-2xl overflow-hidden py-1 relative">
        <!-- Total Transaksi -->
        <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-4 sm:py-6 transition-all duration-200 hover:bg-muted/50 cursor-default" 
             data-haptic="light"
             x-data="{ 
                 target: {{ $statsTotalRentals }}, 
                 display: '0', 
                 format(val) {
                     if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                     return Math.floor(val);
                 },
                 run() { 
                     let start = null;
                     const duration = 2000;
                     const animate = (timestamp) => {
                         if (!start) start = timestamp;
                         const progress = timestamp - start;
                         const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                         this.display = this.format(easeOut * this.target);
                         if (progress < duration) requestAnimationFrame(animate);
                     };
                     requestAnimationFrame(animate);
                 } 
             }" x-intersect.once="run()">
            <span class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1 sm:mb-2">Transaksi</span>
            <span class="text-xl sm:text-4xl font-black text-foreground"><span x-text="display"></span><span class="text-muted-foreground/60 text-base sm:text-2xl ml-0.5">+</span></span>
        </div>

        <!-- Pelanggan -->
        <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-4 sm:py-6 transition-all duration-200 hover:bg-muted/50 cursor-default" 
             data-haptic="light"
             x-data="{ 
                 target: {{ $statsTotalUsers }}, 
                 display: '0', 
                 format(val) {
                     if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                     return Math.floor(val);
                 },
                 run() { 
                     let start = null;
                     const duration = 2200;
                     const animate = (timestamp) => {
                         if (!start) start = timestamp;
                         const progress = timestamp - start;
                         const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                         this.display = this.format(easeOut * this.target);
                         if (progress < duration) requestAnimationFrame(animate);
                     };
                     requestAnimationFrame(animate);
                 } 
             }" x-intersect.once="run()">
            <span class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1 sm:mb-2">Pelanggan</span>
            <span class="text-xl sm:text-4xl font-black text-foreground"><span x-text="display"></span><span class="text-muted-foreground/60 text-base sm:text-2xl ml-0.5">+</span></span>
        </div>

        <!-- Jam Disewa -->
        <div class="group relative flex flex-col items-center text-center px-1 sm:px-4 py-4 sm:py-6 transition-all duration-200 hover:bg-muted/50 cursor-default" 
             data-haptic="light"
             x-data="{ 
                 target: {{ $statsTotalHours }}, 
                 display: '0', 
                 format(val) {
                     if (val >= 1000) return (val/1000).toFixed(1).replace('.0', '') + 'K';
                     return Math.floor(val);
                 },
                 run() { 
                     let start = null;
                     const duration = 2500;
                     const animate = (timestamp) => {
                         if (!start) start = timestamp;
                         const progress = timestamp - start;
                         const easeOut = 1 - Math.pow(1 - Math.min(progress / duration, 1), 3);
                         this.display = this.format(easeOut * this.target);
                         if (progress < duration) requestAnimationFrame(animate);
                     };
                     requestAnimationFrame(animate);
                 } 
             }" x-intersect.once="run()">
            <span class="text-[10px] sm:text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-1 sm:mb-2">Jam Disewa</span>
            <span class="text-xl sm:text-4xl font-black text-foreground"><span x-text="display"></span><span class="text-muted-foreground/60 text-base sm:text-2xl ml-0.5">+</span></span>
        </div>
    </div>
</div>
