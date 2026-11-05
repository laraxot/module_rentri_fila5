<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Spatie\QueueableAction\QueueableAction;

/**
 * Valore dell'header `Digest` (RFC 3230) del pattern ModI INTEGRITY_REST: `SHA-256=<base64 dello SHA-256 del body>`.
 * Il digest va calcolato sugli stessi byte inviati: per questo SendRentriRequestAction serializza il JSON una sola volta.
 */
class BuildDigestHeaderAction
{
    use QueueableAction;

    public function execute(string $body): string
    {
        return 'SHA-256='.base64_encode(hash('sha256', $body, true));
    }
}
