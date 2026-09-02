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

    protected static ?string $recordTitleAttribute = 'txn_id';

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Txn ID'   => $record->txn_id ?? 'N/A',
            'Channel'  => $record->transaction_type,
            'Amount'   => 'BDT ' . BkashTransaction::formatBdtAmount((float) $record->amount),
            'Account'  => $record->beneficiary_account_no ?? 'N/A',
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

        public static function getNavigationBadge(): ?string
    {
        try {
            $count = static::getEloquentQuery()
                ->whereNotNull('batch_id')
                ->distinct('batch_id')
                ->count('batch_id');

            if ($count === 0) {
                $count = static::getEloquentQuery()
                    ->whereNotNull('file_name')
                    ->distinct('file_name')
                    ->count('file_name');
            }

            return $count > 0 ? (string) $count : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Batch files pending Checker verification';
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

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
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

    public static function getGloballySearchableAttributes(): array
    {
        try {
            $table = (new static::$model)->getTable();
            $searchable = ['file_name'];

            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'txn_id')) {
                $searchable[] = 'txn_id';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'reference_id')) {
                $searchable[] = 'reference_id';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'bb_reference_number')) {
                $searchable[] = 'bb_reference_number';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'reference')) {
                $searchable[] = 'reference';
            }

            return $searchable;
        } catch (\Throwable $e) {
            return ['file_name'];
        }
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        $id = $record->txn_id ?: ($record->reference_id ?? ($record->bb_reference_number ?? ($record->reference ?? 'Record')));
        return "Txn: {$id}";
    }
}
