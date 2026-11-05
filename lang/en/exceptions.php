<?php

declare(strict_types=1);

/*
 * Messaggi delle eccezioni di dominio del modulo Rentri.
 */

return [
    'credential' => [
        'not_found' => 'No active and valid RENTRI credential for local unit :site.',
        'certificate_missing' => 'The .p12 certificate of credential ":credential" is missing from the private disk.',
        'certificate_unreadable' => 'Unable to read the .p12 certificate of credential ":credential" (wrong password or invalid file): :error',
        'remote_signature_missing' => 'Credential ":credential" has no remote signature identifier: the FIR cannot be signed.',
    ],
    'mapping' => [
        'missing_code' => 'No RENTRI code configured in rentri.codes.:list for value ":value".',
        'missing_value' => 'Required value ":field" missing on :subject.',
    ],
    'request' => [
        'failed' => 'RENTRI answered :status to :method :path: :detail',
    ],
    'sign' => [
        'incomplete_response' => 'The RENTRI remote signature service did not return signature, SAD or certificate.',
    ],
    'register' => [
        'nothing_to_transmit' => 'No register entries to transmit.',
    ],
    'transmission' => [
        'immutable' => 'RENTRI transmissions are a log: they cannot be deleted.',
        'not_retryable' => 'The stored request contains redacted data (PIN, SAD...): repeat the operation instead of retrying it.',
        'polling_timeout' => 'No outcome from RENTRI after :attempts checks.',
    ],
];
