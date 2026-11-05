# Modulo Rentri

Client delle API **RENTRI** (Registro Elettronico Nazionale per la Tracciabilità dei Rifiuti) per Roosko:
FIR digitale (xFIR), firma, accettazione, trasmissione dei dati dei FIR di rifiuti pericolosi, trasmissione del
registro cronologico di carico/scarico.

Dipende da `Xot`, `User` (policy) e `Waste`. Legge i modelli Waste (`Fir`, `Register`, `RegisterEntry`,
`OperatorSite`, `Operator`, `EwcCode`) e i loro enum, non li estende. Unica nuova dipendenza composer:
`firebase/php-jwt` (più `ext-openssl`).

> **Prima della produzione** leggere la sezione [Da verificare prima della produzione](#da-verificare-prima-della-produzione):
> diversi dettagli dell'API non sono confermati dalla documentazione pubblica consultata e sono configurabili
> in `config/config.php` (marcati `// DA VERIFICARE`).

## Modelli

| Modello | Tabella | Note |
|---|---|---|
| `RentriCredential` | `rentri_credentials` | Credenziale di interoperabilità di un'unità locale (`operator_site_id`, Waste). `.p12` su disco privato, password con cast `encrypted` e in `$hidden`. Soft delete. |
| `RentriTransmission` | `rentri_transmissions` | Log di ogni chiamata: servizio, metodo, path, richiesta **oscurata**, risposta, `transaction_id`, stato (`TransmissionStatusEnum`), tentativi, errore. Morph `subject` (Fir, Register…). Non cancellabile. |
| `RentriCode` | `rentri_codes` | Cache delle codifiche RENTRI (`list`, `code`, `description`, `payload`), unique `(list, code)`. |

Connessione `rentri`. Le FK verso Waste sono colonne indicizzate senza vincolo.

## Enum

- `RentriEnvironmentEnum`: `demo`, `production` (base URL e audience dal config).
- `TransmissionStatusEnum`: `pending` → `submitted` → `succeeded`/`failed`; `failed` → `pending` per "riprova" (`canTransitionTo`).
- `RentriServiceEnum`: `formulari`, `anagrafiche`, `codifiche`, `dati_registri`, `vidimazione_formulari`, `sign` (base path dal config).
- `RentriFirStateEnum`: i 20 stati `StatoFormulario` (verificati), con `awaitsSignature()` e `isFinal()`.
- `RentriServiceHealthEnum`: `ok`, `warning`, `error` (pagina di stato).

## Actions

Pubbliche (contratto della SPEC, usate da Roosko):

```php
IssueDigitalFirAction::execute(Fir $fir, RentriCredential $credential): RentriTransmission
SignFirAction::execute(Fir $fir, RentriCredential $credential, string $pin): RentriTransmission
SubmitFirAcceptanceAction::execute(Fir $fir, RentriCredential $credential): RentriTransmission
TransmitRegisterEntriesAction::execute(Register $register, RentriCredential $credential, ?Collection $entries = null): RentriTransmission
PollRentriTransactionAction::execute(RentriTransmission $transmission): RentriTransmission
ResolveCredentialAction::execute(OperatorSite $site): RentriCredential
```

Altre azioni di dominio:

```php
TransmitHazardousFirDataAction::execute(Fir $fir, RentriCredential $credential): RentriTransmission
CancelDigitalFirAction::execute(Fir $fir, RentriCredential $credential, ?string $notes = null): RentriTransmission
DownloadXfirAction::execute(Fir $fir, RentriCredential $credential): string            // path sul disco privato
CheckRentriServiceStatusAction::execute(RentriCredential $credential, RentriServiceEnum $service): RentriServiceStatusData
SyncRentriCodesAction::execute(RentriCredential $credential, string $list): int
PollPendingRentriTransactionsAction::execute(): int                                       // scheduler, ogni minuto
RetryRentriTransmissionAction::execute(RentriTransmission $transmission): RentriTransmission
```

Interne:

```php
SendRentriRequestAction::execute(RentriCredential $c, string $service, string $method, string $path, array $payload = [], array $query = [], ?Model $subject = null): RentriTransmission
SendRentriRequestAction::exchange(RentriCredential $credential, RentriRequestData $request, ?Model $subject = null, ?RentriTransmission $transmission = null): RentriExchangeData
PerformRentriRequestAction::execute(RentriCredential $credential, RentriRequestData $request, ?RentriKeyPairData $keyPair = null): RentriResponseData
BuildAuthorizationJwtAction::execute(RentriCredential $credential, RentriKeyPairData $keyPair, array $extraClaims = []): string
BuildIntegrityJwtAction::execute(RentriCredential $credential, RentriKeyPairData $keyPair, string $digest, string $contentType): string
BuildDigestHeaderAction::execute(string $body): string
LoadCredentialKeyPairAction::execute(RentriCredential $credential): RentriKeyPairData
MapFirToRentriPayloadAction::execute(Fir $fir, OperatorSite $issuerSite): RentriFirPayloadData
MapFirAcceptanceToRentriPayloadAction::execute(Fir $fir): RentriFirAcceptancePayloadData
MapRegisterEntryToRentriPayloadAction::execute(RegisterEntry $entry): RentriRegisterEntryPayloadData
SignDigestRemotelyAction::execute(RentriCredential $credential, string $digestBase64, string $pin): RemoteSignatureData
ApplyRentriTransmissionOutcomeAction::execute(RentriTransmission $transmission, RentriResponseData $response): void
ResolveRentriCodeAction::execute(string $list, BackedEnum|string $value): string
RedactRentriPayloadAction::execute(array $payload): array
```

Tutte usano `QueueableAction`: per l'asincrono `app(IssueDigitalFirAction::class)->onQueue()->execute(...)`.
Nessun Service, nessun Job.

## Flussi

### Autenticazione di ogni chiamata (pattern AgID ModI)

1. `LoadCredentialKeyPairAction` legge il `.p12` dal disco privato con `openssl_pkcs12_read` e la password
   decifrata; produce chiave privata (mai serializzata: proprietà `#[Hidden]`), certificato e catena `x5c`
   (DER base64). Algoritmo: RS256 per chiavi RSA, ES256 per chiavi EC.
2. `BuildAuthorizationJwtAction` → `Authorization: Bearer <JWT>` (ID_AUTH_REST_02): header `alg`, `typ`, `x5c`;
   claim `iss` (= `subject_identifier`), `aud` (per ambiente), `jti` (UUID nuovo a ogni chiamata), `iat`, `nbf`, `exp`.
3. Se la richiesta ha un body: il JSON è serializzato **una volta**, `Digest: SHA-256=<base64(sha256(body))>`
   (RFC 3230) e `Agid-JWT-Signature: <JWT>` (INTEGRITY_REST_02) con gli stessi claim più
   `signed_headers` = `[{"digest": …}, {"content-type": "application/json"}]`.
4. Retry sugli errori transitori (connessione, 429, 5xx) con token rigenerati; i redirect non vengono seguiti.

### Pattern asincrono

Molte POST restituiscono `transazione_id`. La trasmissione resta `submitted`; ogni minuto lo scheduler
(`rentri:poll-pending-transactions`, registrato in `RentriServiceProvider` con `callAfterResolving(Schedule::class, …)`)
chiama `PollPendingRentriTransactionsAction`, che per ognuna esegue `PollRentriTransactionAction`:
`GET /{transazione_id}/status`, poi a elaborazione terminata `GET /{transazione_id}/result` →
`succeeded`/`failed`. A esito positivo `ApplyRentriTransmissionOutcomeAction` aggiorna i modelli Waste:

- creazione FIR: numero restituito su `firs.number`, `is_digital = true`, stato `issued` (via `ChangeFirStatusAction`);
- registro: `transmitted_at` e `rentri_transmission_ref` sulle scritture inviate (id in `request_payload.meta.entry_ids`).

### FIR digitale in uscita

`IssueDigitalFirAction` (POST `/formulari/v1.0/`) → `SignFirAction` (firma del produttore/trasportatore) →
`DownloadXfirAction` a ciclo concluso. `CancelDigitalFirAction` per annullare (poi firma dell'annullamento).

### FIR in ingresso (accettazione)

Waste `RecordFirAcceptanceAction` registra accettazione, kg accettati e arrivo → Roosko accoda
`SubmitFirAcceptanceAction` (POST `/{numero_fir}/accettazione` con `tipoAccettazione`,
`quantitaAccettata.valore` in kg, `dataOraArrivo`) → `SignFirAction` nello stato `FirmaAccettazione`.

### Firma del FIR

1. `POST /{numero_fir}/hash` → `token`, `digest_to_sign`, `hash_algorithm`;
2. firma del digest con la **firma remota RENTRI** (`SignDigestRemotelyAction`: credentials/authorize con PIN,
   signatures/signHash). Il certificato `.p12` di interoperabilità **non** firma i FIR;
3. `POST /{numero_fir}/acquisizione-firma` (`certificato`, `token`, firma, `identificativo_utente`) → `transazione_id`;
4. in caso di errore ai passi 2-3: `POST /{numero_fir}/rollback-firma`.

PIN e SAD sono oscurati nel log: una firma fallita non si "riprova", si rifà con un nuovo PIN.

### Dati dei pericolosi e registro

- `TransmitHazardousFirDataAction`: FIR digitale → `POST /trasmissioni/operatore/{num_iscr_sito}/{numero_fir}/estrai`;
  FIR cartaceo → `POST /trasmissioni/operatore/{num_iscr_sito}` con i dati mappati.
- `TransmitRegisterEntriesAction`: di default tutte le scritture del registro con `transmitted_at` nullo, ordinate per
  anno e numero, in un'unica trasmissione verso il servizio `dati_registri`.

## Configurazione dei certificati

1. Ottenere da RENTRI (area operatore) il **certificato di interoperabilità** `.p12` e la sua password, per l'ambiente
   demo e, separatamente, per la produzione.
2. Nel pannello **RENTRI → Credenziali RENTRI** creare una credenziale per l'unità locale (`operator_site_id`):
   ambiente, identificativo del soggetto (codice fiscale/partita IVA, va nel claim `iss`), upload del `.p12`,
   password, eventuale identificativo della credenziale di firma remota, scadenza.
3. Il file finisce su `config('rentri.certificates.disk')` (default `local`, privato) nella cartella
   `rentri/certificates`. **Non usare un disco pubblico.**
4. In modifica, lasciare vuoto il campo password conserva quella salvata; la password non viene mai mostrata
   né serializzata.
5. Verificare con la pagina **Stato servizi RENTRI** che la credenziale autentichi correttamente.

Segreti: le chiavi in `rentri.redact_keys` (pin, otp, password, sad, private_key…) sono sostituite con `***` in
`request_payload` e `response_body`; l'xFIR (body non JSON) non viene salvato nel log, solo tipo e dimensione.

## Punti di estensione (config `rentri.*`)

| Chiave | Uso |
|---|---|
| `environments.*.base_url` | URL degli ambienti |
| `services.*` | base path dei servizi |
| `paths.*` | path di status, codifiche, movimenti registro, firma remota |
| `http.*` | timeout, connect timeout, tentativi, attesa, codici da ritentare |
| `jwt.ttl`, `jwt.audience.*`, `jwt.signed_headers_as_list` | claim JWT |
| `certificates.*`, `xfir.*` | dischi privati e cartelle |
| `polling.*` | abilitazione scheduler, tentativi massimi, campo e valori di stato, chiavi di errore |
| `responses.*` | `transazione_id`, numero FIR |
| `codes.*` | mapping dei nostri enum verso le codifiche RENTRI |
| `payload.*` | percorsi (dot notation) dei campi nel JSON RENTRI |
| `sign.*` | campi della firma remota |
| `redact_keys` | chiavi oscurate nei log |

## Filament

- `RentriCredentialResource`: lista, crea, modifica (upload `.p12` su disco privato, password non sovrascritta se vuota).
- `RentriTransmissionResource`: sola lettura (lista + dettaglio) con le azioni **Verifica esito**
  (`PollRentriTransmissionTableAction`) e **Riprova** (`RetryRentriTransmissionTableAction`, nascosta se il payload
  contiene segreti oscurati). Permesso `rentri_transmission.retry`.
- `RentriStatusPage`: stato dei servizi per la credenziale scelta.

Etichette solo da `lang/{it,en}/*.php`.

## Verificato

Dalla documentazione pubblica RENTRI (riportata nella SPEC del progetto):

- ambienti `https://demoapi.rentri.gov.it` e `https://api.rentri.gov.it`;
- servizio formulari `/formulari/v1.0` e i suoi endpoint: `POST /`, `PUT /{numero_fir}`, `POST /{numero_fir}/quantita`,
  `POST /{numero_fir}/trasporto`, `GET /` (query `num_iscr_sito`, header `Paging-Page`, `Paging-PageSize` max 100),
  `POST /{numero_fir}/hash` (`token`, `digest_to_sign`, `hash_algorithm`), `POST /{numero_fir}/acquisizione-firma`
  (`certificato`, `token`, firma, `identificativo_utente`), `POST /{numero_fir}/rollback-firma`, `GET /{numero_fir}/azioni`,
  `GET /{numero_fir}`, `GET|POST /{numero_fir}/xfir`, `POST /xfir/valida`, `POST /{numero_fir}/annulla-fir`,
  `POST /{numero_fir}/note-annullamento` (`note`), `POST /{numero_fir}/accettazione` (`tipoAccettazione` es. `"A"`,
  `quantitaAccettata.valore`, `dataOraArrivo`), `POST /trasmissioni/operatore/{num_iscr_sito}/{numero_fir}/estrai`,
  `POST /trasmissioni/operatore/{num_iscr_sito}`, `GET /{transazione_id}/status`, `GET /{transazione_id}/result`;
- i 20 stati `StatoFormulario`;
- firma dei FIR con certificato qualificato o firma remota RENTRI (`/signatures/signHash`, `/credentials/list`,
  `/credentials/authorize` con PIN); il `.p12` di interoperabilità serve solo ad autenticare le chiamate;
- autenticazione ModI: `Authorization: Bearer` (JWT firmato con header `x5c`) + `Agid-JWT-Signature` + `Digest: SHA-256=…`.

Dalle Linee guida AgID sul Modello di Interoperabilità (pattern di integrità REST): header `Agid-JWT-Signature` e
`Digest` (RFC 3230), claim `signed_headers` come lista di oggetti `{nome-header-minuscolo: valore}`, `aud`/`iat`/`exp`
obbligatori, `x5c`/`x5u`/`x5t#S256` per referenziare il certificato.

## Da verificare prima della produzione

Tutti i punti seguenti sono in `config/config.php` con `// DA VERIFICARE sullo Swagger demo (https://demoapi.rentri.gov.it/docs)`.

1. **Claim JWT**: `iss` = identificativo del soggetto, `aud` = `rentrigov.demo.api` / `rentrigov.api`,
   `exp` = `iat` + 300 s. I valori di `aud` e la scelta di `iss` vengono dalla libreria non ufficiale *pyrentri*
   (vedi fonti), non dalla documentazione RENTRI.
2. **Forma di `signed_headers`**: lista di oggetti (Linee guida ModI, default) oppure oggetto unico
   (come pyrentri) → `jwt.signed_headers_as_list`.
3. **Base path dei servizi** diversi da formulari: `anagrafiche/v1.0`, `codifiche/v1.0`, `dati-registri/v1.0`,
   `vidimazione-formulari/v1.0`, `sign/v1.0`.
4. **Endpoint** `GET /{servizio}/v1.0/status` (usato da pyrentri), `GET /codifiche/v1.0/lookup/{lista}` (esempio in
   pyrentri), `POST /dati-registri/v1.0/{identificativo_registro}/movimenti`, `credentials/info` della firma remota.
5. **Schema del body di creazione FIR** (`payload.fir`) e dei movimenti di registro (`payload.register_entry`,
   `payload.register.entries_key`): i percorsi sono plausibili ma non verificati, l'unico campo verificato è `num_iscr_sito`.
6. **Accettazione**: codici `P` (parziale) e `R` (respinto), unità di misura e motivazione del respingimento.
   Verificati solo `tipoAccettazione` = `A`, `quantitaAccettata.valore`, `dataOraArrivo`.
7. **Codifiche** di stato fisico, unità di misura, causale e tipo movimento del registro (`codes.*`) e formato EER
   (senza spazi).
8. **Formato data/ora** (`Y-m-d\TH:i:sP`, Europe/Rome).
9. **Risposta di status** delle transazioni (`polling.status_key`, `polling.done_values`) e chiavi d'errore del result.
10. **Campo del numero FIR** nella risposta di creazione (`responses.fir_number_key`).
11. **Firma remota**: nomi dei campi (allineati alla Cloud Signature Consortium API v1), algoritmi OID, campo della
    firma in `acquisizione-firma` (`payload.signature.signature` = `firma`), body di `rollback-firma`.
12. **xFIR**: content type della risposta e header `Accept`.
13. **Ordine** `annulla-fir` → `note-annullamento`.

Procedura suggerita: con la credenziale demo, aprire lo Swagger di ogni servizio, confrontare schema e codifiche,
correggere il config (nessuna modifica al codice dovrebbe servire), sincronizzare le codifiche con
`SyncRentriCodesAction` e confrontarle con `codes.*`.

## Fonti consultate

- Documentazione RENTRI per sviluppatori (Swagger demo): https://demoapi.rentri.gov.it/docs — applicazione JavaScript,
  non leggibile dagli strumenti usati per generare il modulo: i fatti verificati vengono dalla SPEC del progetto.
- Linee guida AgID sul Modello di Interoperabilità, pattern di integrità REST:
  https://italia.github.io/lg-modellointeroperabilita-docs/doc/02_Pattern%20sicurezza/06_integrita/02_integrity_rest.html
- *pyrentri* (wrapper Python non ufficiale, MIT): https://github.com/progressify/pyrentri — fonte di `aud`, `iss`,
  `signed_headers` come oggetto, `/status` e `/codifiche/v1.0/lookup/…`. Va usata solo come indizio.
- Certifico, "RENTRI: API per vidimare/aprire formulari e registri":
  https://www.certifico.com/ambiente/news-ambiente/rentri-api-per-vidimare-aprire-formulari-e-registri — contesto
  (API disponibili da gennaio/febbraio 2025), nessun dettaglio tecnico.

## Test

`tests/` (Pest, `uses(TestCase::class)`):

- `Unit/RentriPureLogicTest.php`: digest, oscuramento, codifiche, transizioni di stato, stati FIR, problem+json;
- `Feature/SendRentriRequestActionTest.php`: `.p12` generato al volo con openssl, JWT RS256 con `x5c` verificato con
  la chiave pubblica, `Digest` corretto sul body inviato, `Agid-JWT-Signature` con `signed_headers`, log su
  `rentri_transmissions`, segreti oscurati, errori problem+json, retry con `jti` nuovo;
- `Feature/PollRentriTransactionActionTest.php`: polling che passa a `succeeded`/`failed`, 303, timeout, batch, riprova;
- `Feature/FirFlowTest.php`: mapping accettazione (`tipoAccettazione`, `quantitaAccettata.valore`, `dataOraArrivo`),
  invio accettazione, annullamento con note, xFIR; con il modulo Waste migrato anche mapping FIR, emissione,
  pericolosi, risoluzione credenziale e trasmissione del registro.

Tutte le chiamate HTTP passano da `Http::fake()` con `Http::preventStrayRequests()`.
