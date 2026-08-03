<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ListBkashTransactions extends ListRecords
{
    protected static string $resource = BkashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Transaction'),

            Action::make('upload_excel')
                ->label('Upload bKash Excel File')
                ->icon('heroicon-o-document-arrow-up')
                ->color('primary')
                ->form([
                    Select::make('channel_type')
                        ->label('Channel Type')
                        ->options([
                            'A2A'   => 'Account to Account (A2A)',
                            'BEFTN' => 'BEFTN',
                            'RTGS'  => 'RTGS',
                        ])
                        ->required(),
                    FileUpload::make('file')
                        ->label('Excel / CSV File')
                        ->directory('Bkash_Uploads')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/' . $data['file']);

                    if (!file_exists($filePath)) {
                        return;
                    }

                    $fileName = basename($filePath);
                    $channelType = $data['channel_type'];

                    $importRows = $this->parseRowsFromFile($filePath);

                    if (empty($importRows)) {
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
                        }
                    }

                    $batch->update([
                        'total_data'   => $validCount,
                        'total_amount' => $totalAmount,
                    ]);

                    if ($validCount > 0) {
                        NotificationService::dispatchStage1($fileName, $validCount, $totalAmount);
                    }
                }),
        ];
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