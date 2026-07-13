<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_identifier',
        'action',
        'object_class',
        'object_id',
        'data_before',
        'data_after',
        'ip_address',
        'user_agent',
        'os',
        'browser',
        'result',
    ];

    protected $casts = [
        'data_before' => 'array',
        'data_after' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (AuditLog $log) {
            if (empty($log->id)) {
                $log->id = (string) Str::uuid();
            }
        });
    }
}
