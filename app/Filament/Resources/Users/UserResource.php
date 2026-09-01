<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 2;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->emptyStateHeading('No Users Yet')
            ->emptyStateDescription('Create your first user to get started with the approval workflow.')
            ->emptyStateIcon('heroicon-o-user-plus')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(function (TextColumn $component, $record, Table $table): string {
                        $paginator = $table->getRecords();
                        if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator || $paginator instanceof \Illuminate\Contracts\Pagination\Paginator) {
                            $offset = ($paginator->currentPage() - 1) * $paginator->perPage();
                            $index = array_search($record->getKey(), $paginator->pluck($record->getKeyName())->toArray(), true);
                            return (string) ($offset + ($index !== false ? $index + 1 : 1));
                        }
                        return '1';
                    })
                    ->alignCenter(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('mobile_no'),
                TextColumn::make('organization'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
//    public static function canViewAny(): bool
//    {
//        return auth()->user()
//            ?->hasOrganizationPermission('users.view') ?? false;
//    }

}
