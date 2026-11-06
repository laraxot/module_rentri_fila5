<?php

declare(strict_types=1);

/*
 * Trasmissioni RENTRI (sola lettura): Resource, infolist, tabella e azioni 'verifica esito' / 'riprova'.
 */

return [
    'navigation' => [
        'label' => 'RENTRI transmissions',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-arrows-right-left',
        'sort' => 20,
    ],
    'label' => 'RENTRI transmission',
    'plural_label' => 'RENTRI transmissions',
    'plural' => [
        'label' => 'RENTRI transmissions',
    ],
    'fields' => [
        'created_at' => [
            'label' => 'Created at',
        ],
        'service' => [
            'label' => 'Service',
        ],
        'method' => [
            'label' => 'Method',
        ],
        'path' => [
            'label' => 'Path',
        ],
        'subject_type' => [
            'label' => 'Subject',
        ],
        'subject_id' => [
            'label' => 'Subject ID',
        ],
        'credential.name' => [
            'label' => 'Credential',
        ],
        'request_payload' => [
            'label' => 'Request (redacted)',
            'helper_text' => 'PINs, passwords and keys are never stored in clear text',
        ],
        'status' => [
            'label' => 'Status',
        ],
        'response_status' => [
            'label' => 'HTTP status',
        ],
        'transaction_id' => [
            'label' => 'RENTRI transaction ID',
        ],
        'attempts' => [
            'label' => 'Attempts',
        ],
        'error' => [
            'label' => 'Error',
        ],
        'completed_at' => [
            'label' => 'Completed at',
        ],
        'response_body' => [
            'label' => 'Response',
        ],
    ],
    'actions' => [
        'view' => [
            'label' => 'Details',
        ],
        'back' => [
            'label' => 'Back',
        ],
        'poll' => [
            'label' => 'Check outcome',
            'tooltip' => 'Ask RENTRI for the status of the asynchronous transaction',
            'icon' => 'heroicon-o-arrow-path',
        ],
        'sign' => [
            'label' => 'Sign FIR',
            'tooltip' => 'Remote signature of the digital FIR with the PIN',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'retry' => [
            'label' => 'Retry',
            'tooltip' => 'Send the recorded request again',
            'modal_heading' => 'Retry the transmission?',
            'modal_description' => 'The same request is sent to RENTRI again with a new authentication token.',
            'icon' => 'heroicon-o-arrow-uturn-right',
        ],
    ],
    'sections' => [
        'request' => [
            'label' => 'Request',
            'heading' => 'Request',
        ],
        'outcome' => [
            'label' => 'Outcome',
            'heading' => 'Outcome',
        ],
    ],
];
