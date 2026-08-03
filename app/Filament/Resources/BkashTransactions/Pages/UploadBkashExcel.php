<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Carbon\Carbon;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
        $filePath = storage_path('app/public/' . $formData['file']);

        if (!file_exists($filePath)) {
            Notification::make()
                ->title('File not found')
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

        $batch = BkashTransactionBatch::create([
            'file_name'        => $fileName,
            'transaction_type' => $channelType,
            'sha256'           => $sha256,
            'total_data'       => 0,
            'total_amount'     => 0.00,
            'status_id'        => 1000,
            'created_by'       => auth()->user()->name ?? 'SYSTEM',
            'create_date'      => Carbon::now(),
        ]);

        $validCount = 0;
        $totalAmount = 0.0;

        foreach ($importRows as $index => $row) {
            if ($index === 0 || empty(array_filter((array)$row))) {
                continue;
            }

            $rowArr      = array_values((array)$row);
            $refId       = trim((string)($rowArr[0] ?? ''));
            $beneName    = trim((string)($rowArr[1] ?? ''));
            $beneAccount = trim((string)($rowArr[2] ?? ''));
            $amount      = (float)($rowArr[3] ?? 0);
            $routingNo   = trim((string)($rowArr[4] ?? ''));
            $bankName    = trim((string)($rowArr[5] ?? ''));
            $debitAcc    = trim((string)($rowArr[6] ?? $rowArr[4] ?? '0100202707747'));

            if ($refId && $beneAccount && $amount > 0) {
                $validCount++;
                $totalAmount += $amount;

                BkashTransaction::create([
                    'batch_id'             => $batch->id,
                    'file_name'            => $fileName,
                    'transaction_type'     => $channelType,
                    'reference_id'         => $refId,
                    'txn_id'               => (string)Str::uuid(),
                    'debit_account_no'     => $debitAcc,
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
                    'reference_id'     => $refId ?: null,
                    'credit_account_no'=> $beneAccount ?: null,
                    'amount'           => $amount,
                    'failure_code'     => 'INVALID_ROW',
                    'reject_reason'    => 'Missing reference ID or invalid amount',
                ]);
            }
        }

        $batch->update([
            'total_data'   => $validCount,
            'total_amount' => $totalAmount,
        ]);

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
}
