<?php

namespace App\Traits;

use App\Models\StaffLog;
use Illuminate\Support\Facades\Request;

trait LogsStaffActivity
{
    /**
     * Log staff activity.
     * 
     * @param string $action Action name (e.g., 'mark_as_paid')
     * @param object|null $target The model being affected
     * @param string|null $description Narrative description
     * @param array|null $before Data before change
     * @param array|null $after Data after change
     */
    public function logActivity($action, $target = null, $description = null, $before = null, $after = null)
    {
        // Only log if the user role is not admin (optional: log everyone, 
        // but user specifically asked for staff logs tracking)
        // I will log everyone but clearly mark the user role.
        
        StaffLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target ? $target->id : null,
            'description' => $description,
            'data_before' => $before,
            'data_after' => $after,
            'ip_address' => Request::ip(),
        ]);
    }
}
