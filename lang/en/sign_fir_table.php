<?php

declare(strict_types=1);

return [
    'fields' => [
        'pin' => [
            'label' => 'Signing PIN',
            'helper_text' => 'RENTRI remote signature PIN of the credential user. It is not stored.',
        ],
    ],
    'modal_heading' => 'Sign FIR :fir?',
    'modal_description' => 'RENTRI remote signature asks for the PIN; the outcome arrives when the transaction is checked.',
    'sent' => 'Signature of FIR :fir sent to RENTRI',
    'error' => 'Signature not applied',
];
