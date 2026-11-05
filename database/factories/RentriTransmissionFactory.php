<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;

/**
 * @extends Factory<RentriTransmission>
 */
class RentriTransmissionFactory extends Factory
{
    /**
     * @var class-string<RentriTransmission>
     */
    protected $model = RentriTransmission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rentri_credential_id' => RentriCredential::factory(),
            'service' => RentriServiceEnum::FORMULARI->value,
            'method' => 'POST',
            'path' => '/',
            'request_payload' => ['body' => [], 'query' => [], 'meta' => []],
            'response_status' => 200,
            'response_body' => [],
            'transaction_id' => null,
            'status' => TransmissionStatusEnum::SUCCEEDED,
            'attempts' => 1,
            'error' => null,
            'completed_at' => now(),
        ];
    }

    public function submitted(?string $transactionId = null): static
    {
        return $this->state(fn (array $_attributes): array => [
            'response_status' => 202,
            'transaction_id' => $transactionId ?? $this->faker->uuid(),
            'status' => TransmissionStatusEnum::SUBMITTED,
            'completed_at' => null,
        ]);
    }

    public function failed(string $error = 'HTTP 400'): static
    {
        return $this->state(fn (array $_attributes): array => [
            'response_status' => 400,
            'status' => TransmissionStatusEnum::FAILED,
            'error' => $error,
        ]);
    }
}
