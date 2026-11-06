---
type: story
tags: [rentri, idempotenza, code, sicurezza, firma, autorizzazione]
slug: idempotenza-firma-autorizzazioni
title: "RENTRI: nessun doppio invio, segreti oscurati per parola, firma del FIR dal pannello"
status: review
phase: REVIEW
owner: Rentri
depends_on: []
related:
  - "../../../Roosko/docs/stories/03.integrita-concorrenza-ruoli.story.md"
created: 2026-11-06
updated: 2026-11-06
---

# RENTRI: nessun doppio invio, segreti oscurati per parola, firma del FIR dal pannello

## Story

Come **impianto che emette e accetta FIR digitali**
voglio che **un FIR non venga mai emesso o accettato due volte su RENTRI, che i segreti non finiscano nei log e che la firma con PIN si possa fare dal pannello**
così da **non dover annullare FIR doppi, non esporre PIN e OTP, e chiudere il ciclo del FIR senza strumenti esterni**.

## Perché / evidenze (audit 2026-11-05)

- devops A5: worker `--timeout=60` contro chiamate RENTRI fino a ~150 s con i retry; il job ucciso viene ritentato e `IssueDigitalFirAction` non controllava trasmissioni già in corso → FIR emesso due volte.
- database R16: "Riprova" senza lock (doppio click = doppio invio), nessuno stato "in trasmissione".
- sicurezza B7: oscuramento per nome esatto: `otpCode`, `credentialPassword` passavano in chiaro.
- funzionale: nessun bottone per `SignFirAction`.
- sicurezza A2/M4: policy senza negazione delle abilità non dichiarate (eliminazione massiva delle credenziali con il solo `viewAny`), permessi mai seminati.

## Acceptance criteria

1. Due esecuzioni di `IssueDigitalFirAction` per lo stesso FIR → una sola richiesta HTTP, stessa trasmissione. ✔
2. Dopo un'emissione `failed` si può emettere di nuovo. ✔
3. Un secondo "Riprova" sulla stessa trasmissione non invia. ✔
4. Job RENTRI: coda `rentri`, `tries = 1`, timeout > durata massima della chiamata con retry. ✔
5. Chiavi `otpCode`, `pinFirma`, `credentialPassword`, `X-Secret-Key`, `access_token`, `privateKey`, … oscurate; `respingimento`, `tipoAccettazione`, `credentialID` no. ✔
6. "Firma FIR" solo sulle trasmissioni di emissione/accettazione riuscite di un FIR digitale con numero; PIN in campo password non salvato; abilità `rentri_transmission.sign`. ✔
7. Policy Rentri con `DeniesUndeclaredFilamentAbilities`; `RentriPermissionSeeder` idempotente crea tutti i permessi delle policy. ✔

Test: `tests/Feature/FirFlowTest.php`, `tests/Unit/RentriPureLogicTest.php`.

## Dev agent record

- 2026-11-06 — Claude.
  - **Decisioni**:
    - lo stato `pending` già esistente fa da "in trasmissione": la trasmissione si registra e si committa prima della chiamata HTTP (`open` + `send` separati in `SendRentriRequestAction`), sotto lock della riga del FIR; nessuna nuova colonna;
    - `tries = 1` invece di retry automatici: un reinvio si fa con "Riprova", con la guardia;
    - timeout del job calcolato dalla config `http` (una sola fonte), non un numero fisso;
    - oscuramento per **parola** e non per sottostringa: `pin` come sottostringa oscurerebbe `respingimento` e renderebbe la trasmissione non ripetibile;
    - bottone di firma nel pannello Rentri (sulla trasmissione), perché la credenziale da usare è quella della trasmissione e Waste/Roosko non conoscono le credenziali.
  - **Verifica**: harness Pest Rentri: prima 34 passati + 2 falliti, dopo 57 passati, 0 falliti (corretti anche il fuso del test di accettazione e il body non JSON con Safe\json_decode). PHPStan `Modules/Rentri/app`: 0 errori. `check_refs.php`: 0.
  - **Rischi residui**: una trasmissione rimasta `pending` (processo terminato durante la chiamata) blocca i reinvii automatici e va verificata a mano; una `ConnectionException` dopo che RENTRI ha ricevuto la richiesta porta a `failed` e un "Riprova" può duplicare (servirebbe una chiave di idempotenza lato API, da verificare sullo Swagger); `TransmitRegisterEntriesAction` non usa ancora `exchangeOnce` (selezione delle scritture già in trasmissione, R16, da completare).
