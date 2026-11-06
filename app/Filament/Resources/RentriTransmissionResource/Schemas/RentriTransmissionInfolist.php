<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriTransmissionResource\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontFamily;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Xot\Filament\Resources\Schemas\XotBaseResourceInfolist;
use function Safe\json_encode;

/**
 * Dettaglio di una trasmissione. Richiesta e risposta sono gia' oscurate al salvataggio.
 */
class RentriTransmissionInfolist extends XotBaseResourceInfolist
{
    /**
     * @return array<string, Component>
     */
    public function getInfolistSchema(): array
    {
        return [
            'request' => Section::make('request')
                ->columns(2)
                ->schema([
                    TextEntry::make('service')->badge(),
                    TextEntry::make('method')->badge(),
                    TextEntry::make('path')->fontFamily(FontFamily::Mono)->copyable(),
                    TextEntry::make('subject_type')->formatStateUsing(static fn (?string $state): string => $state === null ? '' : class_basename($state)),
                    TextEntry::make('subject_id'),
                    TextEntry::make('credential.name'),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('request_payload')
                        ->state(static fn (RentriTransmission $record): string => self::json($record->request_payload))
                        ->fontFamily(FontFamily::Mono)
                        ->columnSpanFull(),
                ]),
            'outcome' => Section::make('outcome')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')->badge(),
                    TextEntry::make('response_status'),
                    TextEntry::make('transaction_id')->fontFamily(FontFamily::Mono)->copyable(),
                    TextEntry::make('attempts'),
                    TextEntry::make('completed_at')->dateTime(),
                    TextEntry::make('error')->columnSpanFull(),
                    TextEntry::make('response_body')
                        ->state(static fn (RentriTransmission $record): string => self::json($record->response_body))
                        ->fontFamily(FontFamily::Mono)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @param  array<array-key, mixed>|null  $value
     */
    private static function json(?array $value): string
    {
        if ($value === null || $value === []) {
            return '';
        }
        $encoded = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded;
    }
}
