<?php

declare(strict_types=1);

/*
 * Cache delle codifiche RENTRI.
 */

return [
    'label' => 'RENTRI code',
    'plural_label' => 'RENTRI codes',
    'fields' => [
        'list' => [
            'label' => 'List',
        ],
        'code' => [
            'label' => 'Code',
        ],
        'description' => [
            'label' => 'Description',
        ],
        'payload' => [
            'label' => 'Original data',
        ],
    ],
    'seed' => [
        'acceptance' => [
            'full' => 'Full acceptance',
            'partial' => 'Partial acceptance',
            'rejected' => 'Rejection',
        ],
    ],
];
