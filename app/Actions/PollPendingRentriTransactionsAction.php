<?php

declare(strict_types=1);

namespace Modules\Rentri\Actions;

use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriTransmission;
use Spatie\QueueableAction\QueueableAction;

/**
 * Verifica l'esito delle trasmissioni asincrone in attesa (registrata nello scheduler ogni minuto dal ServiceProvider).
 * Un errore su una trasmissione viene riportato e non blocca le altre.
 *
 * @return int numero di trasmissioni arrivate a un esito definitivo in questa esecuzione
 */
class PollPendingRentriTransactionsAction
{
    use QueueableAction;

    public function execute(): int
    {
        $completed = 0;

        /** @var iterable<RentriTransmission> $transmissions */
        $transmissions = RentriTransmission::query()
            ->where('status', TransmissionStatusEnum::SUBMITTED->value)
            ->whereNotNull('transaction_id')
            ->orderBy('updated_at')
            ->limit(config()->integer('rentri.polling.batch_size'))
            ->get();

        foreach ($transmissions as $transmission) {
            try {
                if (app(PollRentriTransactionAction::class)->execute($transmission)->status->isFinal()) {
                    $completed++;
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $completed;
    }
}
