<?php

namespace App\Filament\Resources\Organizations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultPaginationPageOption(50)
            ->paginated([20, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('name')->sortable(),
                SelectColumn::make('organization_type')
                    ->options([
                        1 => 'MFS',
                        2 => 'Bank',
                        3 => 'Financial Institute',
                        4 => 'Small Business',
                        5 => 'Corporate Client',
                    ])
                    ->label('Organization Type')
                    ->sortable(),
                TextColumn::make('mobile_no')->icon('heroicon-m-phone'),
                TextColumn::make('address'),
                TextColumn::make('ip_address'),
                SelectColumn::make('status_id')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->label('Status'),
                TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization_type')
                    ->multiple()
                    ->options([
                        1 => 'MFS',
                        2 => 'Bank',
                        3 => 'Financial Institute',
                        4 => 'Small Business',
                        5 => 'Corporate Client',
                    ])
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
//                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
