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

    protected static ?string $navigationIconColor = 'success';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)
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
        return 'Batch files ready for Final Confirmation';
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
            'index' => ListBkashTransactionConfirmations::route('/'),
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
