<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private ?Request $request = null)
    {
        $this->request ??= request();
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        ?User $actor,
        string $action,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => $this->request?->ip(),
            'user_agent' => $this->request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
