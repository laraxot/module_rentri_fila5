<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriTransmissionResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Modules\Rentri\Enums\RentriServiceEnum;
use Modules\Rentri\Enums\TransmissionStatusEnum;
use Modules\Rentri\Filament\Actions\PollRentriTransmissionTableAction;
use Modules\Rentri\Filament\Actions\RetryRentriTransmissionTableAction;
use Modules\Rentri\Models\RentriTransmission;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class RentriTransmissionsTable extends XotBaseResourceTable
{
    /**
     * @var class-string<RentriTransmission>
     */
    protected static string $model = RentriTransmission::class;

    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'created_at' => TextColumn::make('created_at')->dateTime()->sortable(),
            'service' => TextColumn::make('service')->badge()->sortable(),
            'method' => TextColumn::make('method')->badge(),
            'path' => TextColumn::make('path')->limit(60)->searchable(),
            'subject_type' => TextColumn::make('subject_type')
                ->formatStateUsing(static fn (?string $state): string => $state === null ? '' : class_basename($state))
                ->toggleable(),
            'subject_id' => TextColumn::make('subject_id')->toggleable(),
            'status' => TextColumn::make('status')->badge()->sortable(),
            'response_status' => TextColumn::make('response_status')->sortable(),
            'transaction_id' => TextColumn::make('transaction_id')->searchable()->toggleable(isToggledHiddenByDefault: true),
            'attempts' => TextColumn::make('attempts')->sortable()->toggleable(isToggledHiddenByDefault: true),
            'error' => TextColumn::make('error')->limit(80)->wrap()->toggleable(),
            'completed_at' => TextColumn::make('completed_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            'status' => SelectFilter::make('status')->options(TransmissionStatusEnum::class),
            'service' => SelectFilter::make('service')->options(RentriServiceEnum::class),
        ];
    }

    /**
     * @return array<string, Action|ActionGroup>
     */
    public function getTableActions(): array
    {
        return [
            'view' => ViewAction::make(),
            'poll' => PollRentriTransmissionTableAction::make(),
            'retry' => RetryRentriTransmissionTableAction::make(),
        ];
    }

    public function getTableBulkActions(): array
    {
        return [];
    }

    /** Header senza "crea": la tabella e' in sola lettura. */
    public function getTableHeaderActions(): array
    {
        return [];
    }
}
