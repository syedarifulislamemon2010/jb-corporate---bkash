<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CbsResponseCallbackRequest;
use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CbsResponseCallbackController extends Controller
{
    /**
     * Handle asynchronous CBS settlement response callback.
     */
    public function store(CbsResponseCallbackRequest $request): JsonResponse
    {
        $responseId  = $request->input('response_id');
        $statusId    = (int) $request->input('status_id');
        $referenceId = $request->input('reference_id');
        $txnId       = $request->input('txn_id');
        $reason      = $request->input('reason');
        $confirmedBy = $request->input('confirmed_by') ?: 'CBS_CALLBACK';

        // 1. Locate Transaction by response_id, txn_id, or reference_id
        $query = BkashTransaction::query();

        if (!empty($responseId)) {
            $txn = (clone $query)->where('response_id', $responseId)->first();
        }

        if (empty($txn) && !empty($txnId)) {
            $txn = (clone $query)->where('txn_id', $txnId)->first();
        }

        if (empty($txn) && !empty($referenceId)) {
            $txn = (clone $query)->where('reference_id', $referenceId)->first();
        }

        if (!$txn) {
            Log::warning("CBS Callback: Transaction not found [Response ID: {$responseId}, Txn ID: {$txnId}, Ref: {$referenceId}]");

            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // 2. Perform updates based on CBS asynchronous callback status
        $updateData = [
            'response_id'  => $responseId,
            'status_id'    => $statusId,
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => Carbon::now(),
        ];

        if ($statusId === BkashTransaction::STATUS_CBS_RESPONSE_SUCCESS) {
            if (empty($txn->cbs_success_at)) {
                $updateData['cbs_success_at'] = Carbon::now();
            }
            Log::info("CBS Callback: Transaction {$txn->reference_id} marked as SUCCESS [Status 1006, Response ID: {$responseId}]");
        } elseif ($statusId === BkashTransaction::STATUS_CBS_RESPONSE_FAILED) {
            $rejectReason = $reason ?: 'CBS reported settlement failure via callback.';
            $updateData['reject_reason'] = $rejectReason;

            Log::warning("CBS Callback: Transaction {$txn->reference_id} marked as FAILED [Status 1007, Response ID: {$responseId}]: {$rejectReason}");

            try {
                Log::channel('critical_financial')->critical("CBS Callback: Transaction {$txn->reference_id} marked as FAILED [Status 1007, Response ID: {$responseId}]: {$rejectReason}");
            } catch (\Throwable $ignored) {
            }
        }

        DB::transaction(function () use ($txn, $updateData, $statusId, $reason) {
            if ($statusId === BkashTransaction::STATUS_CBS_RESPONSE_FAILED) {
                $rejectReason = $reason ?: 'CBS reported settlement failure via callback.';

                // Record in failed transaction report if not already recorded
                BkashFailedTransaction::firstOrCreate(
                    [
                        'reference_id' => $txn->reference_id ?? 'N/A',
                        'batch_id'     => $txn->batch_id,
                    ],
                    [
                        'file_name'         => $txn->file_name ?? 'bKash_File.xlsx',
                        'row_number'        => ($txn->row_sequence !== null ? $txn->row_sequence + 1 : 1),
                        'transaction_type'  => $txn->transaction_type,
                        'debit_account_no'  => $txn->credit_account_no,
                        'credit_account_no' => $txn->debit_account_no,
                        'amount'            => $txn->amount,
                        'failure_code'      => 'CBS_CALLBACK_REJECTED',
                        'reject_reason'     => $rejectReason,
                    ]
                );
            }

            $txn->update($updateData);
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Transaction status updated',
            'id'        => $txn->id,
            'status_id' => $txn->status_id,
        ], 200);
    }
}
