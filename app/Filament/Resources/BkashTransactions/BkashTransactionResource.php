<?php

namespace App\Filament\Resources\BkashTransactions;

use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Filament\Resources\BkashTransactions\Schemas\BkashTransactionForm;
use App\Filament\Resources\BkashTransactions\Tables\BkashTransactionsTable;
use App\Models\BkashTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BkashTransactionResource extends Resource
{
    protected static ?string $model = BkashTransaction::class;

    protected static ?string $recordTitleAttribute = 'reference_id';

    // 👈 আগের সাইডবার নাম অপরিবর্তিত রাখা হলো
    protected static ?string $navigationLabel = 'Bkash Transactions';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    /**
     * 🔍 Checker লেভেলের জন্য: status_id = 1000 (Pending for Checker)
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_id', 1000)
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
        return BkashTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BkashTransactionsTable::configure($table);
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
            'index' => ListBkashTransactions::route('/'),
        ];
    }
}