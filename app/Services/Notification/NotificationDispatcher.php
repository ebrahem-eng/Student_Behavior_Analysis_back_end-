<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Alert;
use App\Models\ConsentLog;
use App\Notifications\RiskAlertNotification;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    /**
     * Dispatch an alert notification to a recipient user.
     */
    public function dispatchAlert(User $recipient, Alert $alert): bool
    {
        try {
            // Check if the user has consented to receiving monitoring/risk notifications
            $hasConsent = ConsentLog::where('user_id', $recipient->id)
                ->where('consent_type', 'notifications')
                ->latest()
                ->value('is_granted');

            // Default to sending unless explicitly revoked
            if ($hasConsent === false) {
                Log::info("Notification skipped for User {$recipient->id}: consent revoked.");
                return false;
            }

            $recipient->notify(new RiskAlertNotification($alert));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to dispatch alert notification: " . $e->getMessage());
            return false;
        }
    }
}
