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
                                'A2A'   => 'Account to Account (A2A)',
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
            if (file_exists($candidate)) {
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
        $importRows = $this->parseRowsFromFile($filePath);


        if (empty($importRows)) {
            Notification::make()
                ->title('No valid rows found in file')
                ->warning()
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

        $headerRow = array_values((array)($importRows[0] ?? []));

        foreach ($importRows as $index => $row) {
            if ($index === 0 || empty(array_filter((array)$row))) {
                continue;
            }

            $rowArr = array_values((array)$row);
            $mapped = $this->mapRowData($headerRow, $rowArr);

            $refId       = $mapped['reference_id'] ?? null;
            $beneName    = $mapped['credit_account_title'] ?? null;
            $beneAccount = $mapped['credit_account_no'] ?? null;
            $amount      = (float)($mapped['amount'] ?? 0);
            $routingNo   = $mapped['credit_routing'] ?? null;
            $bankName    = $mapped['credit_bank'] ?? null;
            $debitAcc    = $mapped['debit_account_no'] ?? '0100202707747';
            $txnId       = $mapped['txn_id'] ?? (string)Str::uuid();

            if ($refId && $beneAccount && $amount > 0) {
                $validCount++;
                $totalAmount += $amount;

                BkashTransaction::create([
                    'batch_id'             => $batch->id,
                    'file_name'            => $fileName,
                    'transaction_type'     => $channelType,
                    'reference_id'         => $refId,
                    'txn_id'               => $txnId,
                    'debit_account_no'     => $debitAcc ?: '0100202707747',
                    'credit_account_no'    => $beneAccount,
                    'credit_account_title' => $beneName,
                    'credit_routing'       => $routingNo,
                    'credit_bank'          => $bankName,
                    'amount'               => $amount,
                    'status_id'            => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by'           => auth()->user()->name ?? 'SYSTEM',
                    'create_date'          => Carbon::now(),
                ]);
            } else {
                BkashFailedTransaction::create([
                    'batch_id'         => $batch->id,
                    'file_name'        => $fileName,
                    'row_number'       => $index + 1,
                    'transaction_type' => $channelType,
                    'reference_id'     => $refId ? Str::limit($refId, 100, '') : 'N/A',
                    'credit_account_no'=> $beneAccount ? Str::limit($beneAccount, 50, '') : null,
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
            NotificationService::dispatchStage1($fileName, $validCount, $totalAmount);
        }

        Notification::make()
            ->title('File Ingested Successfully!')
            ->body("{$validCount} transactions imported for Checker verification.")
            ->success()
            ->send();

        $this->redirect(BkashTransactionResource::getUrl('index'));
    }

    private function parseRowsFromFile(string $filePath): array
    {
        $importRows = [];

        if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
            try {
                $sheets = \Maatwebsite\Excel\Facades\Excel::toCollection(collect([]), $filePath)->toArray();
                $importRows = array_shift($sheets) ?? [];
            } catch (\Throwable $e) {
                $importRows = [];
            }
        }

        if (empty($importRows) && file_exists($filePath)) {
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $importRows[] = $data;
                }
                fclose($handle);
            }
        }

        return $importRows;
    }

    private function cleanString(?string $value, int $maxLength = 100): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string)$value);
        $clean = trim($clean);

        if (empty($clean)) {
            return null;
        }

        return Str::limit($clean, $maxLength, '');
    }

    private function mapRowData(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $colIndex => $headerName) {
            $rawHeader = (string)$headerName;
            $cleanHeader = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader));
            $val = $row[$colIndex] ?? null;

            if ($cleanHeader === '') {
                continue;
            }

            if (in_array($cleanHeader, ['ref', 'refno', 'reference', 'referenceid', 'refid', 'externalref', 'instructionid', 'batchref'])) {
                $mapped['reference_id'] = $this->cleanString((string)$val, 100);
            }
            elseif (in_array($cleanHeader, ['txnid', 'transactionid', 'utr', 'transactionno', 'txid'])) {
                $mapped['txn_id'] = $this->cleanString((string)$val, 100);
            }
            elseif (in_array($cleanHeader, ['acname', 'bankaccountname', 'benename', 'beneficiaryname', 'credittitle', 'accounttitle', 'title', 'beneaccountname'])) {
                $mapped['credit_account_title'] = $this->cleanString((string)$val, 255);
            }
            elseif (in_array($cleanHeader, ['accountno', 'beneficiaryacno', 'bankaccountnumber', 'creditaccountno', 'creditaccount', 'beneaccount', 'beneaccountno'])) {
                $mapped['credit_account_no'] = $this->cleanString((string)$val, 60);
            }
            elseif (in_array($cleanHeader, ['debitaccount', 'debitaccountno', 'sourceaccount', 'senderaccount', 'fromaccount'])) {
                $mapped['debit_account_no'] = $this->cleanString((string)$val, 60);
            }
            elseif (in_array($cleanHeader, ['amount', 'amountbdt', 'amountintaka', 'trnamount', 'totalamount', 'sum', 'value'])) {
                $mapped['amount'] = (float)preg_replace('/[^0-9.]/', '', (string)$val);
            }
            elseif (in_array($cleanHeader, ['routingcode', 'routingnumber', 'beneroutingno', 'routingno', 'creditrouting', 'routing'])) {
                $mapped['credit_routing'] = $this->cleanString((string)$val, 20);
            }
            elseif (in_array($cleanHeader, ['bankname', 'benebankname', 'creditbank', 'bank'])) {
                $mapped['credit_bank'] = $this->cleanString((string)$val, 100);
            }
        }

        if (empty($mapped['reference_id']) && isset($row[0])) {
            $mapped['reference_id'] = $this->cleanString((string)$row[0], 100);
        }
        if (empty($mapped['credit_account_title']) && isset($row[1])) {
            $mapped['credit_account_title'] = $this->cleanString((string)$row[1], 255);
        }
        if (empty($mapped['credit_account_no']) && isset($row[2])) {
            $mapped['credit_account_no'] = $this->cleanString((string)$row[2], 60);
        }
        if (empty($mapped['amount']) && isset($row[3])) {
            $mapped['amount'] = (float)preg_replace('/[^0-9.]/', '', (string)$row[3]);
        }
        if (empty($mapped['credit_routing']) && isset($row[4])) {
            $mapped['credit_routing'] = $this->cleanString((string)$row[4], 20);
        }
        if (empty($mapped['credit_bank']) && isset($row[5])) {
            $mapped['credit_bank'] = $this->cleanString((string)$row[5], 100);
        }

        return $mapped;
    }
}


