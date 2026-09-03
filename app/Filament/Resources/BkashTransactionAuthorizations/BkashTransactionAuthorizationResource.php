<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations;

use App\Filament\Resources\BkashTransactionAuthorizations\Pages\ListBkashTransactionAuthorizations;
use App\Filament\Resources\BkashTransactionAuthorizations\Schemas\BkashTransactionAuthorizationForm;
use App\Filament\Resources\BkashTransactionAuthorizations\Tables\BkashTransactionAuthorizationsTable;
use App\Models\BkashTransaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BkashTransactionAuthorizationResource extends Resource
{
    protected static ?string $model = BkashTransaction::class;

    protected static ?string $recordTitleAttribute = 'reference_id';

    protected static \UnitEnum|string|null $navigationGroup = 'Transaction Pipeline';

    protected static ?string $navigationLabel = 'Transaction Authorization';

    protected static ?int $navigationSort = 2;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationIconColor = 'warning';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_id', BkashTransaction::STATUS_CHECKED)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

        public static function getNavigationBadge(): ?string
    {
        try {
            $count = \App\Models\BkashTransaction::where('status_id', 1001)->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Transactions awaiting 1st Authorization';
    }

    public static function form(Schema $schema): Schema
    {
        return BkashTransactionAuthorizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BkashTransactionAuthorizationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBkashTransactionAuthorizations::route('/'),
        ];
    }
}
