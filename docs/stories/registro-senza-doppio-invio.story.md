---
type: story
tags: [rentri, idempotenza, registro, code]
slug: registro-senza-doppio-invio
title: "RENTRI: le scritture del registro non partono due volte"
status: done
phase: DONE
owner: Rentri
depends_on: []
related:
  - "idempotenza-firma-autorizzazioni.story.md"
  - "../../../Roosko/docs/audit/final-report.md"
created: 2026-11-11
updated: 2026-11-11
---

# RENTRI: le scritture del registro non partono due volte

## Story

Come **impianto che trasmette il registro di carico e scarico a RENTRI**
voglio che **una scrittura gia' inviata e in attesa di esito non venga inviata di nuovo**
così da **non avere movimenti doppi sul registro elettronico nazionale**.

## Perché

Rapporto finale, rischio R2: `TransmitRegisterEntriesAction` sceglieva le scritture con
`transmitted_at` nullo. Il campo si valorizza solo dopo l'esito (polling), quindi un job ripetuto, un
secondo worker o un doppio click nel tempo di attesa rimandavano le stesse scritture.
L'emissione e l'accettazione del FIR erano già protette (`exchangeOnce`), il registro no.

## Fatto

- La scelta delle scritture e la registrazione della trasmissione `pending` avvengono sotto lock della
  riga del registro, in transazione su entrambe le connessioni. La trasmissione è committata prima
  della chiamata HTTP.
- Si escludono le scritture presenti in `meta.entry_ids` di una trasmissione del registro `pending` o
  `submitted`. Se non resta nulla, l'errore dice che sono già in trasmissione
  (`rentri::exceptions.register.already_in_transmission`).
- Le scritture di una trasmissione `failed` tornano inviabili.

## Verifica

- `FirFlowTest` → «le scritture in attesa di esito non si reinviano»: un secondo invio con esito
  pendente è rifiutato. Una scrittura nuova parte da sola. Dopo un `failed`, la scrittura torna inviabile.
- Suite Rentri 58/58, PHPStan 0.
