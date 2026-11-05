<?php

declare(strict_types=1);

namespace Modules\Rentri\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Rentri\Models\RentriCode;

/**
 * @extends Factory<RentriCode>
 */
class RentriCodeFactory extends Factory
{
    /**
     * @var class-string<RentriCode>
     */
    protected $model = RentriCode::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->bothify('??##'));

        return [
            'list' => 'test',
            'code' => $code,
            'description' => $this->faker->sentence(4),
            'payload' => ['codice' => $code],
        ];
    }
}
