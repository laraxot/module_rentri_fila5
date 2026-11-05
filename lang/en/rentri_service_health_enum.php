<?php

declare(strict_types=1);

/*
 * Chiavi lette da EnumTrait: rentri::rentri_service_health_enum.values.<valore>.<attributo>.
 */

return [
    'values' => [
        'ok' => [
            'label' => 'Operational',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
        ],
        'warning' => [
            'label' => 'Warning',
            'color' => 'warning',
            'icon' => 'heroicon-o-exclamation-triangle',
        ],
        'error' => [
            'label' => 'Unavailable',
            'color' => 'danger',
            'icon' => 'heroicon-o-x-circle',
        ],
    ],
];
