<?php

declare(strict_types=1);

/*
 * Stati StatoFormulario RENTRI (verificati). Chiavi lette da EnumTrait: rentri::rentri_fir_state_enum.values.<valore>.<attributo>.
 */

return [
    'values' => [
        'InserimentoQuantita' => [
            'label' => 'Quantity entry',
            'color' => 'gray',
            'icon' => 'heroicon-o-scale',
        ],
        'InserimentoQuantitaTrasportoIniziale' => [
            'label' => 'Quantity and initial transport entry',
            'color' => 'gray',
            'icon' => 'heroicon-o-scale',
        ],
        'InserimentoTrasportoIniziale' => [
            'label' => 'Initial transport entry',
            'color' => 'gray',
            'icon' => 'heroicon-o-truck',
        ],
        'FirmaProduttoreTrasportatoreIniziale' => [
            'label' => 'Producer and initial transporter signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaTrasportatoreIniziale' => [
            'label' => 'Initial transporter signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaProduttore' => [
            'label' => 'Producer signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'InserimentoTrasportoSuccessivo' => [
            'label' => 'Subsequent transport entry',
            'color' => 'gray',
            'icon' => 'heroicon-o-truck',
        ],
        'FirmaTrasportatoreSuccessivo' => [
            'label' => 'Subsequent transporter signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaAnnotazione' => [
            'label' => 'Annotation signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaTrasbordoParziale' => [
            'label' => 'Partial transshipment signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaTrasbordoTotale' => [
            'label' => 'Full transshipment signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaSostaTecnica' => [
            'label' => 'Technical stop signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'InserimentoAccettazione' => [
            'label' => 'Acceptance entry',
            'color' => 'info',
            'icon' => 'heroicon-o-inbox-arrow-down',
        ],
        'FirmaAccettazione' => [
            'label' => 'Acceptance signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'Accettato' => [
            'label' => 'Accepted',
            'color' => 'success',
            'icon' => 'heroicon-o-check-circle',
        ],
        'RespintoAccettatoParzialmente' => [
            'label' => 'Rejected / partially accepted',
            'color' => 'danger',
            'icon' => 'heroicon-o-exclamation-circle',
        ],
        'FirmaDestinatarioSuccessivo' => [
            'label' => 'Subsequent recipient signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaAccettazioneSuccessiva' => [
            'label' => 'Subsequent acceptance signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'FirmaAnnullamento' => [
            'label' => 'Cancellation signature',
            'color' => 'warning',
            'icon' => 'heroicon-o-pencil-square',
        ],
        'Annullato' => [
            'label' => 'Cancelled',
            'color' => 'gray',
            'icon' => 'heroicon-o-no-symbol',
        ],
    ],
];
