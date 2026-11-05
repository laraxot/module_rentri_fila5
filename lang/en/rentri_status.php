<?php

declare(strict_types=1);

/*
 * Pagina RentriStatusPage.
 */

return [
    'navigation' => [
        'label' => 'RENTRI service status',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-signal',
        'sort' => 30,
    ],
    'title' => 'RENTRI service status',
    'credential' => [
        'heading' => 'Credential used for the check',
        'none' => 'No active credential: create one in "RENTRI credentials".',
    ],
    'services' => [
        'heading' => 'Services',
        'not_checked' => 'Press "Check" to query the services.',
    ],
    'columns' => [
        'service' => 'Service',
        'health' => 'Status',
        'http_status' => 'HTTP',
        'message' => 'Message',
        'checked_at' => 'Checked at',
    ],
    'actions' => [
        'check' => [
            'label' => 'Check',
            'tooltip' => 'Query the status endpoint of each service',
            'icon' => 'heroicon-o-signal',
        ],
    ],
];
