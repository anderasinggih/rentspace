<?php

namespace App\Helpers;

use App\Models\Rental;

class CustomerHelper
{
    public static function getTier($ltv)
    {
        if ($ltv >= 6000000) return (object)['label' => 'LEGEND', 'color' => 'bg-primary text-primary-foreground shadow-sm'];
        if ($ltv >= 3000000) return (object)['label' => 'DIAMOND', 'color' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'];
        if ($ltv >= 1000000) return (object)['label' => 'PLATINUM', 'color' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'];
        if ($ltv >= 500000) return (object)['label' => 'GOLD', 'color' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'];
        if ($ltv >= 100000) return (object)['label' => 'SILVER', 'color' => 'border-border bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'];
        
        return (object)['label' => 'BRONZE', 'color' => 'border-transparent bg-secondary text-secondary-foreground'];
    }

    public static function getLtv($nik)
    {
        return Rental::where('nik', $nik)->sum('grand_total');
    }
}
