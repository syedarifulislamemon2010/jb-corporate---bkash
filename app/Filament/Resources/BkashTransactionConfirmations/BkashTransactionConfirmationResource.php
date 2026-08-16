<?php

namespace App\Filament\Resources\BkashTransactionConfirmations;

use App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations;
use App\Filament\Resources\BkashTransactionConfirmations\Schemas\BkashTransactionConfirmationForm;
use App\Filament\Resources\BkashTransactionConfirmations\Tables\BkashTransactionConfirmationsTable;
use App\Models\BkashTransaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BkashTransactionConfirmationResource extends Resource
{
    protected static ?string $model = BkashTransaction::class;

    protected static ?string $recordTitleAttribute = 'reference_id';

    protected static \UnitEnum|string|null $navigationGroup = 'Transaction Pipeline';

    protected static ?string $navigationLabel = 'Transaction Confirmation';

    protected static ?string $pluralModelLabel = 'Transaction Confirmation';

    protected static ?int $navigationSort = 3;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_id', 1002)
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

    public static function form(Schema $schema): Schema
    {
        return BkashTransactionConfirmationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BkashTransactionConfirmationsTable::configure($table);
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
            'index' => ListBkashTransactionConfirmations::route('/'),
        ];
    }
}
