<?php

namespace App\Http\Controllers;

use App\Models\DowntimeLog;
use Illuminate\Http\Request;

class DowntimeLogController extends Controller
{
    public function index(Request $request)
    {
        $query = DowntimeLog::with(['device' => function($query) {
            $query->withTrashed();
        }]);

        if ($request->filled('date')) {
            $query->whereDate('went_offline_at', $request->date);
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $logs = $query->orderBy('went_offline_at', 'desc')->paginate(15);
        $devices = \App\Models\MonitoredDevice::withTrashed()->orderBy('name')->get();

        return view('logs.index', compact('logs', 'devices'));
    }
}
