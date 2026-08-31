<?php

namespace App\Filament\Resources\BkashTransactions;

use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Filament\Resources\BkashTransactions\Pages\UploadBkashExcel;
use App\Filament\Resources\BkashTransactions\Schemas\BkashTransactionForm;
use App\Filament\Resources\BkashTransactions\Tables\BkashTransactionsTable;
use App\Models\BkashTransaction;
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

    protected static array $globallySearchableAttributes = [
        'reference_id',
        'txn_id',
        'debit_account_no',
        'debit_account_title',
        'credit_account_no',
        'file_name',
    ];

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Txn ID'   => $record->txn_id ?? 'N/A',
            'Channel'  => $record->transaction_type,
            'Amount'   => 'BDT ' . BkashTransaction::formatBdtAmount((float) $record->amount),
            'Account'  => $record->debit_account_no ?? 'N/A',
            'File'     => $record->file_name ?? 'N/A',
        ];
    }

    protected static bool $shouldRegisterNavigation = true;

    protected static \UnitEnum|string|null $navigationGroup = 'Transaction Pipeline';

    protected static ?string $navigationLabel = 'Checker - Verify Files';

    protected static ?string $pluralModelLabel = 'Checker - Verify Files';

    protected static ?int $navigationSort = 1;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationIconColor = 'info';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
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
            'index'  => ListBkashTransactions::route('/'),
            'upload' => UploadBkashExcel::route('/upload'),
        ];
    }
}
