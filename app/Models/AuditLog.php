<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'field',
        'old_value',
        'new_value',
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?string $field = null,
        mixed $old = null,
        mixed $new = null,
    ): self {
        return static::query()->create([
            'auditable_type' => $subject ? $subject::class : 'system',
            'auditable_id' => $subject?->getKey() ?? 0,
            'field' => $field ?? 'event',
            'old_value' => $old !== null ? (string) $old : null,
            'new_value' => $new !== null ? (string) $new : null,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
