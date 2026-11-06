<?php

declare(strict_types=1);

/*
 * Trasmissioni RENTRI (sola lettura): Resource, infolist, tabella e azioni 'verifica esito' / 'riprova'.
 */

return [
    'navigation' => [
        'label' => 'Trasmissioni RENTRI',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-arrows-right-left',
        'sort' => 20,
    ],
    'label' => 'Trasmissione RENTRI',
    'plural_label' => 'Trasmissioni RENTRI',
    'plural' => [
        'label' => 'Trasmissioni RENTRI',
    ],
    'fields' => [
        'created_at' => [
            'label' => 'Creata il',
        ],
        'service' => [
            'label' => 'Servizio',
        ],
        'method' => [
            'label' => 'Metodo',
        ],
        'path' => [
            'label' => 'Percorso',
        ],
        'subject_type' => [
            'label' => 'Oggetto',
        ],
        'subject_id' => [
            'label' => 'ID oggetto',
        ],
        'credential.name' => [
            'label' => 'Credenziale',
        ],
        'request_payload' => [
            'label' => 'Richiesta (oscurata)',
            'helper_text' => 'PIN, password e chiavi non sono mai salvati in chiaro',
        ],
        'status' => [
            'label' => 'Stato',
        ],
        'response_status' => [
            'label' => 'Codice HTTP',
        ],
        'transaction_id' => [
            'label' => 'ID transazione RENTRI',
        ],
        'attempts' => [
            'label' => 'Tentativi',
        ],
        'error' => [
            'label' => 'Errore',
        ],
        'completed_at' => [
            'label' => 'Completata il',
        ],
        'response_body' => [
            'label' => 'Risposta',
        ],
    ],
    'actions' => [
        'view' => [
            'label' => 'Dettaglio',
        ],
        'back' => [
            'label' => 'Indietro',
        ],
        'poll' => [
            'label' => 'Verifica esito',
            'tooltip' => 'Interroga RENTRI sullo stato della transazione asincrona',
            'icon' => 'heroicon-o-arrow-path',
        ],
        'sign' => [
            'label' => 'Firma FIR',
            'tooltip' => 'Firma remota del FIR digitale con il PIN',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'retry' => [
            'label' => 'Riprova',
            'tooltip' => 'Reinvia la richiesta registrata',
            'modal_heading' => 'Riprovare la trasmissione?',
            'modal_description' => 'La stessa richiesta viene reinviata a RENTRI con un nuovo token di autenticazione.',
            'icon' => 'heroicon-o-arrow-uturn-right',
        ],
    ],
    'sections' => [
        'request' => [
            'label' => 'Richiesta',
            'heading' => 'Richiesta',
        ],
        'outcome' => [
            'label' => 'Esito',
            'heading' => 'Esito',
        ],
    ],
];
