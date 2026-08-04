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

            $refId            = $this->cleanString($mapped['reference_id'] ?? null, 255);
            $accountName      = $this->cleanString($mapped['debit_account_title'] ?? null, 150);
            $accountNo        = $this->cleanString($mapped['debit_account_no'] ?? null, 100);
            $amount           = (float)($mapped['amount'] ?? 0);
            $routingNo        = $this->cleanString($mapped['debit_routing'] ?? null, 20);
            $bankName         = $this->cleanString($mapped['credit_routing'] ?? null, 100);
            $branchName       = $this->cleanString($mapped['credit_bank'] ?? null, 255);
            $debitAccount     = $this->cleanString($mapped['credit_account_no'] ?? null, 100);
            $txnId            = $this->cleanString($mapped['txn_id'] ?? (string)Str::uuid(), 100);
            $createDate       = $mapped['create_date'] ?? null;
            $rejectReason     = $this->cleanString($mapped['reject_reason'] ?? null, 255);

            if ($refId && $amount > 0) {
                $validCount++;
                $totalAmount += $amount;

                $txnData = [
                    'batch_id'             => $batch->id,
                    'transaction_type'     => $channelType,
                    'reference_id'         => Str::limit($refId, 255, ''),
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
                    'create_date'          => $createDate ? Carbon::parse($createDate) : Carbon::now(),
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
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xls', 'xlsx'])) {
            try {
                if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    foreach ($sheet->toArray(null, true, true, false) as $row) {
                        $importRows[] = $row;
                    }
                } elseif (class_exists('Maatwebsite\Excel\Facades\Excel')) {
                    $sheets = \Maatwebsite\Excel\Facades\Excel::toCollection(collect([]), $filePath)->toArray();
                    $importRows = array_shift($sheets) ?? [];
                }
            } catch (\Throwable $e) {
                $importRows = [];
            }
        }

        if (empty($importRows) && $ext === 'csv' && is_file($filePath)) {
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 5000, ',')) !== false) {
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

        // Header-to-DB-field mapping table (user-defined):
        // Ref / Ref No = reference_id
        // Date / Execution Date = create_date
        // Return Date = return_date
        // A/C Name / Bank_Account_Name / Bene. Name = debit_account_title
        // Account No / Beneficiary A/C No / Bank Account Number / Bank_Account_No = debit_account_no
        // Amount / Amount(BDT) / Amount in Taka = amount
        // Routing Code / RoutingNumber / Bene. Routing No = debit_routing
        // Bank Name / Bene. Bank Name = credit_routing
        // Branch Name / Bene. Branch Name = credit_bank
        // Bank & Branch Name = credit_routing (combined bank+branch)
        // Debit Account = credit_account_no
        // Txn ID = txn_id
        // Reject Reason = reject_reason
        // SL = SKIP (serial number)

        foreach ($headers as $colIndex => $headerName) {
            $rawHeader = trim((string)$headerName);
            $cleanHeader = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader));
            $val = $row[$colIndex] ?? null;

            if ($cleanHeader === '' || $cleanHeader === 'sl') {
                continue;
            }

            // Ref / Ref No / Ref No. → reference_id
            if (in_array($cleanHeader, ['ref', 'refno', 'reference', 'referenceid', 'refid'])) {
                $mapped['reference_id'] = $this->cleanString((string)$val, 255);
            }
            // Date / Execution Date → create_date
            elseif (in_array($cleanHeader, ['date', 'executiondate', 'createdate', 'transactiondate', 'txndate'])) {
                $mapped['create_date'] = $val;
            }
            // Return Date → return_date
            elseif (in_array($cleanHeader, ['returndate'])) {
                $mapped['return_date'] = $val;
            }
            // A/C Name / Bank_Account_Name / Bene. Name / Bank Account Name → debit_account_title
            elseif (in_array($cleanHeader, ['acname', 'bankaccountname', 'benename', 'beneficiaryname', 'accountname', 'beneaccountname'])) {
                $mapped['debit_account_title'] = $this->cleanString((string)$val, 150);
            }
            // Account No / Beneficiary A/C No / Bank Account Number / Bank_Account_No → debit_account_no
            elseif (in_array($cleanHeader, ['accountno', 'beneficiaryacno', 'bankaccountnumber', 'bankaccountno', 'beneaccountno', 'acno'])) {
                $mapped['debit_account_no'] = $this->cleanString((string)$val, 100);
            }
            // Amount / Amount(BDT) / Amount in Taka → amount
            elseif (in_array($cleanHeader, ['amount', 'amountbdt', 'amountintaka'])) {
                $cleanVal = preg_replace('/[^0-9.]/', '', str_replace(',', '', (string)$val));
                $mapped['amount'] = (float)$cleanVal;
            }
            // Routing Code / RoutingNumber / Bene. Routing No → debit_routing
            elseif (in_array($cleanHeader, ['routingcode', 'routingnumber', 'beneroutingno', 'routingno'])) {
                $mapped['debit_routing'] = $this->cleanString((string)$val, 20);
            }
            // Bank Name / Bene. Bank Name → credit_routing
            elseif (in_array($cleanHeader, ['bankname', 'benebankname'])) {
                $mapped['credit_routing'] = $this->cleanString((string)$val, 100);
            }
            // Branch Name / Bene. Branch Name → credit_bank
            elseif (in_array($cleanHeader, ['branchname', 'benebranchname'])) {
                $mapped['credit_bank'] = $this->cleanString((string)$val, 255);
            }
            // Bank & Branch Name (combined, used in RTGS) → credit_routing
            elseif (in_array($cleanHeader, ['bankbranchname'])) {
                $mapped['credit_routing'] = $this->cleanString((string)$val, 100);
            }
            // Debit Account → credit_account_no
            elseif (in_array($cleanHeader, ['debitaccount', 'debitaccountno'])) {
                $mapped['credit_account_no'] = $this->cleanString((string)$val, 100);
            }
            // Txn ID → txn_id
            elseif (in_array($cleanHeader, ['txnid', 'transactionid'])) {
                $mapped['txn_id'] = $this->cleanString((string)$val, 100);
            }
            // Reject Reason → reject_reason
            elseif (in_array($cleanHeader, ['rejectreason'])) {
                $mapped['reject_reason'] = $this->cleanString((string)$val, 255);
            }
        }

        // NO positional fallback — only header-based mapping to avoid garbage values

        return $mapped;
    }

}


