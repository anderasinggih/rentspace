<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get maintenance settings
        $isMaintenance = Setting::getVal('is_maintenance', '0') === '1';

        if ($isMaintenance) {
            // Check if user is NOT an admin
            // We allow admins to keep working even in maintenance mode
            $isAdmin = auth()->check() && auth()->user()->role === 'admin';

            if (!$isAdmin) {
                // If it's not an admin route, show maintenance page
                // Exempting logout and login routes if necessary, 
                // but usually we just block everything frontend.
                // If it's not an admin route or auth route, show maintenance page
                // We must exempt livewire routes so components keep working
                $isPathsExempt = $request->is('admin') || 
                                 $request->is('admin/*') || 
                                 $request->is('login') || 
                                 $request->is('logout') ||
                                 $request->is('livewire/*');

                if (!$isPathsExempt) {
                    return response()->view('errors.maintenance', [
                        'message' => Setting::getVal('maintenance_message', 'Kami akan segera kembali!')
                    ], 503);
                }
            }
        }

        return $next($request);
    }
}
