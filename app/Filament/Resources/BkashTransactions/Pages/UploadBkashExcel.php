<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

use Carbon\Carbon;

use App\Services\BkashExcelParserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadBkashExcel extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BkashTransactionResource::class;

    protected string $view = 'filament.resources.bkash-transactions.pages.upload-bkash-excel';

    protected static ?string $title = 'Upload bKash Excel File';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ingest bKash Settlement File')
                    ->description('Upload system-generated Excel file (A2A, BEFTN, RTGS) from Multi-Bank Tool (XLS) or Oracle ERP (XLSX).')
                    ->schema([
                        Select::make('channel_type')
                            ->label('Transaction Channel')
                            ->options([
                                'A2A'   => 'Account to Account (A2A)-Janata Bank PLC.',
                                'BEFTN' => 'BEFTN',
                                'RTGS'  => 'RTGS',
                            ])
                            ->required(),

                        FileUpload::make('file')
                            ->label('Select File (.xls / .xlsx / .csv)')
                            ->directory('Bkash_Uploads')
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }


    public function submit(): void
    {
        $formData = $this->form->getState();
        $relativeFile = $formData['file'] ?? '';
        if (is_array($relativeFile)) {
            $relativeFile = array_values($relativeFile)[0] ?? '';
        }

        $possiblePaths = [
            Storage::disk('public')->path($relativeFile),
            Storage::disk('local')->path($relativeFile),
            storage_path('app/public/' . $relativeFile),
            storage_path('app/' . $relativeFile),
        ];

        $filePath = null;
        foreach ($possiblePaths as $candidate) {
            if (is_file($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        if (!$filePath) {
            Notification::make()
                ->title('File not found')
                ->body('Uploaded file could not be located in storage. Please try re-selecting the file.')
                ->danger()
                ->send();
            return;
        }

        $fileName = basename($filePath);
        $channelType = $formData['channel_type'];
        $importRows = BkashExcelParserService::parseFile($filePath);



        if (empty($importRows)) {
            Notification::make()
                ->title('No valid rows found in file')
                ->warning()
                ->send();
            return;
        }

        $headerRow = array_values((array)($importRows[0] ?? []));

        // File-Level Validation - Single Debit Account Rule
        $fileLevelValidation = BkashExcelParserService::validateFileLevelDebitAccounts($importRows, $headerRow);

        if (!$fileLevelValidation['is_valid']) {
            Notification::make()
                ->title('Upload Rejected: Multiple Debit Accounts')
                ->body($fileLevelValidation['error_message'])
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        $sha256 = hash_file('sha256', $filePath);

        $batchData = [
            'file_name'        => $fileName,
            'transaction_type' => $channelType,
            'total_data'       => 0,
            'status_id'        => 1000,
            'created_by'       => auth()->user()->name ?? 'SYSTEM',
            'create_date'      => Carbon::now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transaction_batch', 'sha256')) {
            $batchData['sha256'] = $sha256;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transaction_batch', 'total_amount')) {
            $batchData['total_amount'] = 0.00;
        }

        $batch = BkashTransactionBatch::create($batchData);

        $validCount = 0;
        $totalAmount = 0.0;

        $uploaderName = auth()->user()->name ?? 'SYSTEM';
        $uploaderId   = auth()->id();

        DB::transaction(function () use ($importRows, $headerRow, $channelType, $batch, $fileName, $uploaderName, $uploaderId, &$validCount, &$totalAmount) {
            foreach ($importRows as $index => $row) {
                if ($index === 0 || empty(array_filter((array)$row))) {
                    continue;
                }

                $rowArr = array_values((array)$row);
                $mapped = BkashExcelParserService::mapRowData($headerRow, $rowArr, $channelType);

                $refId  = BkashExcelParserService::cleanString($mapped['reference_id'] ?? null, 255);
                $amount = (float)($mapped['amount'] ?? 0);

                if ($refId && $amount > 0) {
                    $validCount++;
                    $totalAmount += $amount;

                    $txnData = BkashExcelParserService::buildTransactionData(
                        $mapped,
                        $channelType,
                        $batch,
                        $index,
                        $fileName,
                        $uploaderName,
                        $uploaderId
                    );

                    BkashTransaction::create($txnData);
                } else {
                    $failedData = BkashExcelParserService::buildFailedTransactionData(
                        $mapped,
                        $channelType,
                        $batch,
                        $index,
                        $fileName,
                        'INVALID_ROW',
                        'Missing reference ID or invalid amount'
                    );

                    BkashFailedTransaction::create($failedData);
                }
            }
        });

        $updateData = ['total_data' => $validCount];
        if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transaction_batch', 'total_amount')) {
            $updateData['total_amount'] = $totalAmount;
        }
        $batch->update($updateData);

        if ($validCount > 0) {
            NotificationService::dispatchStage1($fileName, $validCount, $totalAmount, auth()->user());
        }

        Notification::make()
            ->title('File Ingested Successfully!')
            ->body("{$validCount} transactions imported for Checker verification.")
            ->success()
            ->send();

        $this->redirect(BkashTransactionResource::getUrl('index'));
    }
}


