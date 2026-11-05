<?php

declare(strict_types=1);

namespace Modules\Rentri\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Modules\Rentri\Database\Factories\RentriTransmissionFactory;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Exceptions\RentriException;

/**
 * Registro delle chiamate verso RENTRI: richiesta (oscurata), risposta, transazione asincrona ed esito.
 *
 * E' un log: si aggiorna lo stato, non si cancella.
 *
 * @property int $id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int $rentri_credential_id
 * @property string $service
 * @property string $method
 * @property string $path
 * @property array<string, mixed>|null $request_payload
 * @property int|null $response_status
 * @property array<array-key, mixed>|null $response_body
 * @property string|null $transaction_id
 * @property TransmissionStatusEnum $status
 * @property int $attempts
 * @property string|null $error
 * @property CarbonImmutable|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property-read RentriCredential|null $credential
 * @property-read Model|null $subject
 *
 * @method static RentriTransmissionFactory factory($count = null, $state = [])
 */
class RentriTransmission extends BaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'subject_type',
        'subject_id',
        'rentri_credential_id',
        'service',
        'method',
        'path',
        'request_payload',
        'response_status',
        'response_body',
        'transaction_id',
        'status',
        'attempts',
        'error',
        'completed_at',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<RentriCredential, $this>
     */
    public function credential(): BelongsTo
    {
        $relation = $this->belongsTo(RentriCredential::class, 'rentri_credential_id');
        // La credenziale puo' essere stata cancellata (soft delete) dopo la trasmissione: il log resta leggibile.
        $relation->getQuery()->withoutGlobalScope(SoftDeletingScope::class);

        return $relation;
    }

    public function serviceEnum(): RentriServiceEnum
    {
        return RentriServiceEnum::from($this->service);
    }

    protected static function booted(): void
    {
        static::deleting(static function (): never {
            throw new RentriException(RentriException::translate('rentri::exceptions.transmission.immutable'));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'subject_id' => 'integer',
            'rentri_credential_id' => 'integer',
            'request_payload' => 'array',
            'response_status' => 'integer',
            'response_body' => 'array',
            'status' => TransmissionStatusEnum::class,
            'attempts' => 'integer',
            'completed_at' => 'immutable_datetime',
        ]);
    }
}
