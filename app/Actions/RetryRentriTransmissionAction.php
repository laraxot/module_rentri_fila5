<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Datas\RentriRequestData;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\QueueableAction\QueueableAction;
use Webmozart\Assert\Assert;

/**
 * Reinvia una trasmissione fallita riusando la stessa riga di `rentri_transmissions`.
 *
 * Il payload salvato e' oscurato: se contiene valori oscurati (PIN, SAD...) non puo' essere reinviato e va
 * rifatta l'operazione di dominio (es. SignFirAction con un nuovo PIN).
 */
class RetryRentriTransmissionAction
{
    use QueueableAction;

    public function execute(RentriTransmission $transmission): RentriTransmission
    {
        Assert::true($transmission->status->canTransitionTo(TransmissionStatusEnum::PENDING));

        if (app(RedactRentriPayloadAction::class)->containsRedactedValue($transmission->request_payload ?? [])) {
            throw new RentriException(RentriException::translate('rentri::exceptions.transmission.not_retryable'));
        }

        $credential = $transmission->credential;
        Assert::isInstanceOf($credential, RentriCredential::class);

        return app(SendRentriRequestAction::class)->exchange(
            $credential,
            RentriRequestData::fromTransmission($transmission),
            null,
            $transmission,
        )->transmission;
    }

    public function canRetry(RentriTransmission $transmission): bool
    {
        return $transmission->status === TransmissionStatusEnum::FAILED
            && ! app(RedactRentriPayloadAction::class)->containsRedactedValue($transmission->request_payload ?? []);
    }
}
