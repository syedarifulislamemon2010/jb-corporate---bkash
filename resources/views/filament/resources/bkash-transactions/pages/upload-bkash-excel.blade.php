<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6" aria-label="bKash Settlement File Ingest Form">
        {{ $this->form }}

        <div class="flex items-center gap-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-document-arrow-up" aria-label="Process and Ingest File">
                Process & Ingest File
            </x-filament::button>

            <x-filament::button color="gray" tag="a" href="{{ \App\Filament\Resources\BkashTransactions\BkashTransactionResource::getUrl('index') }}" aria-label="Cancel file ingestion and return to list">
                Cancel
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
