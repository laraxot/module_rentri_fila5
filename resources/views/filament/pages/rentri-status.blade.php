<?php

declare(strict_types=1);

use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\RentriServiceHealthEnum;

/** @var \Modules\Rentri\Filament\Pages\RentriStatusPage $this */
$options = $this->getCredentialOptions();
?>
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ __('rentri::rentri_status.credential.heading') }}</x-slot>

        @if ($options === [])
            <p>{{ __('rentri::rentri_status.credential.none') }}</p>
        @else
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="credentialId">
                    @foreach ($options as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">{{ __('rentri::rentri_status.services.heading') }}</x-slot>

        @if ($this->results === [])
            <p>{{ __('rentri::rentri_status.services.not_checked') }}</p>
        @else
            <table class="fi-ta-table w-full text-start">
                <thead>
                    <tr>
                        <th class="text-start">{{ __('rentri::rentri_status.columns.service') }}</th>
                        <th class="text-start">{{ __('rentri::rentri_status.columns.health') }}</th>
                        <th class="text-start">{{ __('rentri::rentri_status.columns.http_status') }}</th>
                        <th class="text-start">{{ __('rentri::rentri_status.columns.message') }}</th>
                        <th class="text-start">{{ __('rentri::rentri_status.columns.checked_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->results as $service => $result)
                        @php($health = RentriServiceHealthEnum::from($result['health']))
                        <tr>
                            <td>{{ RentriServiceEnum::from($service)->getLabel() }}</td>
                            <td>
                                <x-filament::badge :color="$health->getColor()" :icon="$health->getIcon()">
                                    {{ $health->getLabel() }}
                                </x-filament::badge>
                            </td>
                            <td>{{ $result['http_status'] ?? '—' }}</td>
                            <td>{{ $result['message'] ?? '' }}</td>
                            <td>{{ \Carbon\CarbonImmutable::parse($result['checked_at'])->format('d/m/Y H:i:s') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-panels::page>
