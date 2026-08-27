# Janata Bank — CBS Asynchronous Response Callback API Manual Testing Guide

This guide details how to manually test the Janata Bank Core Banking System (CBS) / BACH settlement callback endpoint (`POST /api/cbs/response-callback`) using **cURL**, **Postman**, or **Artisan**.

---

## 1. Architecture & Mechanism

- **Endpoint**: `POST /api/cbs/response-callback`
- **Authentication**: `X-CBS-API-Key` HTTP Header (System-to-System Shared Secret)
- **Middleware**: `App\Http\Middleware\AuthenticateCbsCallback`
- **Controller**: `App\Http\Controllers\Api\CbsResponseCallbackController`

> [!NOTE]
> Bank host-to-host callbacks are machine-to-machine integrations. Basic user authentication (username/password) is not applicable because the caller is an automated CBS backend engine. The `X-CBS-API-Key` header with constant-time string comparison (`hash_equals`) is the standard secure mechanism.

---

## 2. Prerequisites & Environment Setup

Check or set the callback API key in your `.env` file:

```dotenv
CBS_CALLBACK_API_KEY=cbs-secret-callback-key-2026
```

If not explicitly defined in `.env`, the system defaults to:
`cbs-secret-callback-key-2026` (as defined in `config/bkash.php`).

---

## 3. Step 1: Seed Test Users & Transaction Data

Run the dedicated seeding command:

```bash
php artisan test:seed-cbs-callback-data
```

This command will:
1. Ensure the 3 required workflow roles exist (`bkash_checker`, `bkash_authorizer_1`, `bkash_authorizer_2`).
2. Create/update 3 predictable test users:
   - **Checker**: `test.checker@jbcorporate.test` (`01711000001`)
   - **1st Authorizer**: `test.authorizer1@jbcorporate.test` (`01711000002`)
   - **2nd Authorizer**: `test.authorizer2@jbcorporate.test` (`01711000003`)
3. Create/reset a test transaction with `status_id = 1003` (`STATUS_FINAL_AUTHORIZED`):
   - **Txn ID**: `TEST_TXN_CBS_001`
   - **Reference ID**: `TEST_REF_CBS_001`
   - **Amount**: BDT `50,000.00`
   - **Status**: `1003` (Ready to receive CBS Callback)

---

## 4. Step 2: Manual API Testing Scenarios (cURL)

Make sure your local Laravel application server is running:
```bash
php artisan serve
```

### Scenario A: Settlement SUCCESS Callback (Status `1006`)

Simulates CBS successfully debiting the pool account and crediting the beneficiary:

```bash
curl -X POST http://localhost:8000/api/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "X-CBS-API-Key: cbs-secret-callback-key-2026" \
  -d '{
    "response_id": "CBS_RESP_SUCCESS_001",
    "status_id": 1006,
    "txn_id": "TEST_TXN_CBS_001",
    "confirmed_by": "JANATA_CBS_CORE"
  }'
```

**Expected HTTP Response (200 OK):**
```json
{
  "success": true,
  "message": "Transaction status updated",
  "id": 1,
  "status_id": 1006
}
```

---

### Scenario B: Settlement REJECTION / FAILURE Callback (Status `1007`)

Simulates CBS rejecting the transaction (e.g., beneficiary account closed or dormant):

```bash
curl -X POST http://localhost:8000/api/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "X-CBS-API-Key: cbs-secret-callback-key-2026" \
  -d '{
    "response_id": "CBS_RESP_FAIL_001",
    "status_id": 1007,
    "txn_id": "TEST_TXN_CBS_001",
    "reason": "Beneficiary account dormant or closed in core banking system",
    "confirmed_by": "JANATA_CBS_CORE"
  }'
```

**Expected HTTP Response (200 OK):**
```json
{
  "success": true,
  "message": "Transaction status updated",
  "id": 1,
  "status_id": 1007
}
```

---

### Scenario C: Unauthorized Request (Invalid / Missing API Key)

```bash
curl -X POST http://localhost:8000/api/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "X-CBS-API-Key: wrong-invalid-key" \
  -d '{
    "response_id": "CBS_RESP_UNAUTH_001",
    "status_id": 1006,
    "txn_id": "TEST_TXN_CBS_001"
  }'
```

**Expected HTTP Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Unauthorized: Invalid or missing CBS API Key"
}
```

---

### Scenario D: Non-existent Transaction ID (404 Not Found)

```bash
curl -X POST http://localhost:8000/api/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "X-CBS-API-Key: cbs-secret-callback-key-2026" \
  -d '{
    "response_id": "CBS_RESP_NOT_FOUND",
    "status_id": 1006,
    "txn_id": "NON_EXISTENT_TXN_99999"
  }'
```

**Expected HTTP Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Transaction not found"
}
```

---

## 5. Step 3: Database Verification via Tinker

After sending callback requests, verify the changes in database:

```bash
php artisan tinker
```

```php
// Check Transaction status and timestamps
$txn = \App\Models\BkashTransaction::where('txn_id', 'TEST_TXN_CBS_001')->first();
$txn->status_id;        // 1006 (SUCCESS) or 1007 (FAILED)
$txn->response_id;      // "CBS_RESP_SUCCESS_001" or "CBS_RESP_FAIL_001"
$txn->confirmed_at;     // Carbon timestamp
$txn->cbs_success_at;   // Carbon timestamp (for 1006)
$txn->reject_reason;    // Reason text (for 1007)

// Check Failed Transactions audit record (when testing 1007)
$failed = \App\Models\BkashFailedTransaction::where('reference_id', 'TEST_REF_CBS_001')->first();
$failed->failure_code;   // 'CBS_CALLBACK_REJECTED'
$failed->reject_reason;  // Reason message
```

---

## 6. Step 4: Testing with Postman

A ready-to-import Postman Collection is included at:
`docs/postman/CBS_Callback_Collection.json`

### How to import into Postman:
1. Open **Postman**.
2. Click **Import** (top left).
3. Select the file: `docs/postman/CBS_Callback_Collection.json`.
4. The collection **Janata Bank — CBS Response Callback API** will appear with 6 pre-configured requests:
   - `1. CBS Callback — SUCCESS (Status 1006)`
   - `2. CBS Callback — FAILED (Status 1007)`
   - `3. CBS Callback — By Reference ID (Status 1006)`
   - `4. CBS Callback — UNAUTHORIZED (Invalid API Key -> 401)`
   - `5. CBS Callback — NOT FOUND (Non-existent Txn -> 404)`
   - `6. CBS Callback — VALIDATION ERROR (Invalid Status -> 422)`
5. Adjust the collection variable `baseUrl` if your application is running on a different port/host.