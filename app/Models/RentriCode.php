<?php

declare(strict_types=1);

namespace Modules\Rentri\Models;

use Illuminate\Support\Carbon;
use Modules\Rentri\Database\Factories\RentriCodeFactory;

/**
 * Cache locale di una voce delle codifiche RENTRI (SyncRentriCodesAction).
 *
 * @property int $id
 * @property string $list
 * @property string $code
 * @property string $description
 * @property array<array-key, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $created_by
 * @property string|null $updated_by
 *
 * @method static RentriCodeFactory factory($count = null, $state = [])
 */
class RentriCode extends BaseModel
{
    /** @var list<string> */
    protected $fillable = [
        'list',
        'code',
        'description',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'payload' => 'array',
        ]);
    }
}
