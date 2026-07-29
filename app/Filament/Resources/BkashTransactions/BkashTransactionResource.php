<?php

namespace App\Filament\Resources\BkashTransactions;

use App\Filament\Resources\BkashTransactions\Pages\CreateBkashTransaction;
use App\Filament\Resources\BkashTransactions\Pages\EditBkashTransaction;
use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Filament\Resources\BkashTransactions\Schemas\BkashTransactionForm;
use App\Filament\Resources\BkashTransactions\Tables\BkashTransactionsTable;
use App\Models\BkashTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BkashTransactionResource extends Resource
{
    protected static ?string $model = BkashTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'reference_id';

    protected static ?string $navigationLabel = 'Bkash Transactions';

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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBkashTransactions::route('/'),
            'create' => CreateBkashTransaction::route('/create'),
            'edit' => EditBkashTransaction::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}