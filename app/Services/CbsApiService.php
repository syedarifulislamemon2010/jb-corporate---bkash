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
                ->retry($this->retryAttempts, 1000, throw: false)
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
    public function settleTransaction($txn): array
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

        return $this->postBatchTransaction($txn, $token);
    }

    /**
     * Post A2A (type 1), BEFTN (type 2), or RTGS (type 3) transaction to /api/bkash-transactions
     */
    protected function postBatchTransaction(BkashTransaction $txn, string $token): array
    {
        $endpoint = $this->baseUrl . config('bkash.cbs_api.endpoints.transactions', '/api/bkash-transactions');

        // Channel code: 1 for A2A, 2 for BEFTN, 3 for RTGS
        $channelType = strtoupper((string) $txn->transaction_type);
        $channelCode = match ($channelType) {
            'A2A'   => 1,
            'BEFTN' => 2,
            'RTGS'  => 3,
            default => 1,
        };

        $debitAcc = $txn->credit_account_no ?: '0100202707747'; // Default to bKash TCSA
        $creditAcc = $txn->debit_account_no;
        $creditTitle = $txn->debit_account_title ?: 'bKash Beneficiary';
        $routingNo = $txn->credit_routing ?: ($txn->debit_routing ?: '315260856');

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
            Log::info("CBS API: Posting {$txn->transaction_type} Txn {$txn->reference_id} [Type {$channelCode}] to {$endpoint}");

            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->retry($this->retryAttempts, 1000, throw: false)
                ->post($endpoint, $payload);

            $isSuccess = $response->successful();
            $data = $response->json() ?? $response->body();

            if ($isSuccess) {
                Log::info("CBS API: Successfully settled Txn {$txn->reference_id} [Status {$response->status()}]");

                return [
                    'success'       => true,
                    'status_code'   => $response->status(),
                    'response'      => $data,
                    'failure_code'  => null,
                    'reject_reason' => null,
                    'message'       => 'Transaction posted successfully to CBS.',
                ];
            }

            // Extract error message and error code from CBS response
            $responseArray = is_array($data) ? $data : [];
            $rawMessage = $responseArray['message'] ?? $responseArray['error'] ?? $responseArray['error_message'] ?? (is_string($data) ? $data : 'CBS rejected transaction.');
            $rawCode    = $responseArray['error_code'] ?? $responseArray['code'] ?? null;

            // Normalize failure_code (Requirement: Detect dormant accounts and map appropriately)
            $upperMessage = strtoupper((string) $rawMessage);
            $upperCode    = strtoupper((string) $rawCode);

            if (str_contains($upperMessage, 'DORMANT') || str_contains($upperCode, 'DORMANT') || str_contains($upperMessage, 'INACTIVE') || str_contains($upperMessage, 'BLOCKED')) {
                $failureCode = 'DORMANT_ACCOUNT';
            } elseif (str_contains($upperMessage, 'ROUTING') || str_contains($upperCode, 'ROUTING')) {
                $failureCode = 'INVALID_ROUTING';
            } elseif (str_contains($upperMessage, 'ACCOUNT') || str_contains($upperCode, 'ACCOUNT')) {
                $failureCode = 'INVALID_ACCOUNT_NO';
            } else {
                $failureCode = $rawCode ? strtoupper(str_replace(' ', '_', (string) $rawCode)) : 'CBS_REJECTED';
            }

            Log::warning("CBS API: Posting rejected for Txn {$txn->reference_id} [Status {$response->status()}, Code {$failureCode}]: {$rawMessage}");

            return [
                'success'       => false,
                'status_code'   => $response->status(),
                'response'      => $data,
                'failure_code'  => $failureCode,
                'reject_reason' => (string) $rawMessage,
                'message'       => (string) $rawMessage,
            ];
        } catch (\Throwable $e) {
            Log::error("CBS API: Exception posting Txn {$txn->reference_id}: " . $e->getMessage());

            if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response) {
                $data = $e->response->json() ?? $e->response->body();
                $responseArray = is_array($data) ? $data : [];
                $rawMessage = $responseArray['message'] ?? $responseArray['error'] ?? $e->getMessage();
                $upperMessage = strtoupper((string) $rawMessage);
                $failureCode = str_contains($upperMessage, 'DORMANT') ? 'DORMANT_ACCOUNT' : 'CBS_REJECTED';

                return [
                    'success'       => false,
                    'status_code'   => $e->response->status(),
                    'response'      => $data,
                    'failure_code'  => $failureCode,
                    'reject_reason' => (string) $rawMessage,
                    'message'       => (string) $rawMessage,
                ];
            }

            return [
                'success'       => false,
                'status_code'   => 500,
                'response'      => null,
                'failure_code'  => 'NETWORK_ERROR',
                'reject_reason' => 'API Network Error: ' . $e->getMessage(),
                'message'       => 'API Network Error: ' . $e->getMessage(),
            ];
        }
    }
}