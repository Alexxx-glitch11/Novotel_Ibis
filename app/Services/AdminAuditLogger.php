<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogger
{
    public function record(Request $request, string $action, string $entityType, int $entityId, ?array $old = null, ?array $new = null): void
    {
        AuditLog::create([
            'users_id' => $request->user()?->users_id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
