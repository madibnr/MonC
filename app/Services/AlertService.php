<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * Dispatch an alert to all subscribed users.
     */
    public function dispatch(Alert $alert): void
    {
        $subscriptions = AlertSubscription::active()
            ->forType($alert->type)
            ->with('user')
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                match ($subscription->channel) {
                    'web' => $this->sendWebNotification($subscription->user, $alert),
                    'email' => $this->sendEmailNotification($subscription->user, $alert),
                    'telegram' => $this->sendTelegramNotification($subscription->user, $alert),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::warning("Failed to send {$subscription->channel} alert to user {$subscription->user_id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Web notification (stored in alerts table, shown in UI).
     * The alert is already created, so this is a no-op for web.
     */
    protected function sendWebNotification(User $user, Alert $alert): void
    {
        // Web notifications are handled by the alerts table itself.
        // The UI polls or checks for unread alerts.
        // This method exists for extensibility (e.g., WebSocket push).
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(User $user, Alert $alert): void
    {
        if (! $user->email) {
            return;
        }

        try {
            Mail::raw(
                "MonC Alert [{$alert->severity}]\n\n".
                "{$alert->title}\n\n".
                "{$alert->message}\n\n".
                "Time: {$alert->created_at->format('d M Y H:i:s')}\n".
                "Type: {$alert->type}\n\n".
                '-- MonC Monitoring CCTV System',
                function ($message) use ($user, $alert) {
                    $message->to($user->email)
                        ->subject("[MonC Alert] {$alert->title}");
                }
            );

            Log::info("Email alert sent to {$user->email} for alert #{$alert->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send email alert to {$user->email}: {$e->getMessage()}");
        }
    }

    /**
     * Send Telegram notification via webhook.
     */
    protected function sendTelegramNotification(User $user, Alert $alert): void
    {
        $botToken = config('monc.telegram.bot_token');
        $chatId = $user->telegram_chat_id;

        if (! $botToken || ! $chatId) {
            return;
        }

        $severityEmoji = match ($alert->severity) {
            'critical' => '🔴',
            'warning' => '🟡',
            'info' => '🔵',
            default => '⚪',
        };

        $text = "{$severityEmoji} *MonC Alert*\n\n".
            "*{$alert->title}*\n".
            "{$alert->message}\n\n".
            "🕐 {$alert->created_at->format('d M Y H:i:s')}\n".
            "📋 Type: `{$alert->type}`";

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            if ($response->successful()) {
                Log::info("Telegram alert sent to chat {$chatId} for alert #{$alert->id}");
            } else {
                Log::warning("Telegram API error for alert #{$alert->id}: ".$response->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram alert: {$e->getMessage()}");
        }
    }

    /**
     * Get unread alert count for web UI badge.
     */
    public function getUnreadCount(): int
    {
        return Alert::unread()->unresolved()->count();
    }

    /**
     * Get recent unresolved alerts for dashboard.
     */
    public function getRecentAlerts(int $limit = 20): Collection
    {
        return Alert::unresolved()
            ->latest()
            ->take($limit)
            ->get();
    }
}
