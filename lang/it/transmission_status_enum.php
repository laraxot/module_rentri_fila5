<?php

declare(strict_types=1);

/*
 * Chiavi lette da EnumTrait: rentri::transmission_status_enum.values.<valore>.<attributo>.
 */

return [
    'values' => [
        'pending' => [
            'label' => 'In attesa',
            'color' => 'gray',
            'icon' => 'heroicon-o-clock',
            'description' => 'Registrata, non ancora inviata',
        ],
        'submitted' => [
            'label' => 'Inviata',
            'color' => 'info',
            'icon' => 'heroicon-o-paper-airplane',
            'description' => 'Accettata da RENTRI, esito asincrono in attesa',
        ],
        'succeeded' => [
            'label' => 'Riuscita',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
            'description' => 'Esito positivo',
        ],
        'failed' => [
            'label' => 'Fallita',
            'color' => 'danger',
            'icon' => 'heroicon-o-x-circle',
            'description' => 'Esito negativo o errore',
        ],
    ],
];
