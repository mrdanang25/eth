<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class MonitoredDevice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'ip_address',
        'status',
        'last_checked_at',
        'failed_since',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'failed_since' => 'datetime',
        ];
    }
}
