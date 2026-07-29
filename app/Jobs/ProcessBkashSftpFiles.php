<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\Bank;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class ProcessBkashSftpFiles implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
    }

    public function handle(): void
    {
        try {
            $file_list = Storage::disk('bkash_sftp')->allFiles('/var/www/html/beftn_bach_rtgs/storage/app/public/bkash/');
            dd($file_list);
            $dir = storage_path("app/public/Bkash_Files/");

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            foreach ($file_list as $key => $value) {
                $filename = basename($value);
                $topath = 'Bkash_Files/' . $filename;

                Storage::disk('public')->put($topath, Storage::disk('bkash_sftp')->get($value));
                $localFilePath = storage_path("app/public/Bkash_Files_Uploaded" . $topath);

                if (File::exists($localFilePath)) {
                    $this->uploadContent(new \Illuminate\Http\File($localFilePath));
                }
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            throw new \Exception($ex->getMessage(), Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }
    }

    public function is_dir_empty($dir)
    {
        if (!is_readable($dir)) return NULL;
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return FALSE;
            }
        }
        return TRUE;
    }

    public function uploadContent($file)
    {
        if ($file) {
            $datas = Excel::toCollection(collect([]), $file->getRealPath())->toArray();
            $importData_arr = array_shift($datas);

            $filename = $file->getFilename();
            $fileType = explode('_', $filename)[0];
            $extension = $file->getExtension();
            $fileSize = $file->getSize();

            $this->checkUploadedFileProperties($extension, $fileSize);

            $filepath = storage_path("app/public/Bkash_Files_Uploaded/" . str_replace('-', '', Carbon::now()->toDateString()) . '/');
            if (!is_dir($filepath)) {
                mkdir($filepath, 0777, true);
            }

            File::copy($file->getRealPath(), $filepath . $filename);

            $message = [];
            $emptymessage = [];
            $totaldebit = 0;

            foreach ($importData_arr as $key => $importData) {
                if ($key == 0) {
                    continue;
                } else {
                    if (is_null($importData_arr[$key][0]) && !isset($importData_arr[$key][1]) && !isset($importData_arr[$key][2])
                        && !isset($importData_arr[$key][3]) && !isset($importData_arr[$key][4])) {
                        unset($importData_arr[$key]);
                        continue;
                    }

                    for ($i = 0; $i < 5; $i++) {
                        if (isset($importData[0]) && !preg_match('/^[a-zA-Z0-9.-]+$/', $importData[0])) {
                            array_push($message, 'Invalid Data/Special Character at line no ' . ($key + 1) . ' and column no 1');
                        } else if (isset($importData[$i]) && !preg_match('/^[a-zA-Z0-9 .\-\/]+$/', $importData[$i])) {
                            array_push($message, 'Invalid Data/Special Character at line no ' . ($key + 1) . ' and column no ' . ($i + 1));
                        }
                        if (!isset($importData[$i])) {
                            array_push($emptymessage, 'Empty Value found at line no ' . ($key + 1) . ' and column no ' . ($i + 1));
                        }
                    }

                    if (count($emptymessage) > 0) {
                        throw ValidationException::withMessages(['file' => $emptymessage]);
                    }

                    if (strlen($importData[0]) > 17) {
                        array_push($message, 'Invalid Account No at line no ' . ($key + 1));
                    }

                    if ($routingNo) {
                        $branch = Branch::where('routingno', $routingNo)->first();

                        if (!isset($branch->branchname)) {
                            array_push($message, 'Invalid Routing No at line no ' . ($key + 1));
                        }
                        if (isset($branch->branchname) && $branch->status == 0) {
                            array_push($message, 'Branch is closed for Routing No at line no ' . ($key + 1));
                        }
                        if (substr($routingNo, 0, 3) == '135') {
                            array_push($message, 'Janata Bank PLC. Routing No found at line no ' . ($key + 1));
                        }
                    }

                    if ($amountVal != "" && $fileType == 'RTGS') {
                        if (number_format((float)$amountVal, 2, '.', '') < 100000) {
                            array_push($message, 'Invalid Amount found at line no ' . ($key + 1));
                        } else {
                            $totaldebit = bcadd($totaldebit, number_format((float)$amountVal, 2, '.', ''), 2);
                        }
                    }
                }
            }

            if (count($message) > 0) {
                throw ValidationException::withMessages(['file' => $message]);
            }

            try {
                $bkashBatch = new BkashTransactionBatch;
                $bkashBatch->file_name = $filename;
                $bkashBatch->transaction_type = $fileType;
                $bkashBatch->status_id = 1001;
                $bkashBatch->total_data = count($importData_arr);
                $bkashBatch->create_date = Carbon::now()->toDateTimeString();
                $bkashBatch->created_by = 'SYSTEM';
                $bkashBatch->save();

                $batchId = $bkashBatch->id;

                if ($fileType == 'JANATA') {
                    foreach ($importData_arr as $key => $importData) {
                        if ($key == 0) {
                            continue;
                        } else {
                            $bkash = new BkashTransaction;
                            $bkash->batch_id = $batchId;
                            $bkash->reason = $importData[5] ?? null;
                            $bkash->status_id = 1001;
                            $bkash->create_date = Carbon::now()->toDateTimeString();
                            $bkash->credit_account_title = $importData[1] ?? null;
                            $bkash->credit_account_no = $importData[2] ?? null;
                            $bkash->amount = (float)($importData[3] ?? 0);
                            $bkash->debit_account_no = $importData[4] ?? null;
                            $bkash->reference_id = $importData[0] ?? null;
                            $bkash->created_by = 'SYSTEM';
                            $bkash->save();
                        }
                    }
                } else if ($fileType == 'BEFTN') {
                    foreach ($importData_arr as $key => $importData) {
                        if ($key == 0) {
                            continue;
                        } else {
                            $bkash = new BkashTransaction;
                            $bkash->batch_id = $batchId;
                            $bkash->status_id = 1001;
                            $bkash->create_date = Carbon::now()->toDateTimeString();
                            $bkash->debit_account_title = $importData[1] ?? null;
                            $bkash->credit_account_no = $importData[2] ?? null;
                            $bkash->amount = (float)($importData[3] ?? 0);
                            $bkash->debit_account_no = $importData[7] ?? null;
                            $bkash->reference_id = $importData[0] ?? null;
                            $bkash->credit_routing = $importData[4] ?? null;
                            $bkash->credit_bank = $importData[5] ?? null;
                            $bkash->credit_account_title = $importData[1] ?? null;
                            $bkash->created_by = 'SYSTEM';
                            $bkash->save();
                        }
                    }
                } else if ($fileType == 'RTGS') {
                    foreach ($importData_arr as $key => $importData) {
                        if ($key == 0) {
                            continue;
                        } else {
                            $bkash = new BkashTransaction;
                            $bkash->batch_id = $batchId;
                            $bkash->status_id = 1001;
                            $bkash->create_date = Carbon::now()->toDateTimeString();
                            $bkash->reference_id = $importData[8] ?? null;
                            $bkash->credit_account_title = $importData[2] ?? null;
                            $bkash->credit_account_no = $importData[3] ?? null;
                            $bkash->credit_bank = substr($importData[5], 0, 3);
                            $bkash->credit_routing = $importData[5] ?? null;
                            $bkash->amount = (float)($importData[6] ?? 0);
                            $bkash->debit_account_no = $importData[7] ?? null;
                            $bkash->reason = $importData[1] ?? null;
                            $bkash->created_by = 'SYSTEM';
                            $bkash->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                throw ValidationException::withMessages(['file' => 'Error! Please Try Again!']);
            }
        }
    }

    public function checkUploadedFileProperties($extension, $fileSize)
    {
        $valid_extension = array("csv", "xls", "xlsx");
        $maxFileSize = 102097152;

        if (in_array(strtolower($extension), $valid_extension)) {
            if ($fileSize > $maxFileSize) {
                throw new \Exception('File size too large.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }
        } else {
            throw new \Exception('Invalid file extension, only csv, xls or xlsx file allowed.', Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }
    }
}