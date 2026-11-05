<?php

declare(strict_types=1);

/*
 * Chiavi lette da EnumTrait: rentri::transmission_status_enum.values.<valore>.<attributo>.
 */

return [
    'values' => [
        'pending' => [
            'label' => 'Pending',
            'color' => 'gray',
            'icon' => 'heroicon-o-clock',
            'description' => 'Recorded, not sent yet',
        ],
        'submitted' => [
            'label' => 'Submitted',
            'color' => 'info',
            'icon' => 'heroicon-o-paper-airplane',
            'description' => 'Accepted by RENTRI, asynchronous outcome pending',
        ],
        'succeeded' => [
            'label' => 'Succeeded',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
            'description' => 'Positive outcome',
        ],
        'failed' => [
            'label' => 'Failed',
            'color' => 'danger',
            'icon' => 'heroicon-o-x-circle',
            'description' => 'Negative outcome or error',
        ],
    ],
];
