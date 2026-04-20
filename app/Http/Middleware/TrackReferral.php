<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AffiliatorProfile;

class TrackReferral
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->has('ref')) {
            $ref = strtoupper($request->query('ref'));
            
            // Validate if ref exists in affiliator_profiles
            $exists = AffiliatorProfile::where('referral_code', $ref)->exists();
            
            if ($exists) {
                // Store in session and set a 30-day cookie
                session(['affiliate_ref' => $ref]);
                \Illuminate\Support\Facades\Cookie::queue('affiliate_ref', $ref, 60 * 24 * 30);
            }
        }

        return $next($request);
    }
}
