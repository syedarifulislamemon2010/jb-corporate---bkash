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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

        foreach ($importRows as $index => $row) {
            if ($index === 0 || empty(array_filter((array)$row))) {
                continue;
            }

            $rowArr = array_values((array)$row);
            $mapped = BkashExcelParserService::mapRowData($headerRow, $rowArr, $channelType);

            $refId            = BkashExcelParserService::cleanString($mapped['reference_id'] ?? null, 255);
            $accountName      = BkashExcelParserService::cleanString($mapped['debit_account_title'] ?? null, 150);
            $accountNo        = BkashExcelParserService::cleanString($mapped['debit_account_no'] ?? null, 100);
            $amount           = (float)($mapped['amount'] ?? 0);
            $routingNo        = BkashExcelParserService::cleanString($mapped['debit_routing'] ?? null, 20);
            $bankName         = BkashExcelParserService::cleanString($mapped['credit_routing'] ?? null, 100);
            $branchName       = BkashExcelParserService::cleanString($mapped['credit_bank'] ?? null, 255);
            $debitAccount     = BkashExcelParserService::cleanString($mapped['credit_account_no'] ?? null, 100);
            $txnId            = BkashExcelParserService::cleanString($mapped['txn_id'] ?? (string)Str::uuid(), 100);
            $createDate       = $mapped['create_date'] ?? null;
            $rejectReason     = BkashExcelParserService::cleanString($mapped['reject_reason'] ?? null, 255);

            if ($refId && $amount > 0) {
                $validCount++;
                $totalAmount += $amount;

                $parsedDate = $createDate ? Carbon::parse($createDate) : Carbon::now();
                $valueDate  = \App\Helper\ValueDateHelper::resolve($parsedDate)->toDateString();

                $txnData = [
                    'batch_id'             => $batch->id,
                    'row_sequence'         => $index,
                    'transaction_type'     => $channelType,
                    'reference_id'         => Str::limit($refId, 255, ''),
                    'bb_reference_number'  => $bbRef ? Str::limit($bbRef, 100, '') : null,
                    'txn_id'               => Str::limit($txnId, 100, ''),
                    'debit_account_title'  => $accountName ? Str::limit($accountName, 150, '') : null,
                    'debit_account_no'     => $accountNo ? Str::limit($accountNo, 100, '') : null,
                    'debit_routing'        => $routingNo ? Str::limit($routingNo, 20, '') : null,
                    'credit_account_no'    => $debitAccount ? Str::limit($debitAccount, 100, '') : null,
                    'credit_routing'       => $bankName ? Str::limit($bankName, 100, '') : null,
                    'credit_bank'          => $branchName ? Str::limit($branchName, 255, '') : null,
                    'amount'               => $amount,
                    'status_id'            => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by'           => Str::limit(auth()->user()->name ?? 'SYSTEM', 255, ''),
                    'created_by_id'        => auth()->id(),
                    'create_date'          => $parsedDate,
                    'value_date'           => $valueDate,
                    'file_name'            => $fileName,
                ];

                if ($rejectReason) {
                    $txnData['reject_reason'] = $rejectReason;
                }

                BkashTransaction::create($txnData);
            } else {
                BkashFailedTransaction::create([
                    'batch_id'         => $batch->id,
                    'file_name'        => $fileName,
                    'row_number'       => $index + 1,
                    'transaction_type' => $channelType,
                    'reference_id'     => $refId ? Str::limit($refId, 100, '') : 'N/A',
                    'credit_account_no'=> $debitAccount ? Str::limit($debitAccount, 50, '') : null,
                    'amount'           => $amount,
                    'failure_code'     => 'INVALID_ROW',
                    'reject_reason'    => 'Missing reference ID or invalid amount',
                ]);
            }
        }

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
            ->body("{$validCount} transactions imported for Authorization.")
            ->success()
            ->send();

        $this->redirect(\App\Filament\Resources\BkashTransactionAuthorizations\BkashTransactionAuthorizationResource::getUrl('index'));
    }
}


