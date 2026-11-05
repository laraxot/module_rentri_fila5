<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriCredentialResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Modules\Rentri\Enums\RentriEnvironmentEnum;
use Modules\Waste\Models\OperatorSite;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceForm;

/**
 * Form della credenziale RENTRI.
 *
 * - il certificato .p12 va sul disco privato `rentri.certificates.disk` (visibilita' private);
 * - la password non viene mai mostrata: il campo parte vuoto (attributo $hidden sul modello) e viene salvato
 *   solo se compilato, quindi lasciarlo vuoto in modifica conserva quella esistente. Obbligatorio in creazione.
 */
class RentriCredentialForm extends XotBaseResourceForm
{
    /**
     * @return array<string, Component>
     */
    public function getFormSchema(): array
    {
        return [
            'operator_site_id' => Select::make('operator_site_id')
                ->options(static fn (): array => OperatorSite::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->map(static fn (mixed $name): string => \is_scalar($name) ? (string) $name : '')
                    ->all())
                ->searchable()
                ->required(),
            'name' => TextInput::make('name')
                ->required()
                ->maxLength(255),
            'environment' => Select::make('environment')
                ->options(RentriEnvironmentEnum::class)
                ->default(RentriEnvironmentEnum::DEMO->value)
                ->required(),
            'subject_identifier' => TextInput::make('subject_identifier')
                ->required()
                ->maxLength(32),
            'certificate_path' => FileUpload::make('certificate_path')
                ->disk(static fn (): string => config()->string('rentri.certificates.disk'))
                ->directory(static fn (): string => config()->string('rentri.certificates.directory'))
                ->visibility('private')
                ->acceptedFileTypes(['application/x-pkcs12', 'application/pkcs12', 'application/octet-stream'])
                ->maxSize(256)
                ->required(),
            'certificate_password' => TextInput::make('certificate_password')
                ->password()
                ->revealable(false)
                ->autocomplete('new-password')
                ->formatStateUsing(static fn (): ?string => null)
                ->dehydrated(static fn (?string $state): bool => $state !== null && $state !== '')
                ->required(static fn (string $operation): bool => $operation === Operation::Create->value),
            'remote_signature_credential_id' => TextInput::make('remote_signature_credential_id')
                ->maxLength(255),
            'expires_at' => DateTimePicker::make('expires_at'),
            'is_active' => Toggle::make('is_active')
                ->default(true),
        ];
    }
}
