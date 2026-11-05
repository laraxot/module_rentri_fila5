<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rentri\Enums\RentriEnvironmentEnum;
use Modules\Rentri\Models\RentriCredential;

/**
 * Il certificato non viene generato: `certificate_path` punta a un file che i test creano con openssl
 * (tests/Support/TestCertificate.php).
 *
 * @extends Factory<RentriCredential>
 */
class RentriCredentialFactory extends Factory
{
    /**
     * @var class-string<RentriCredential>
     */
    protected $model = RentriCredential::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operator_site_id' => $this->faker->numberBetween(1, 1000),
            'name' => 'RENTRI '.$this->faker->company(),
            'environment' => RentriEnvironmentEnum::DEMO,
            'subject_identifier' => $this->faker->numerify('###########'),
            'certificate_path' => 'rentri/certificates/'.$this->faker->uuid().'.p12',
            'certificate_password' => $this->faker->password(12, 20),
            'remote_signature_credential_id' => null,
            'expires_at' => now()->addYear(),
            'is_active' => true,
        ];
    }

    public function production(): static
    {
        return $this->state(fn (array $_attributes): array => ['environment' => RentriEnvironmentEnum::PRODUCTION]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $_attributes): array => ['is_active' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $_attributes): array => ['expires_at' => now()->subDay()]);
    }
}
