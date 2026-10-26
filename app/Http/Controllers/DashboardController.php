<?php

namespace App\Http\Controllers;

use App\Models\MonitoredDevice;
use App\Models\DowntimeLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalDevices = MonitoredDevice::count();
        $offlineDevicesCount = MonitoredDevice::where('status', 'offline')->count();
        $recentLogs = DowntimeLog::with(['device' => function($query) {
            $query->withTrashed();
        }])->orderBy('went_offline_at', 'desc')->take(5)->get();

        return view('dashboard', compact('totalDevices', 'offlineDevicesCount', 'recentLogs'));
    }
}
