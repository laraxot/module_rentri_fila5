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
 *
 * La trasmissione si rilegge e riapre (`pending`) sotto lock in una transazione breve: due
 * "Riprova" contemporanei non producono due invii.
 */
class RetryRentriTransmissionAction
{
    use QueueableAction;

    public function execute(RentriTransmission $transmission): RentriTransmission
    {
        if (app(RedactRentriPayloadAction::class)->containsRedactedValue($transmission->request_payload ?? [])) {
            throw new RentriException(RentriException::translate('rentri::exceptions.transmission.not_retryable'));
        }

        $credential = $transmission->credential;
        Assert::isInstanceOf($credential, RentriCredential::class);
        $request = RentriRequestData::fromTransmission($transmission);
        $sender = app(SendRentriRequestAction::class);

        // Riapertura sotto lock e commit prima della chiamata: un doppio click trova la riga gia'
        // `pending` e si ferma sull'Assert, invece di inviare due volte.
        $claimed = $transmission->getConnection()->transaction(static function () use ($transmission, $credential, $request, $sender): RentriTransmission {
            $locked = RentriTransmission::query()->whereKey($transmission->getKey())->lockForUpdate()->firstOrFail();
            Assert::true($locked->status->canTransitionTo(TransmissionStatusEnum::PENDING));

            return $sender->open($credential, $request, null, $locked);
        });
        $transmission->setRawAttributes($claimed->getAttributes(), true);

        return $sender->send($credential, $request, $transmission)->transmission;
    }

    public function canRetry(RentriTransmission $transmission): bool
    {
        return $transmission->status === TransmissionStatusEnum::FAILED
            && ! app(RedactRentriPayloadAction::class)->containsRedactedValue($transmission->request_payload ?? []);
    }
}
