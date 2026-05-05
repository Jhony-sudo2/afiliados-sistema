<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;
use Throwable;

final class Audit
{
    public static function log(string $module, string $action, ?string $recordId = null, ?array $payload = null): void
    {
        try {
            $json = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;

            Database::connection()->prepare('
                INSERT INTO audit_logs (user_id, module_name, action_name, record_id, payload_json, ip_address, created_at)
                VALUES (:user_id, :module_name, :action_name, :record_id, :payload_json, :ip_address, NOW())
            ')->execute([
                'user_id' => Auth::id(),
                'module_name' => $module,
                'action_name' => $action,
                'record_id' => $recordId,
                'payload_json' => $json,
                'ip_address' => current_ip(),
            ]);
        } catch (Throwable) {
            // La auditoría nunca debe romper la operación principal.
        }
    }
}
