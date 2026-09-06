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
        return 'Batch files awaiting 1st Authorization';
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
            'index' => ListBkashTransactionAuthorizations::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['txn_id', 'reference_id', 'file_name'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return "Txn: {$record->txn_id}";
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'File'   => $record->file_name ?? 'N/A',
            'Amount' => 'BDT ' . \App\Models\BkashTransaction::formatBdtAmount((float) ($record->amount ?? 0)),
        ];
    }
}
