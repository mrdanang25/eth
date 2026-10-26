<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitoredDevice;
use App\Models\DowntimeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DeviceStatusNotification;
use Carbon\Carbon;

class DeviceStatusController extends Controller
{
    /**
     * Get list of all IPs to monitor.
     */
    public function index(Request $request)
    {
        $this->verifyToken($request);

        $devices = MonitoredDevice::select('id', 'ip_address')->get();
        return response()->json($devices);
    }

    /**
     * Store ping results and evaluate downtime logic.
     */
    public function store(Request $request)
    {
        $this->verifyToken($request);

        $results = $request->input('results'); // Expected format: [['ip' => '192.168.1.1', 'status' => 'success|failed'], ...]

        if (!$results || !is_array($results)) {
            return response()->json(['error' => 'Invalid data format'], 400);
        }

        $now = now();

        foreach ($results as $result) {
            $device = MonitoredDevice::where('ip_address', $result['ip'])->first();

            if (!$device) continue;

            $device->last_checked_at = $now;

            if ($result['status'] === 'failed') {
                if ($device->status === 'online') {
                    if (is_null($device->failed_since)) {
                        // First failure
                        $device->failed_since = clone $now;
                    } else {
                        // Check if 1 minute threshold has passed
                        if ($device->failed_since->diffInSeconds($now) >= 60) {
                            $device->status = 'offline';

                            // Create Downtime Log
                            DowntimeLog::create([
                                'device_id' => $device->id,
                                'went_offline_at' => clone $now,
                            ]);

                            // Send Telegram Notification
                            $this->sendTelegramNotification($device, 'offline', $now);
                        }
                    }
                }
            } else if ($result['status'] === 'success') {
                // Reset failed_since if it was failing but didn't reach offline status
                $device->failed_since = null;

                if ($device->status === 'offline') {
                    $device->status = 'online';

                    // Close Downtime Log
                    $log = DowntimeLog::where('device_id', $device->id)
                        ->whereNull('came_online_at')
                        ->latest('went_offline_at')
                        ->first();

                    if ($log) {
                        $log->came_online_at = clone $now;
                        $log->total_duration_minutes = $log->went_offline_at->diffInMinutes($now);
                        $log->save();
                    }

                    // Send Telegram Notification
                    $this->sendTelegramNotification($device, 'online', $now);
                }
            }

            $device->save();
        }

        return response()->json(['message' => 'Processed successfully']);
    }

    private function verifyToken(Request $request)
    {
        $token = $request->bearerToken();
        $expectedToken = env('PING_DAEMON_TOKEN', 'default-secure-token-123');

        if ($token !== $expectedToken) {
            abort(401, 'Unauthorized API Token');
        }
    }

    private function sendTelegramNotification($device, $status, $time)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            Log::warning("Telegram config missing. Cannot send {$status} notification for {$device->name}.");
            return;
        }

        $emoji = $status === 'online' ? '✅' : '❌';
        $statusText = strtoupper($status);
        $message = "{$emoji} <b>Device {$statusText}</b>\n"
                 . "<b>Name:</b> {$device->name}\n"
                 . "<b>IP:</b> {$device->ip_address}\n"
                 . "<b>Time:</b> {$time->format('Y-m-d H:i:s')}";

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }
    }
}
