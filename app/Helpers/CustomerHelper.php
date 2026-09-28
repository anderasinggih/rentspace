<?php

namespace App\Helpers;

use App\Models\Rental;

class CustomerHelper
{
    public static function tiers()
    {
        return [
            ['label' => 'BRONZE', 'threshold' => 0, 'color' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'],
            ['label' => 'SILVER', 'threshold' => 100000, 'color' => 'bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-slate-100 border-slate-300 dark:border-slate-700 shadow-sm'],
            ['label' => 'GOLD', 'threshold' => 500000, 'color' => 'bg-gradient-to-r from-amber-400/20 to-yellow-500/20 text-amber-700 dark:text-amber-400 border-amber-500/30 shadow-sm shadow-amber-500/10'],
            ['label' => 'PLATINUM', 'threshold' => 1000000, 'color' => 'bg-gradient-to-r from-indigo-500/20 to-purple-500/20 text-indigo-700 dark:text-indigo-400 border-indigo-500/30 shadow-sm shadow-indigo-500/10'],
            ['label' => 'DIAMOND', 'threshold' => 3000000, 'color' => 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-700 dark:text-emerald-400 border-emerald-500/30 shadow-sm shadow-emerald-500/10'],
            ['label' => 'LEGEND', 'threshold' => 6000000, 'color' => 'bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-lg shadow-red-500/50 ring-2 ring-red-500/30'],
        ];
    }

    public static function getTier($ltv)
    {
        $tiers = array_reverse(self::tiers());
        foreach ($tiers as $tier) {
            if ($ltv >= $tier['threshold']) {
                return (object)$tier;
            }
        }
        return (object)$tiers[count($tiers)-1];
    }

    public static function getNextTier($ltv)
    {
        $tiers = self::tiers();
        foreach ($tiers as $tier) {
            if ($ltv < $tier['threshold']) {
                return (object)$tier;
            }
        }
        return null; // Already Legend
    }

    public static function getLtv($no_wa)
    {
        return Rental::where('no_wa', $no_wa)->sum('grand_total');
    }

    public static function formatWa($number)
    {
        if (!$number) return '';
        
        // Remove all non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // If starts with 0, replace with 62
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        // If starts with 8 (common mistake), prepend 62
        elseif (str_starts_with($number, '8')) {
            $number = '62' . $number;
        }
        
        return $number;
    }
}
