<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Query\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $organizations = Organization::where('status_id', 1)->pluck('name', 'id')->toArray();
        return $table
			->recordUrl(null)
            ->defaultPaginationPageOption(50)
            ->paginated([20, 50, 100])
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('name'),
                SelectColumn::make('organization')
                    ->options($organizations),
                TextColumn::make('mobile_no'),
                TextColumn::make('email')
                    ->label('Email address'),
                TextColumn::make('email_verified_at'),
                TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->options($organizations),
                Filter::make('name')
                    ->form([
                        TextInput::make('name')
                            ->label('Name'),
                    ]),
                SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
