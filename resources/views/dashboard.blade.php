@extends('layouts.app')

@section('content')
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card bg-primary text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Total Monitored PCs</h5>
                <h1 class="display-3">{{ $totalDevices }}</h1>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card {{ $offlineDevicesCount > 0 ? 'bg-danger' : 'bg-success' }} text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Current Offline PCs</h5>
                <h1 class="display-3">{{ $offlineDevicesCount }}</h1>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Downtime Events</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Went Offline At</th>
                            <th>Came Online At</th>
                            <th>Duration (Minutes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->device->name }} {!! $log->device->trashed() ? '<span class="badge bg-warning text-dark">Deleted</span>' : '' !!}</td>
                            <td>{{ $log->device->ip_address }}</td>
                            <td>{{ $log->went_offline_at->format('M d, Y H:i:s') }}</td>
                            <td>
                                @if($log->came_online_at)
                                    {{ $log->came_online_at->format('M d, Y H:i:s') }}
                                @else
                                    <span class="badge bg-danger">Currently Offline</span>
                                @endif
                            </td>
                            <td>{{ $log->total_duration_minutes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No recent downtime events. Everything is running smoothly!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recentLogs->count() > 0)
            <div class="card-footer text-end">
                <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-secondary">View All Logs</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection