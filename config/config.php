<?php

declare(strict_types=1);

/*
 * Configurazione del modulo Rentri (chiave `rentri.*`).
 *
 * Convenzione: ogni valore marcato `// DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)`
 * NON e' confermato dalla documentazione ufficiale RENTRI consultata. Va controllato sullo Swagger dell'ambiente
 * demo prima di andare in produzione (elenco completo in docs/README.md, sezione "Da verificare prima della produzione").
 * I valori senza marcatura sono verificati sulla documentazione pubblica RENTRI.
 */
return [
    'name' => 'Rentri',
    'description' => 'Client delle API RENTRI (FIR digitale, firma, accettazione, registro cronologico)',
    'icon' => 'heroicon-o-arrows-right-left',
    'navigation' => [
        'enabled' => true,
        'sort' => 60,
    ],

    /*
     * Ambiente usato quando un sito ha piu' credenziali attive (ResolveCredentialAction).
     * Valori: RentriEnvironmentEnum (demo, production).
     */
    'default_environment' => env('RENTRI_ENVIRONMENT', 'demo'),

    /*
     * Ambienti (verificati).
     */
    'environments' => [
        'demo' => [
            'base_url' => 'https://demoapi.rentri.gov.it',
        ],
        'production' => [
            'base_url' => 'https://api.rentri.gov.it',
        ],
    ],

    /*
     * Base path dei servizi, relativo al base_url dell'ambiente.
     * Le chiavi sono i valori di RentriServiceEnum.
     */
    'services' => [
        'formulari' => 'formulari/v1.0',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'anagrafiche' => 'anagrafiche/v1.0',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — /codifiche/v1.0/lookup/{lista} compare anche in pyrentri (fonte non ufficiale)
        'codifiche' => 'codifiche/v1.0',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'dati_registri' => 'dati-registri/v1.0',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'vidimazione_formulari' => 'vidimazione-formulari/v1.0',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — servizio di firma remota (signHash, credentials/*)
        'sign' => 'sign/v1.0',
    ],

    /*
     * Path relativi al base path del servizio. `{segnaposto}` sostituiti dalle Actions.
     */
    'paths' => [
        // GET /{service}/v1.0/status: DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — usato da pyrentri (fonte non ufficiale)
        'service_status' => '/status',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'codes_lookup' => '/lookup/{list}',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'register_movements' => '/{identificativo_registro}/movimenti',
        // Firma remota, nomi allineati alla Cloud Signature Consortium API v1.
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — credentials/info non e' nella documentazione consultata
        'sign_credentials_authorize' => '/credentials/authorize',
        'sign_credentials_info' => '/credentials/info',
        'sign_signatures_sign_hash' => '/signatures/signHash',
    ],

    'http' => [
        'timeout' => (int) env('RENTRI_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('RENTRI_HTTP_CONNECT_TIMEOUT', 10),
        // Tentativi totali sugli errori transitori (connessione, 429, 5xx). A ogni tentativo i JWT sono rigenerati (jti nuovo).
        'retry_times' => (int) env('RENTRI_HTTP_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('RENTRI_HTTP_RETRY_SLEEP_MS', 500),
        'retry_on_status' => [429, 500, 502, 503, 504],
    ],

    /*
     * JWT ModI (ID_AUTH_REST_02 in Authorization, INTEGRITY_REST_02 in Agid-JWT-Signature).
     * Header x5c con il certificato di interoperabilita' (verificato). Claim e audience NON verificati.
     */
    'jwt' => [
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — durata del token in secondi
        'ttl' => (int) env('RENTRI_JWT_TTL', 300),
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — valori presi da pyrentri (fonte non ufficiale)
        'audience' => [
            'demo' => 'rentrigov.demo.api',
            'production' => 'rentrigov.api',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        // true: `signed_headers` come lista di oggetti (Linee guida ModI); false: un solo oggetto (come pyrentri).
        'signed_headers_as_list' => true,
    ],

    'certificates' => [
        // Disco PRIVATO su cui stanno i .p12 caricati dal pannello (mai un disco pubblico).
        'disk' => env('RENTRI_CERTIFICATES_DISK', 'local'),
        'directory' => 'rentri/certificates',
    ],

    'xfir' => [
        // Dove DownloadXfirAction salva gli xFIR scaricati (disco privato).
        'disk' => env('RENTRI_XFIR_DISK', 'local'),
        'directory' => 'rentri/xfir',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — Accept ed estensione per GET /{numero_fir}/xfir
        'accept' => 'application/xml, application/json, application/problem+json',
        'extension' => 'xml',
    ],

    'polling' => [
        // Registra nello scheduler il poll delle transazioni in attesa (ogni minuto).
        'enabled' => (bool) env('RENTRI_POLLING_ENABLED', true),
        // Oltre questi tentativi la trasmissione passa a failed.
        'max_attempts' => 120,
        // Trasmissioni esaminate per esecuzione.
        'batch_size' => 50,
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — campo dello stato della transazione
        // nella risposta di GET /{transazione_id}/status e valori che indicano "elaborazione terminata".
        // Una risposta 303 See Other (pattern ModI NONBLOCK_PULL_REST) e' trattata comunque come terminata.
        'status_key' => 'stato',
        'done_values' => ['Eseguita', 'Completata', 'Elaborata', 'Done', 'Completed'],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — chiavi che, se valorizzate nel result, indicano esito negativo
        'result_error_keys' => ['errori', 'errors'],
    ],

    // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — formato di date/ore nei payload (es. dataOraArrivo)
    'datetime_format' => 'Y-m-d\\TH:i:sP',
    'timezone' => 'Europe/Rome',

    'responses' => [
        // Campo con l'identificativo della transazione asincrona (verificato).
        'transaction_id_key' => 'transazione_id',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — numero del FIR nella risposta di creazione
        'fir_number_key' => 'numero_fir',
    ],

    /*
     * Chiavi oscurate (case-insensitive, a ogni livello) nei payload salvati su rentri_transmissions.
     */
    'redact_keys' => ['pin', 'otp', 'password', 'certificate_password', 'sad', 'private_key', 'secret'],
    'redacted_value' => '***',

    /*
     * Mapping dei nostri enum (modulo Waste) verso le codifiche RENTRI.
     */
    'codes' => [
        // tipoAccettazione: "A" verificato per accettazione totale.
        'acceptance' => [
            'full' => 'A',
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
            'partial' => 'P',
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
            'rejected' => 'R',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — codifica "stato fisico"
        'physical_state' => [
            'solid_powdery' => 'SP',
            'solid_non_powdery' => 'S',
            'sludge' => 'FP',
            'liquid' => 'L',
            'viscous' => 'VS',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — codifica "unita' di misura"
        'quantity_unit' => [
            'kg' => 'kg',
            'l' => 'l',
            'm3' => 'm3',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — causale del movimento di registro
        'register_cause' => [
            'new_production' => 'NP',
            'from_third_parties' => 'DT',
            'to_third_parties' => 'aT',
            'internal_treatment' => 'T',
            'rejected_return' => 'RE',
            'adjustment' => 'M',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — tipo movimento (carico/scarico)
        'register_entry_type' => [
            'load' => 'C',
            'unload' => 'S',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — EER inviato senza spazi ("191204")
        'ewc_strip_spaces' => true,
    ],

    /*
     * Struttura dei payload JSON. Ogni voce associa un nostro dato al percorso (dot notation) nel JSON RENTRI.
     * Una voce a null non viene inviata. Le voci senza marcatura sono verificate.
     */
    'payload' => [
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — intero schema di creazione FIR (POST /formulari/v1.0/).
        // Unica eccezione: `num_iscr_sito` (verificato come parametro di GET /, presente nel body anche in pyrentri).
        'fir' => [
            'site_number' => 'num_iscr_sito',
            'producer_fiscal_code' => 'dati_partenza.produttore.codice_fiscale',
            'producer_business_name' => 'dati_partenza.produttore.denominazione',
            'producer_site_number' => 'dati_partenza.produttore.num_iscr_sito',
            'transporter_fiscal_code' => 'dati_partenza.trasportatore.codice_fiscale',
            'transporter_business_name' => 'dati_partenza.trasportatore.denominazione',
            'transporter_albo_number' => 'dati_partenza.trasportatore.numero_iscrizione_albo',
            'recipient_fiscal_code' => 'dati_partenza.destinatario.codice_fiscale',
            'recipient_business_name' => 'dati_partenza.destinatario.denominazione',
            'recipient_site_number' => 'dati_partenza.destinatario.num_iscr_sito',
            'intermediary_fiscal_code' => 'dati_partenza.intermediario.codice_fiscale',
            'operation' => 'dati_partenza.destinatario.attivita',
            'ewc_code' => 'dati_partenza.rifiuto.codice_eer',
            'waste_description' => 'dati_partenza.rifiuto.descrizione',
            'physical_state' => 'dati_partenza.rifiuto.stato_fisico',
            'hazard_properties' => 'dati_partenza.rifiuto.caratteristiche_pericolo',
            'quantity' => 'dati_partenza.rifiuto.quantita.valore',
            'quantity_unit' => 'dati_partenza.rifiuto.quantita.unita_misura',
            'is_quantity_estimated' => 'dati_partenza.rifiuto.peso_da_verificare_a_destino',
            'adr_un_number' => 'dati_partenza.rifiuto.numero_onu',
            'vehicle_plate' => 'dati_partenza.trasporto.targa_automezzo',
            'trailer_plate' => 'dati_partenza.trasporto.targa_rimorchio',
            'driver_name' => 'dati_partenza.trasporto.conducente',
            'transport_started_at' => 'dati_partenza.trasporto.data_ora_inizio',
            'notes' => 'dati_partenza.annotazioni',
        ],
        // POST /{numero_fir}/accettazione: tipoAccettazione, quantitaAccettata.valore, dataOraArrivo verificati (fissi nel codice).
        'acceptance' => [
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
            'quantity_unit' => 'quantitaAccettata.unitaMisura',
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
            'rejection_reason' => 'motivazioneRespingimento',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — schema dei movimenti del registro cronologico
        'register' => [
            'entries_key' => 'movimenti',
        ],
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'register_entry' => [
            'year' => 'anno',
            'number' => 'progressivo',
            'type' => 'tipo_operazione',
            'cause' => 'causale_operazione',
            'recorded_at' => 'data_ora_registrazione',
            'operation_at' => 'data_ora_operazione',
            'ewc_code' => 'rifiuto.codice_eer',
            'physical_state' => 'rifiuto.stato_fisico',
            'hazard_properties' => 'rifiuto.caratteristiche_pericolo',
            'quantity' => 'rifiuto.quantita.valore',
            'quantity_unit' => 'rifiuto.quantita.unita_misura',
            'operation' => 'rifiuto.attivita',
            'fir_number' => 'riferimenti.numero_fir',
            'counterpart_fiscal_code' => 'riferimenti.codice_fiscale_controparte',
            'load_references' => 'riferimenti.riferimenti_carico',
            'rectifies' => 'riferimenti.rettifica_di',
            'notes' => 'annotazioni',
        ],
        // POST /{numero_fir}/acquisizione-firma: certificato, token, identificativo_utente verificati.
        'signature' => [
            'certificate' => 'certificato',
            'token' => 'token',
            'user_identifier' => 'identificativo_utente',
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
            'signature' => 'firma',
            // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — body di POST /{numero_fir}/rollback-firma
            'rollback_token' => 'token',
        ],
        // POST /{numero_fir}/note-annullamento (verificato).
        'cancellation_notes' => 'note',
    ],

    /*
     * Firma remota RENTRI (servizio Sign). Nomi dei campi secondo la Cloud Signature Consortium API v1.
     * DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs) — che il servizio segua la CSC API e con quali campi.
     */
    'sign' => [
        'sign_algo' => '1.2.840.113549.1.1.11',
        'hash_algo' => '2.16.840.1.101.3.4.2.1',
        'request_keys' => [
            'credential_id' => 'credentialID',
            'num_signatures' => 'numSignatures',
            'hashes' => 'hash',
            'pin' => 'PIN',
            'sad' => 'SAD',
            'sign_algo' => 'signAlgo',
            'hash_algo' => 'hashAlgo',
            'certificates' => 'certificates',
        ],
        'response_keys' => [
            'sad' => 'SAD',
            'signatures' => 'signatures',
            'certificate' => 'cert.certificates.0',
        ],
    ],

    /*
     * Nomi dei campi delle codifiche restituite da GET lookup (SyncRentriCodesAction).
     */
    'code_lists' => [
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'code_key' => 'codice',
        // DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)
        'description_key' => 'descrizione',
    ],
];
