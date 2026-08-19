<?php

namespace App\Services;

use App\Models\BkashTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CbsApiService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected int $retryAttempts;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('bkash.cbs_api.base_url', 'http://172.18.18.64'), '/');
        $this->username = (string) config('bkash.cbs_api.username', 'API');
        $this->password = (string) config('bkash.cbs_api.password', 'Admin@123');
        $this->timeout = (int) config('bkash.cbs_api.timeout', 30);
        $this->retryAttempts = (int) config('bkash.cbs_api.retry_attempts', 3);
    }

    /**
     * Retrieve a valid Bearer token for API calls.
     * Tokens are cached in Cache for 50 minutes to prevent redundant login calls.
     */
    public function getAuthToken(bool $forceRefresh = false): ?string
    {
        $cacheKey = 'cbs_api_bearer_token_' . md5($this->baseUrl . $this->username);

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cachedToken = Cache::get($cacheKey);
            if (!empty($cachedToken)) {
                return $cachedToken;
            }
        }

        try {
            $loginEndpoint = $this->baseUrl . config('bkash.cbs_api.endpoints.login', '/api/login');

            Log::info("CBS API: Requesting authentication token from {$loginEndpoint} [User: {$this->username}]");

            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, 1000)
                ->post($loginEndpoint, [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract token from response (handles 'token', 'access_token', 'data.token', etc.)
                $token = $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? null;

                if (empty($token) && is_string($response->body())) {
                    // Fallback in case raw token string is returned
                    $bodyStr = trim($response->body(), "\" \t\n\r");
                    if (str_starts_with($bodyStr, 'eyJ') || strlen($bodyStr) > 20) {
                        $token = $bodyStr;
                    }
                }

                if (!empty($token)) {
                    // Cache for 50 minutes (3000 seconds)
                    Cache::put($cacheKey, $token, now()->addMinutes(50));
                    Log::info("CBS API: Successfully obtained and cached Bearer token.");
                    return $token;
                }

                Log::error("CBS API: Login response succeeded but no token found in payload: " . $response->body());
            } else {
                Log::error("CBS API: Login failed with status {$response->status()}: " . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("CBS API: Exception during authentication: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Settle an individual transaction via the Host-to-Host CBS API.
     *
     * @return array ['success' => bool, 'status_code' => int, 'response' => array|string, 'message' => string]
     */
    public function settleTransaction(BkashTransaction $txn): array
    {
        $token = $this->getAuthToken();

        if (empty($token)) {
            // Attempt one force refresh if cached token failed
            $token = $this->getAuthToken(forceRefresh: true);
        }

        if (empty($token)) {
            return [
                'success'     => false,
                'status_code' => 401,
                'response'    => null,
                'message'     => 'Unable to authenticate with CBS API server (Failed to acquire Bearer token).',
            ];
        }

        $channel = strtoupper((string) $txn->transaction_type);

        // Select endpoint based on channel type
        if ($channel === 'A2A') {
            return $this->postA2aTransaction($txn, $token);
        }

        return $this->postBatchTransaction($txn, $token);
    }

    /**
     * Post BEFTN or RTGS transaction to /api/bkash-transactions
     */
    protected function postBatchTransaction(BkashTransaction $txn, string $token): array
    {
        $endpoint = $this->baseUrl . config('bkash.cbs_api.endpoints.transactions', '/api/bkash-transactions');

        // Channel code: 2 for BEFTN, 3 for RTGS
        $channelCode = strtoupper((string)$txn->transaction_type) === 'RTGS' ? 3 : 2;

        $debitAcc = $txn->credit_account_no ?: '0100202707747'; // Default to bKash TCSA
        $creditAcc = $txn->debit_account_no;
        $creditTitle = $txn->debit_account_title ?: 'bKash Beneficiary';
        $routingNo = $txn->debit_routing ?: ($txn->credit_routing ?: '315260856');

        $payload = [
            'uniqueId'           => (string) ($txn->txn_id ?: $txn->reference_id),
            'debitAccount'       => (string) $debitAcc,
            'creditAccount'      => (string) $creditAcc,
            'creditAccountTitle' => (string) $creditTitle,
            'creditRoutingNo'    => (string) $routingNo,
            'amount'             => (float) $txn->amount,
            'remarks'            => "bKash {$txn->transaction_type} Settlement - Ref: {$txn->reference_id}",
            'type'               => $channelCode,
        ];

        try {
            Log::info("CBS API: Posting {$txn->transaction_type} Txn {$txn->reference_id} to {$endpoint}");

            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, 1000)
                ->post($endpoint, $payload);

            $isSuccess = $response->successful();
            $data = $response->json() ?? $response->body();

            if ($isSuccess) {
                Log::info("CBS API: Successfully settled Txn {$txn->reference_id} [Status {$response->status()}]");
            } else {
                Log::warning("CBS API: Posting rejected for Txn {$txn->reference_id} [Status {$response->status()}]: " . $response->body());
            }

            return [
                'success'     => $isSuccess,
                'status_code' => $response->status(),
                'response'    => $data,
                'message'     => $isSuccess ? 'Transaction posted successfully to CBS.' : ($response->json('message') ?? 'CBS rejected transaction.'),
            ];
        } catch (\Throwable $e) {
            Log::error("CBS API: Network exception posting Txn {$txn->reference_id}: " . $e->getMessage());

            return [
                'success'     => false,
                'status_code' => 500,
                'response'    => null,
                'message'     => 'API Network Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Post A2A / Probashi Card transaction to /api/probashi-card-info
     */
    protected function postA2aTransaction(BkashTransaction $txn, string $token): array
    {
        $endpoint = $this->baseUrl . config('bkash.cbs_api.endpoints.a2a_probashi', '/api/probashi-card-info');

        $payload = [
            'bmet_id'               => (string) ($txn->reference_id ?: 'BMET_' . $txn->id),
            'account_no'            => (string) ($txn->debit_account_no ?: $txn->credit_account_no),
            'card_title'            => (string) ($txn->debit_account_title ?: 'G S KIBRIA'),
            'visa_number'           => (string) ($txn->visa_number ?? 'EA1204512'),
            'visa_issue_date'       => (string) ($txn->visa_issue_date ?? now()->format('Y-m-d')),
            'visa_issue_place'      => (string) ($txn->visa_issue_place ?? 'DHAKA'),
            'passport_number'       => (string) ($txn->passport_number ?? '5214512344'),
            'recruiting_licence_no' => (string) ($txn->recruiting_licence_no ?? '112233'),
            'destination_country'   => (string) ($txn->destination_country ?? 'USA'),
            'customer_image'        => (string) ($txn->customer_image ?? ''),
            'qr_image'              => (string) ($txn->qr_image ?? ''),
        ];

        try {
            Log::info("CBS API: Posting A2A Txn {$txn->reference_id} to {$endpoint}");

            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, 1000)
                ->post($endpoint, $payload);

            $isSuccess = $response->successful();
            $data = $response->json() ?? $response->body();

            return [
                'success'     => $isSuccess,
                'status_code' => $response->status(),
                'response'    => $data,
                'message'     => $isSuccess ? 'A2A transaction posted successfully.' : ($response->json('message') ?? 'A2A API rejected transaction.'),
            ];
        } catch (\Throwable $e) {
            Log::error("CBS API: Network exception posting A2A Txn {$txn->reference_id}: " . $e->getMessage());

            return [
                'success'     => false,
                'status_code' => 500,
                'response'    => null,
                'message'     => 'API Network Error: ' . $e->getMessage(),
            ];
        }
    }
}
