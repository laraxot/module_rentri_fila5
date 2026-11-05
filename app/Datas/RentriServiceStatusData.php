<?php

declare(strict_types=1);

namespace Modules\Rentri\Datas;

use Carbon\CarbonImmutable;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\RentriServiceHealthEnum;
use Spatie\LaravelData\Data;

/**
 * Esito del controllo di stato di un servizio RENTRI (CheckRentriServiceStatusAction).
 */
class RentriServiceStatusData extends Data
{
    public function __construct(
        public RentriServiceEnum $service,
        public RentriServiceHealthEnum $health,
        public ?int $httpStatus,
        public ?string $message,
        public CarbonImmutable $checkedAt,
    ) {}
}
