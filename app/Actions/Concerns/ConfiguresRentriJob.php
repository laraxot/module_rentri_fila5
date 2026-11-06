<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions\Concerns;

/**
 * Impostazioni del job per le queueable action che chiamano RENTRI (ActionJob di spatie le legge
 * dalle proprieta' `queue`, `tries`, `timeout` dell'action).
 *
 * - coda dedicata `rentri.queue.name` (default `rentri`): una chiamata lenta non ferma etichette e
 *   altri job;
 * - un solo tentativo: un reinvio automatico dopo un timeout potrebbe ripetere un'operazione gia'
 *   arrivata a RENTRI; si riprova dal pannello ("Riprova"), con le guardie di idempotenza;
 * - timeout del job = durata massima della chiamata con i retry HTTP (`rentri.http`) + margine:
 *   tentativi x (timeout + connect_timeout) + pause fra i tentativi + `rentri.queue.timeout_margin`.
 *   Il worker deve avere `--timeout` >= questo valore e `retry_after` della connessione di coda
 *   maggiore (vedi docs/README.md, "Code e worker").
 */
trait ConfiguresRentriJob
{
    public string $queue;

    public int $tries = 1;

    public int $timeout;

    public function __construct()
    {
        $this->queue = self::rentriQueue();
        $this->timeout = self::rentriJobTimeout();
    }

    public static function rentriQueue(): string
    {
        $queue = config('rentri.queue.name');

        return \is_string($queue) && $queue !== '' ? $queue : 'rentri';
    }

    /** Secondi: caso peggiore di una chiamata RENTRI con tutti i retry, piu' il margine. */
    public static function rentriJobTimeout(): int
    {
        $attempts = max(1, config()->integer('rentri.http.retry_times', 3));
        $perAttempt = config()->integer('rentri.http.timeout', 30) + config()->integer('rentri.http.connect_timeout', 10);
        $sleeps = (int) ceil(($attempts - 1) * config()->integer('rentri.http.retry_sleep_ms', 500) / 1000);

        return $attempts * $perAttempt + $sleeps + config()->integer('rentri.queue.timeout_margin', 30);
    }
}
