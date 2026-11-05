<?php

declare(strict_types=1);

namespace Modules\Rentri\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Rentri\Database\Factories\RentriCredentialFactory;
use Modules\Rentri\Enums\RentriEnvironmentEnum;
use Modules\Waste\Models\OperatorSite;

/**
 * Credenziale di interoperabilita' RENTRI di un'unita' locale (sito) del modulo Waste.
 *
 * Il certificato .p12 sta su disco privato (`rentri.certificates.disk`), la password e' cifrata
 * (cast `encrypted`) ed esclusa dalla serializzazione ($hidden).
 *
 * @property int $id
 * @property int $operator_site_id
 * @property string $name
 * @property RentriEnvironmentEnum $environment
 * @property string $subject_identifier
 * @property string $certificate_path
 * @property string $certificate_password
 * @property string|null $remote_signature_credential_id
 * @property CarbonImmutable|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property-read OperatorSite|null $operatorSite
 *
 * @method static RentriCredentialFactory factory($count = null, $state = [])
 */
class RentriCredential extends BaseModel
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'operator_site_id',
        'name',
        'environment',
        'subject_identifier',
        'certificate_path',
        'certificate_password',
        'remote_signature_credential_id',
        'expires_at',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'certificate_password',
    ];

    /**
     * @return BelongsTo<OperatorSite, $this>
     */
    public function operatorSite(): BelongsTo
    {
        return $this->belongsTo(OperatorSite::class, 'operator_site_id');
    }

    /**
     * @return HasMany<RentriTransmission, $this>
     */
    public function transmissions(): HasMany
    {
        return $this->hasMany(RentriTransmission::class, 'rentri_credential_id');
    }

    public function isUsable(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'operator_site_id' => 'integer',
            'environment' => RentriEnvironmentEnum::class,
            'certificate_password' => 'encrypted',
            'expires_at' => 'immutable_datetime',
            'is_active' => 'boolean',
        ]);
    }
}
