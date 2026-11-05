<?php

declare(strict_types=1);

/*
 * Credenziali RENTRI: Resource (navigation/label), form, infolist e tabella leggono da qui le chiavi fields.* e actions.*.
 */

return [
    'navigation' => [
        'label' => 'RENTRI credentials',
        'group' => 'RENTRI',
        'icon' => 'heroicon-o-key',
        'sort' => 10,
    ],
    'label' => 'RENTRI credential',
    'plural_label' => 'RENTRI credentials',
    'plural' => [
        'label' => 'RENTRI credentials',
    ],
    'fields' => [
        'operator_site_id' => [
            'label' => 'Local unit',
            'placeholder' => 'Select the site',
            'helper_text' => 'Site (Waste module) the credential belongs to',
        ],
        'name' => [
            'label' => 'Name',
            'placeholder' => 'E.g. Padova plant - demo',
        ],
        'environment' => [
            'label' => 'Environment',
            'helper_text' => 'Demo for testing, Production for live transmissions',
        ],
        'subject_identifier' => [
            'label' => 'Subject identifier',
            'placeholder' => 'Tax code or VAT number',
            'helper_text' => 'Identifier of the operator registered with RENTRI (JWT iss claim)',
        ],
        'certificate_path' => [
            'label' => 'Interoperability certificate (.p12)',
            'helper_text' => 'The .p12 file issued by RENTRI to authenticate API calls. Stored on a private disk. It is NOT the FIR signing certificate',
        ],
        'certificate_password' => [
            'label' => 'Certificate password',
            'helper_text' => 'Leave empty to keep the stored password. It is stored encrypted and never shown',
        ],
        'remote_signature_credential_id' => [
            'label' => 'Remote signature credential ID',
            'helper_text' => 'Credential of the RENTRI remote signature service used to sign FIRs (PIN required for each signature)',
        ],
        'expires_at' => [
            'label' => 'Expires at',
            'helper_text' => 'Certificate expiry date: after this date the credential is no longer used',
        ],
        'is_active' => [
            'label' => 'Active',
        ],
        'operatorSite.name' => [
            'label' => 'Local unit',
        ],
        'updated_at' => [
            'label' => 'Last update',
        ],
    ],
    'actions' => [
        'create' => [
            'label' => 'New credential',
        ],
        'edit' => [
            'label' => 'Edit',
        ],
        'delete' => [
            'label' => 'Delete',
        ],
        'save' => [
            'label' => 'Save',
        ],
        'createAnother' => [
            'label' => 'Save and create another',
        ],
        'cancel' => [
            'label' => 'Cancel',
        ],
    ],
];
