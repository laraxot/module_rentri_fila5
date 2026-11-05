<?php

declare(strict_types=1);

/*
 * Pagina RentriStatusPage.
 */

return [
    'navigation' => [
        'label' => 'Stato servizi RENTRI',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-signal',
        'sort' => 30,
    ],
    'title' => 'Stato dei servizi RENTRI',
    'credential' => [
        'heading' => 'Credenziale usata per il controllo',
        'none' => 'Nessuna credenziale attiva: creane una in "Credenziali RENTRI".',
    ],
    'services' => [
        'heading' => 'Servizi',
        'not_checked' => 'Premi "Verifica" per interrogare i servizi.',
    ],
    'columns' => [
        'service' => 'Servizio',
        'health' => 'Stato',
        'http_status' => 'HTTP',
        'message' => 'Messaggio',
        'checked_at' => 'Verificato il',
    ],
    'actions' => [
        'check' => [
            'label' => 'Verifica',
            'tooltip' => 'Interroga l\'endpoint di stato di ogni servizio',
            'icon' => 'heroicon-o-signal',
        ],
    ],
];
