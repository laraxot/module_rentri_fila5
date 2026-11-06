<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Actions;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Modules\Rentri\Actions\GetSignableFirAction;
use Modules\Rentri\Actions\SignFirAction;
use Modules\Rentri\Exceptions\RentriException;
use Modules\Rentri\Models\RentriCredential;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Waste\Models\Fir;
use Modules\Xot\Actions\Cast\SafeStringCastAction;
use Modules\Xot\Filament\Actions\XotBaseAction;
use Webmozart\Assert\Assert;

/**
 * "Firma FIR": firma remota del FIR digitale (SignFirAction) dalla trasmissione di emissione o di
 * accettazione riuscita, con la credenziale di quella trasmissione.
 *
 * Il PIN si digita in un campo password: va solo a SignFirAction, non si salva (il payload della
 * trasmissione e' oscurato) e non resta nello stato del componente dopo la chiusura della modale.
 * Abilita' `sign` di RentriTransmissionPolicy (`rentri_transmission.sign`). Testi: sign_fir_table.php.
 */
class SignFirTableAction extends XotBaseAction
{
    public static function getDefaultName(): ?string
    {
        return 'sign';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-pencil-square')
            ->color('primary')
            ->authorize('sign')
            ->modalHeading(static fn (RentriTransmission $record): string => self::text('modal_heading', ['fir' => (string) app(GetSignableFirAction::class)->execute($record)?->number]))
            ->modalDescription(self::text('modal_description'))
            ->schema([
                'pin' => TextInput::make('pin')
                    ->password()
                    ->revealable(false)
                    ->autocomplete('one-time-code')
                    ->required()
                    ->maxLength(64),
            ])
            ->visible(static fn (RentriTransmission $record): bool => app(GetSignableFirAction::class)->execute($record) instanceof Fir)
            ->action(function (array $data, RentriTransmission $record): void {
                $fir = app(GetSignableFirAction::class)->execute($record);
                $credential = $record->credential;
                Assert::isInstanceOf($fir, Fir::class);
                Assert::isInstanceOf($credential, RentriCredential::class);

                try {
                    $result = app(SignFirAction::class)->execute($fir, $credential, SafeStringCastAction::cast($data['pin'] ?? ''));
                } catch (RentriException $exception) {
                    Notification::make()->danger()->title(self::text('error'))->body($exception->getMessage())->send();
                    $this->halt();

                    return;
                }

                Notification::make()
                    ->title(self::text('sent', ['fir' => (string) $fir->number]))
                    ->body($result->status->getLabel())
                    ->color($result->status->getColor())
                    ->send();
            });
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function text(string $key, array $replace = []): string
    {
        $value = __('rentri::sign_fir_table.'.$key, $replace);

        return \is_string($value) ? $value : 'rentri::sign_fir_table.'.$key;
    }
}
