@extends('layouts.app')

@section('content')
<div class="row mt-4">
    <div class="col-md-12">
        <h2 class="mb-3">Downtime Logs History</h2>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('logs.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date" class="form-label">Filter by Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="device_id" class="form-label">Filter by Device</label>
                        <select class="form-select" id="device_id" name="device_id">
                            <option value="">All Devices</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ request('device_id') == $device->id ? 'selected' : '' }}>
                                    {{ $device->name }} ({{ $device->ip_address }}) {!! $device->trashed() ? '- [Deleted]' : '' !!}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Went Offline At</th>
                            <th>Came Online At</th>
                            <th>Duration (Minutes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                {{ optional($log->device)->name ?? 'Unknown Device' }}
                                {!! optional($log->device)->trashed() ? '<span class="badge bg-warning text-dark">Deleted</span>' : '' !!}
                            </td>
                            <td>{{ optional($log->device)->ip_address ?? '-' }}</td>
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
                            <td colspan="5" class="text-center py-4">No downtime logs found for the given criteria.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="card-footer d-flex justify-content-center mt-3">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection