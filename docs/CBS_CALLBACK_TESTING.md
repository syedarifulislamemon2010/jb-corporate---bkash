# Janata Bank — CBS Settlement Response Callback Testing Guide

This guide details how to test the Janata Bank Core Banking System (CBS) / BACH asynchronous settlement callback system.

---

> [!IMPORTANT]
> **Production vs. Dev/Staging Authentication Mechanism**
>
> ⚠️ **এই token-based টেস্ট এন্ডপয়েন্ট শুধুমাত্র dev/staging পরিবেশের জন্য এবং প্রোডাকশনে ডিজেবল থাকে। JANATA BANK production এ ঠিক আগের মতোই `/api/cbs/response-callback` এন্ডপয়েন্টে `X-CBS-API-Key` header দিয়ে callback পাঠাবে — production authentication mechanism সম্পূর্ণ অপরিবর্তিত ও সুরক্ষিত।**

---

## 🏛️ Part 1: Production CBS Callback (`X-CBS-API-Key` Header)

### 1.1 Architecture & Security Mechanism
- **Route**: `POST /api/cbs/response-callback`
- **Authentication**: `X-CBS-API-Key` HTTP Header (Machine-to-Machine Shared Secret)
- **Middleware**: `App\Http\Middleware\AuthenticateCbsCallback`
- **Controller**: `App\Http\Controllers\Api\CbsResponseCallbackController`

Bank host-to-host callbacks are machine-to-machine integrations. Basic user authentication (username/password) is not applicable because the caller is an automated CBS backend engine. The `X-CBS-API-Key` header with constant-time string comparison (`hash_equals`) is the standard secure banking mechanism.

### 1.2 Environment Configuration
Check or configure `CBS_CALLBACK_API_KEY` in `.env`:
```dotenv
CBS_CALLBACK_API_KEY=cbs-secret-callback-key-2026
```

### 1.3 Seed Test Data
```bash
php artisan test:seed-cbs-callback-data
```

### 1.4 Production Callback cURL Examples

#### A. Settlement SUCCESS Callback (Status `1006`):
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

#### B. Settlement REJECTION / FAILURE Callback (Status `1007`):
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

#### C. Unauthorized Request (401 Response):
```bash
curl -X POST http://localhost:8000/api/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "X-CBS-API-Key: wrong-invalid-key" \
  -d '{"response_id": "CBS_123", "status_id": 1006, "txn_id": "TEST_TXN_CBS_001"}'
```

---

## 🧪 Part 2: Dev/Staging Token-Based Testing (Laravel Sanctum)

For internal team development and QA testing without needing the production `X-CBS-API-Key`, a token-issuing test flow is available strictly in non-production environments.

### 2.1 Test Endpoints Overview
| Endpoint | Method | Auth Guard | Description |
| :--- | :--- | :--- | :--- |
| `/api/test-auth/token` | `POST` | Public (Dev/Staging only) | Issues a Bearer token for test users |
| `/api/test-auth/cbs/response-callback` | `POST` | `auth:sanctum` | Reuses core CBS callback logic via Bearer token |

### 2.2 Step-by-Step Manual Token Testing

#### Step 1: Seed Test Users & Transaction
Run the following artisan command:
```bash
php artisan test:seed-users --with-transaction
```
This creates:
- **Checker User**: `checker@test.jbcorporate.com` / `Test@Pass123`
- **1st Authorizer**: `authorizer1@test.jbcorporate.com` / `Test@Pass123`
- **2nd Authorizer**: `authorizer2@test.jbcorporate.com` / `Test@Pass123`
- **Test Transaction**: `TEST_TXN_CBS_001` (Status `1003` - `STATUS_FINAL_AUTHORIZED`)

#### Step 2: Request Bearer Token
```bash
curl -X POST http://localhost:8000/api/test-auth/token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "checker@test.jbcorporate.com",
    "password": "Test@Pass123"
  }'
```
**Response:**
```json
{
  "success": true,
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "bKash Checker Test User",
    "email": "checker@test.jbcorporate.com",
    "organization": "Janata Bank PLC.",
    "role": "bkash_checker"
  }
}
```

#### Step 3: Send CBS Callback using Token
```bash
curl -X POST http://localhost:8000/api/test-auth/cbs/response-callback \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|abcdef123456..." \
  -d '{
    "response_id": "TEST_CBS_RESP_001",
    "status_id": 1006,
    "txn_id": "TEST_TXN_CBS_001",
    "confirmed_by": "TEST_VIA_POSTMAN"
  }'
```

---

## 🔍 Part 3: Database Verification via Tinker

After sending callback requests (via API Key or Sanctum Token), verify the database updates:

```bash
php artisan tinker
```

```php
// Check Transaction status and timestamps
$txn = \App\Models\BkashTransaction::where('txn_id', 'TEST_TXN_CBS_001')->first();
$txn->status_id;        // 1006 (STATUS_CBS_RESPONSE_SUCCESS) or 1007 (STATUS_CBS_RESPONSE_FAILED)
$txn->response_id;      // "TEST_CBS_RESP_001"
$txn->confirmed_at;     // Carbon timestamp
$txn->cbs_success_at;   // Carbon timestamp (for 1006)
$txn->reject_reason;    // Reason message (for 1007)

// Check Failed Transactions audit record (when testing 1007)
$failed = \App\Models\BkashFailedTransaction::where('reference_id', 'TEST_REF_CBS_001')->first();
$failed->failure_code;   // 'CBS_CALLBACK_REJECTED'
$failed->reject_reason;  // Reason message
```

---

## 📦 Part 4: Postman Collections

Two ready-to-import Postman collections (v2.1.0) are available in `docs/postman/`:

1. **`docs/postman/CBS_Callback_Collection.json`**
   - Direct production-mirror testing using `X-CBS-API-Key` header.
   - Includes 6 pre-configured requests (Success 1006, Fail 1007, Ref ID lookup, 401 Unauthorized, 404 Not Found, 422 Validation error).

2. **`docs/postman/CBS_Callback_Token_Test_Collection.json`**
   - Dev/Staging token-based testing with automated script that extracts the token from `1. Get Token` and populates `{{auth_token}}` variable for subsequent requests.