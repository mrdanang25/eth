<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MonitoredDevice;
use App\Models\DowntimeLog;
use Carbon\Carbon;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_access_devices_list_but_not_create()
    {
        $viewer = User::factory()->create([
            'username' => 'viewer',
            'role' => 'viewer',
        ]);

        // Should be able to view index
        $response = $this->actingAs($viewer)->get('/devices');
        $response->assertStatus(200);

        // Should not be able to view create form
        $response = $this->actingAs($viewer)->get('/devices/create');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_devices_page()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/devices');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_device()
    {
        $admin = User::factory()->create([
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/devices', [
            'name' => 'Localhost',
            'ip_address' => '127.0.0.1',
        ]);

        $response->assertRedirect('/devices');
        $this->assertDatabaseHas('monitored_devices', [
            'name' => 'Localhost',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_api_ping_results_updates_device_status()
    {
        $device = MonitoredDevice::create([
            'name' => 'Test PC',
            'ip_address' => '192.168.1.50',
            'status' => 'online',
        ]);

        $token = 'default-secure-token-123';
        config(['app.env' => 'testing', 'PING_DAEMON_TOKEN' => $token]);

        $results = [
            ['ip' => '192.168.1.50', 'status' => 'failed']
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/ping-results', ['results' => $results]);

        $response->assertStatus(200);

        $device->refresh();
        // First fail sets failed_since, but status remains online due to 1 minute threshold
        $this->assertNotNull($device->failed_since);
        $this->assertEquals('online', $device->status);

        // Simulate time passing (over 1 min)
        $device->failed_since = Carbon::now()->subSeconds(65);
        $device->save();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/ping-results', ['results' => $results]);

        $device->refresh();
        // Should now be offline
        $this->assertEquals('offline', $device->status);
        $this->assertDatabaseCount('downtime_logs', 1);

        // Recover
        $results = [
            ['ip' => '192.168.1.50', 'status' => 'success']
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/ping-results', ['results' => $results]);

        $device->refresh();
        $this->assertEquals('online', $device->status);
        $this->assertNull($device->failed_since);

        $log = DowntimeLog::first();
        $this->assertNotNull($log->came_online_at);
        $this->assertNotNull($log->total_duration_minutes);
    }
}
