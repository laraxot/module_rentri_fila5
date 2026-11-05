<?php

declare(strict_types=1);

/*
 * Credenziali RENTRI: Resource (navigation/label), form, infolist e tabella leggono da qui le chiavi fields.* e actions.*.
 */

return [
    'navigation' => [
        'label' => 'Credenziali RENTRI',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-key',
        'sort' => 10,
    ],
    'label' => 'Credenziale RENTRI',
    'plural_label' => 'Credenziali RENTRI',
    'plural' => [
        'label' => 'Credenziali RENTRI',
    ],
    'fields' => [
        'operator_site_id' => [
            'label' => 'Unità locale',
            'placeholder' => 'Seleziona il sito',
            'helper_text' => 'Sito (modulo Waste) a cui appartiene la credenziale',
        ],
        'name' => [
            'label' => 'Nome',
            'placeholder' => 'Es. Impianto di Padova - demo',
        ],
        'environment' => [
            'label' => 'Ambiente',
            'helper_text' => 'Demo per il collaudo, Produzione per le trasmissioni reali',
        ],
        'subject_identifier' => [
            'label' => 'Identificativo soggetto',
            'placeholder' => 'Codice fiscale o partita IVA',
            'helper_text' => 'Identificativo dell\'operatore iscritto al RENTRI (claim iss del JWT)',
        ],
        'certificate_path' => [
            'label' => 'Certificato di interoperabilità (.p12)',
            'helper_text' => 'File .p12 rilasciato da RENTRI per autenticare le chiamate API. Salvato su disco privato. NON è il certificato di firma dei FIR',
        ],
        'certificate_password' => [
            'label' => 'Password del certificato',
            'helper_text' => 'Lascia vuoto per mantenere la password già salvata. Viene conservata cifrata e non è mai mostrata',
        ],
        'remote_signature_credential_id' => [
            'label' => 'Identificativo credenziale di firma remota',
            'helper_text' => 'Credenziale del servizio di firma remota RENTRI usata per firmare i FIR (PIN richiesto a ogni firma)',
        ],
        'expires_at' => [
            'label' => 'Scadenza',
            'helper_text' => 'Data di scadenza del certificato: dopo questa data la credenziale non viene più usata',
        ],
        'is_active' => [
            'label' => 'Attiva',
        ],
        'operatorSite.name' => [
            'label' => 'Unità locale',
        ],
        'updated_at' => [
            'label' => 'Ultima modifica',
        ],
    ],
    'actions' => [
        'create' => [
            'label' => 'Nuova credenziale',
        ],
        'edit' => [
            'label' => 'Modifica',
        ],
        'delete' => [
            'label' => 'Elimina',
        ],
        'save' => [
            'label' => 'Salva',
        ],
        'createAnother' => [
            'label' => 'Salva e crea un\'altra',
        ],
        'cancel' => [
            'label' => 'Annulla',
        ],
    ],
];
