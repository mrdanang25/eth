<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeLog extends Model
{
    protected $fillable = [
        'device_id',
        'went_offline_at',
        'came_online_at',
        'total_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'went_offline_at' => 'datetime',
            'came_online_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitoredDevice::class, 'device_id')->withTrashed();
    }
}
