<?php

declare(strict_types=1);

/*
 * Cache delle codifiche RENTRI.
 */

return [
    'label' => 'Codifica RENTRI',
    'plural_label' => 'Codifiche RENTRI',
    'fields' => [
        'list' => [
            'label' => 'Lista',
        ],
        'code' => [
            'label' => 'Codice',
        ],
        'description' => [
            'label' => 'Descrizione',
        ],
        'payload' => [
            'label' => 'Dati originali',
        ],
    ],
    'seed' => [
        'acceptance' => [
            'full' => 'Accettazione totale',
            'partial' => 'Accettazione parziale',
            'rejected' => 'Respingimento',
        ],
    ],
];
