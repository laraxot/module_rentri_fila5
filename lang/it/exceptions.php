<?php

declare(strict_types=1);

/*
 * Messaggi delle eccezioni di dominio del modulo Rentri.
 */

return [
    'credential' => [
        'not_found' => 'Nessuna credenziale RENTRI attiva e valida per l\'unità locale :site.',
        'certificate_missing' => 'Il certificato .p12 della credenziale ":credential" non è presente sul disco privato.',
        'certificate_unreadable' => 'Impossibile leggere il certificato .p12 della credenziale ":credential" (password errata o file non valido): :error',
        'remote_signature_missing' => 'La credenziale ":credential" non ha un identificativo di firma remota: impossibile firmare il FIR.',
    ],
    'mapping' => [
        'missing_code' => 'Nessuna codifica RENTRI configurata in rentri.codes.:list per il valore ":value".',
        'missing_value' => 'Dato obbligatorio ":field" mancante su :subject.',
    ],
    'request' => [
        'failed' => 'RENTRI ha risposto :status a :method :path: :detail',
    ],
    'sign' => [
        'incomplete_response' => 'Il servizio di firma remota RENTRI non ha restituito firma, SAD o certificato.',
    ],
    'register' => [
        'nothing_to_transmit' => 'Nessuna scrittura del registro da trasmettere.',
    ],
    'transmission' => [
        'immutable' => 'Le trasmissioni RENTRI sono un registro: non si possono cancellare.',
        'not_retryable' => 'La richiesta salvata contiene dati oscurati (PIN, SAD...): ripeti l\'operazione invece di riprovarla.',
        'polling_timeout' => 'Nessun esito da RENTRI dopo :attempts verifiche.',
    ],
];
