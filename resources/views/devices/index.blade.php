@extends('layouts.app')

@section('content')
<div class="row mt-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Monitored Devices</h2>
            <a href="{{ route('devices.create') }}" class="btn btn-success">Add New Device</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Last Checked</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                        <tr>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->ip_address }}</td>
                            <td>
                                @if($device->status === 'online')
                                    <span class="badge bg-success">Online</span>
                                @else
                                    <span class="badge bg-danger">Offline</span>
                                    @if($device->failed_since)
                                        <br><small class="text-muted">Since {{ $device->failed_since->diffForHumans() }}</small>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $device->last_checked_at ? $device->last_checked_at->diffForHumans() : 'Never' }}</td>
                            <td>
                                <a href="{{ route('devices.edit', $device->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                <form action="{{ route('devices.destroy', $device->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this device? Downtime logs will be preserved.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No devices monitored yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection