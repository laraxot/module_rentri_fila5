<?php

declare(strict_types=1);

return [
    'fields' => [
        'pin' => [
            'label' => 'PIN di firma',
            'helper_text' => 'PIN della firma remota RENTRI dell\'utente della credenziale. Non viene salvato.',
        ],
    ],
    'modal_heading' => 'Firmare il FIR :fir?',
    'modal_description' => 'La firma remota RENTRI chiede il PIN; l\'esito arriva con la verifica della transazione.',
    'sent' => 'Firma del FIR :fir inviata a RENTRI',
    'error' => 'Firma non eseguita',
];
