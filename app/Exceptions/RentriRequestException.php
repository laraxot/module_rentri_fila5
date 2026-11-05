<?php

declare(strict_types=1);

namespace Modules\Rentri\Exceptions;

use Modules\Rentri\Datas\RentriProblemData;
use Modules\Rentri\Models\RentriTransmission;

/**
 * Errore HTTP restituito da RENTRI (application/problem+json): codice HTTP, tipo, titolo e dettaglio.
 */
class RentriRequestException extends RentriException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?RentriProblemData $problem = null,
        public readonly ?int $transmissionId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public static function fromTransmission(RentriTransmission $transmission, ?RentriProblemData $problem = null): self
    {
        $status = $transmission->response_status ?? 0;
        $detail = $problem?->summary() ?? ($transmission->error ?? '');

        return new self(
            RentriException::translate('rentri::exceptions.request.failed', [
                'method' => $transmission->method,
                'path' => $transmission->path,
                'status' => $status,
                'detail' => $detail,
            ]),
            $status,
            $problem,
            $transmission->id,
        );
    }

    public function type(): ?string
    {
        return $this->problem?->type;
    }

    public function detail(): ?string
    {
        return $this->problem?->detail;
    }
}
