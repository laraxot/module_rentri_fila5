<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Modules\Rentri\Models\RentriTransmission;
use Spatie\LaravelData\Data;

/**
 * Esito di una chiamata: la trasmissione registrata e la risposta completa (null se la richiesta non e' partita
 * o la connessione e' fallita). Serve alle Actions che leggono il body grezzo (xFIR) o il body non oscurato.
 */
class RentriExchangeData extends Data
{
    public function __construct(
        public RentriTransmission $transmission,
        public ?RentriResponseData $response = null,
    ) {}
}
