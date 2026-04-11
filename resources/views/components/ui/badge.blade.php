@props([
    'variant' => 'default',
    'class' => ''
])

@php
    $baseClasses = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2';
    
    $variantClasses = match($variant) {
        'default' => 'bg-primary border-transparent text-primary-foreground hover:bg-primary/80',
        'secondary' => 'bg-secondary border-transparent text-secondary-foreground hover:bg-secondary/80',
        'destructive' => 'bg-destructive border-transparent text-destructive-foreground hover:bg-destructive/80',
        'outline' => 'text-foreground border border-input',
        'blue' => 'bg-blue-50 border-transparent text-blue-700 dark:bg-blue-950 dark:text-blue-300',
        'green' => 'bg-green-50 border-transparent text-green-700 dark:bg-green-950 dark:text-green-300',
        'sky' => 'bg-sky-50 border-transparent text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'purple' => 'bg-purple-50 border-transparent text-purple-700 dark:bg-purple-950 dark:text-purple-300',
        'red' => 'bg-red-50 border-transparent text-red-700 dark:bg-red-950 dark:text-red-300',
        'amber' => 'bg-amber-50 border-transparent text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'zinc' => 'bg-zinc-100 border-transparent text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
        default => 'bg-primary border-transparent text-primary-foreground hover:bg-primary/80',
    };
@endphp

<div {{ $attributes->merge(['class' => $baseClasses . ' ' . $variantClasses . ' ' . $class]) }}>
    {{ $slot }}
</div>
