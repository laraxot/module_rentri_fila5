<?php

declare(strict_types=1);

namespace Modules\Rentri\Filament\Resources\RentriCredentialResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Modules\Rentri\Enums\RentriEnvironmentEnum;
use Modules\Rentri\Models\RentriCredential;
use Modules\Xot\Filament\Resources\Tables\XotBaseResourceTable;

class RentriCredentialsTable extends XotBaseResourceTable
{
    /**
     * @var class-string<RentriCredential>
     */
    protected static string $model = RentriCredential::class;

    /**
     * @return array<string, Column>
     */
    public function getTableColumns(): array
    {
        return [
            'name' => TextColumn::make('name')->searchable()->sortable(),
            'operator_site_id' => TextColumn::make('operatorSite.name')->searchable(),
            'environment' => TextColumn::make('environment')->badge()->sortable(),
            'subject_identifier' => TextColumn::make('subject_identifier')->searchable(),
            'expires_at' => TextColumn::make('expires_at')->dateTime()->sortable(),
            'is_active' => IconColumn::make('is_active')->boolean()->sortable(),
            'updated_at' => TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            'environment' => SelectFilter::make('environment')->options(RentriEnvironmentEnum::class),
            'is_active' => TernaryFilter::make('is_active'),
        ];
    }

    public function getTableActions(): array
    {
        return [
            'edit' => EditAction::make(),
        ];
    }

    public function getTableBulkActions(): array
    {
        return [
            'delete' => DeleteBulkAction::make(),
        ];
    }
}
