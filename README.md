# Janata Bank Corporate Payment Portal
## Enterprise bKash Automated Host-to-Host (H2H) Payment Settlement System

An enterprise-grade automated payment settlement portal engineered for **Janata Bank PLC.** to ingest, validate, dual-authorize, and execute instant Host-to-Host (H2H) settlements for **bKash Limited** across **Account to Account (A2A)**, **BEFTN**, and **RTGS** channels.

---

## 📌 1. Executive Summary & Core Requirements Matrix

| Requirement Dimension | Business Specification | Architectural Implementation |
| :--- | :--- | :--- |
| **Phase-by-Phase H2H Rollout** | Phase 1: A2A (7 days/week) $\rightarrow$ Phase 2: BEFTN $\rightarrow$ Phase 3: RTGS | Configurable channel pipelines with dynamic tabs and dedicated validation rules |
| **SFTP 15-Min File Ingestion** | Fetch instruction files every 15 minutes, 7 days a week from designated source folders | Automated `sftp:fetch-bkash-files` cron job scanning `/Account-to-Account`, `/BEFTN`, `/RTGS` |
| **Dual Source File Formats** | Multi-Bank Tool (`.xls`) and Oracle ERP (`.xlsx`) | Cell-by-cell string preservation via `PhpSpreadsheet` & `IOFactory` parser |
| **Value Date & Holiday Processing** | Accurate calendar/value date visibility for post-banking hours & holiday runs | Database `value_date` column tracking execution vs settlement dates |
| **MT940 SWIFT Statements** | SWIFT MT940 statement delivery to SFTP for TCSA & Ops accounts | `Mt940GeneratorService` generating `:20:`, `:25:`, `:28C:`, `:60F:`, `:61:`, `:86:`, `:62F:` formatted `.sta` files |
| **Dual Signatory Workflow** | bKash Checker $\rightarrow$ 1st Authorizer $\rightarrow$ 2nd Authorizer (Final CBS Settlement) | Role-based status lifecycle (`1000` Pending $\rightarrow$ `1001` Checked $\rightarrow$ `1002` Auth 1 $\rightarrow$ `1004` CBS Settled) |
| **CBS / BACH API Integration** | Real-time Host-to-Host settlement via REST APIs (BEFTN, RTGS, A2A) | `CbsApiService` with automated JWT Bearer token management and `ExecuteCbsSettlementJob` |
| **Multi-Stage SMS & Email Alerts** | Broadcast exact SMS & Email templates across 4 journey stages | `NotificationService` with BDT Lakh/Crore comma-formatted amounts and counts |
| **Line-Item Bank Statement** | Single debit line items per transaction (No bulk/consolidated debits) | Individual CBS host-to-host debit-credit posting entries per row |
| **Central Bank Routing** | Share `reference_id` instead of `txn_id` with Bangladesh Bank for BEFTN/RTGS | Gateway payload transformer swapping transaction keys for external clearing |

---

## 🔌 2. CBS / BEFTN / RTGS / A2A API Architecture

The portal features an automated **`CbsApiService`** client that interfaces with the Bank's Host-to-Host clearing server:

### Architectural Workflow

```mermaid
flowchart TD
    subgraph UI ["1. Portal Frontend (Filament Admin)"]
        A["2nd Authorizer Reviews Batch"] --> B["Click 'Final Authorize Selected (Instantly Settle)'"]
    end

    subgraph Controller ["2. Controller & Table Action"]
        B --> C["BkashTransactionConfirmationsTable.php"]
        C --> D["Update Status: 1003 (Final Authorized)"]
        C --> E["NotificationService: Stage 4 Alert"]
        C --> F["ExecuteCbsSettlementJob::dispatchSync()"]
    end

    subgraph Service ["3. CBS Service Layer (CbsApiService.php)"]
        F --> G["Check / Refresh JWT Bearer Token in Cache"]
        G -->|Token Expired / Missing| H["POST /api/login"]
        H -->|Bearer Token Received| I["Cache Token for 50 Minutes"]
        G -->|Valid Token Found| I
        I --> J{"Identify Channel"}
        J -->|BEFTN or RTGS| K["Map Payload for /api/bkash-transactions"]
        J -->|A2A / Probashi| L["Map Payload for /api/probashi-card-info"]
    end

    subgraph BankServer ["4. Janata Bank CBS Clearing Server"]
        K -->|HTTP POST + Bearer Token| M["BEFTN/RTGS Core Gateway"]
        L -->|HTTP POST + Bearer Token| N["A2A Probashi Card Gateway"]
        M & N -->|HTTP 200 OK + responseId| O["Transaction Generated Successfully"]
    end

    subgraph Database ["5. Ledger & Audit Persistence"]
        O --> P["Update bkash_transactions -> Status 1004 (CBS / BACH Settled)"]
        O --> Q["Record responseId & payload in posting_attempts table"]
        P --> R["Real-time Reflection on Dashboard & Reports Table"]
    end
```

---

### Field Mapping Matrix

| Postman Field | Database Column | Service Mapping (`CbsApiService.php`) | Description |
| :--- | :--- | :--- | :--- |
| `uniqueId` | `txn_id` / `reference_id` | `(string) ($txn->txn_id ?: $txn->reference_id)` | Unique global transaction identifier |
| `debitAccount` | `credit_account_no` | `(string) $txn->credit_account_no` | bKash TCSA / Ops Account |
| `creditAccount` | `debit_account_no` | `(string) $txn->debit_account_no` | Beneficiary bank account number |
| `creditAccountTitle` | `debit_account_title` | `(string) $txn->debit_account_title` | Beneficiary account title |
| `creditRoutingNo` | `debit_routing` | `(string) ($txn->debit_routing ?: $txn->credit_routing)` | Beneficiary bank 9-digit routing number |
| `amount` | `amount` | `(float) $txn->amount` | Monetary value in BDT |
| `remarks` | — | `"bKash {$txn->transaction_type} Settlement - Ref: {$txn->reference_id}"` | Transaction narrative description |
| `type` | `transaction_type` | `2` for BEFTN, `3` for RTGS | API channel routing code |

---

### API Endpoints Reference

#### 1. Authentication (`POST /api/login`)
- **Endpoint**: `{BASE_URL}/api/login`
- **Request Body**:
  ```json
  {
      "username": "<API_USERNAME>",
      "password": "<API_PASSWORD>"
  }
  ```
- **Response**: Returns JWT Bearer token cached in cache store for 50 minutes.

#### 2. BEFTN & RTGS Settlement (`POST /api/bkash-transactions`)
- **Endpoint**: `{BASE_URL}/api/bkash-transactions`
- **Headers**: `Authorization: Bearer {token}`
- **Request Payload**:
  ```json
  {
      "uniqueId": "BKS2026XXXXXXXX",
      "debitAccount": "0100XXXXXXXXX",
      "creditAccount": "4512XXXXXXXXX",
      "creditAccountTitle": "BENEFICIARY_ACCOUNT_TITLE",
      "creditRoutingNo": "315XXXXXX",
      "amount": 500.00,
      "remarks": "bKash BEFTN Settlement - Ref: RM41107",
      "type": 2
  }
  ```
- **Response Example**:
  ```json
  {
      "responseCode": 200,
      "message": "Transaction Generated Successfully!",
      "uniqueId": "BKS2026XXXXXXXX",
      "responseId": "B135XXXXXXXXXXXX"
  }
  ```

#### 3. A2A / Probashi Card Transfer (`POST /api/probashi-card-info`)
- **Endpoint**: `{BASE_URL}/api/probashi-card-info`
- **Headers**: `Authorization: Bearer {token}`
- **Request Payload**:
  ```json
  {
      "bmet_id": "BMET2026XXXXXXXX",
      "account_no": "0100XXXXXXXXX",
      "card_title": "CARD_HOLDER_NAME",
      "visa_number": "EAXXXXXXXX",
      "visa_issue_date": "YYYY-MM-DD",
      "visa_issue_place": "DHAKA",
      "passport_number": "XXXXXXXXXX",
      "recruiting_licence_no": "XXXXXX",
      "destination_country": "USA",
      "customer_image": "<BASE64_IMAGE>",
      "qr_image": "<BASE64_IMAGE>"
  }
  ```

---

### CLI Connectivity Tester

Run the built-in command to verify API connectivity from the terminal:

```bash
php artisan cbs:test-api
```

*(For login check only: `php artisan cbs:test-api --dry-run`)*

---

## 🛠️ 3. Validation & Business Logic Engine

1. **File Uniqueness**: Enforces SHA256 integrity hash & file name uniqueness matching naming conventions:
   - **A2A**: `JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
   - **BEFTN**: `BEFTN_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
   - **RTGS**: `RTGS_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx` *(where x, y are integer slot numbers)*
2. **Single Debit Account Rule**: Every single transaction inside a file must originate from the exact same debit account (`credit_account_no`), which must strictly belong to either bKash **Trust Cum Settlement Account** or **Operational Account**.
3. **Global `txn_id` Uniqueness**: Cross-checks `txn_id` against the historical ledger while explicitly allowing duplicate accounts inside a single file.
4. **RTGS Threshold Enforcement**: Mandatory rule requiring `amount >= 100,000 BDT` for RTGS transactions.
5. **Partial Processing Protocol**: Erroneous or dormant account rows are automatically isolated into `bkash_failed_transactions` with clear `reject_reason` descriptions, while all valid rows settle instantly without manual bank intervention.

---

## 📐 4. Database Schema & Field Mapping

| Field Label | Database Column (`snake_case`) | Data Type | Functional Description |
| :--- | :--- | :--- | :--- |
| **Ref / Ref No** | `reference_id` | `VARCHAR(255)` | Instruction reference number (Sent to Bangladesh Bank) |
| **Date / Execution Date** | `create_date` | `TIMESTAMP` | File creation / Execution timestamp |
| **Value Date** | `value_date` | `DATE` | Value date for holiday and post-banking hours settlement |
| **Return Date** | `return_date` | `TIMESTAMP` | EFT Return timestamp |
| **Bank Account Name** | `debit_account_title` | `VARCHAR(150)` | Beneficiary account title |
| **Bank Account No** | `debit_account_no` | `VARCHAR(100)` | Beneficiary account number |
| **Amount / Amount(BDT)** | `amount` | `DECIMAL(18,2)` | Monetary value in BDT (2 decimal places) |
| **Routing Number** | `debit_routing` | `VARCHAR(20)` | 9-digit routing code |
| **Bank Name** | `credit_routing` | `VARCHAR(100)` | Beneficiary bank name |
| **Branch Name** | `credit_bank` | `VARCHAR(255)` | Beneficiary branch name |
| **Debit Account** | `credit_account_no` | `VARCHAR(100)` | Sender Account (bKash TCSA / Operational Account) |
| **Txn ID** | `txn_id` | `VARCHAR(100)` | Unique global transaction identifier |
| **Reject Reason** | `reject_reason` | `TEXT` | Failure cause description for partial processing |
| **Status ID** | `status_id` | `INTEGER` | Lifecycle state (`1000` Pending, `1001` Checked, `1002` Auth 1, `1004` Settled) |
| **Audit Logs** | `created_by`, `checked_by`, `approved_by_1`, `approved_by_2` | `VARCHAR(255)` | User action audit tracking |
| **Audit Timestamps** | `checked_at`, `approved_at_1`, `approved_at_2`, `cbs_success_at` | `TIMESTAMP` | State transition audit timestamps |

---

## 📲 5. Multi-Stage Notification Journey (SMS & Email Templates)

All notifications format monetary values with standard comma separation (e.g., `1,56,100.82`):

- **Stage 1 (SFTP File Ingestion Complete):**
  > "Dear Sir/Madam, File Name: “{file_name}” Total Trn: “{total_count}”, Total Amount: “{amount}”. File is pending for Checker. Please Check this file. Thank you. Best Regards, JANATA BANK"
- **Stage 2 (Checked by bKash Checker):**
  > "Dear Sir/Madam, File Name: “{file_name}” Total Trn: “{total_count}”, Total Amount: “{amount}” is checked by “{checker_name}” & is pending for further Authorization/Approval. Thank you. JANATA BANK"
- **Stage 3 (Authorized by 1st Authorizer):**
  > "Dear Sir/Madam, File Name: “{file_name}” Total Trn: “{total_count}”, Total Amount: “{amount}” is Authorized by “{authorizer_1_name}” & is pending for further Authorization/Approval or final authorization. Thank you. JANATA BANK"
- **Stage 4 (Authorized by 2nd Authorizer - Finalized):**
  > "Dear Sir/Madam, File Name: “{file_name}” Total Trn: “{total_count}”, Total Amount: “{amount}” is Authorized by “{authorizer_2_name}” & is finally authorized. Thank you. JANATA BANK"

---

## 🗺️ 6. Portal Navigation & UX Architecture

```
├── Dashboard (Live TCSA & Operational Account Balance Widgets)
├── Transaction Pipeline
│   ├── Create Transactions
│   │   ├── Dynamic Tabs: All Transmissions | Account to Account (A2A) - Janata Bank PLC. | BEFTN | RTGS
│   │   └── Manual Transaction Creation & Bulk Excel File Upload
│   ├── Transaction Confirmation (Checker Verification Queue)
│   └── Transaction Authorization (1st & 2nd Authorizer Approval Queue)
├── Audits & Reports
│   ├── Batch File History (Comprehensive Ingestion Log & Settlement Overview)
│   ├── Transaction Process & EFT Reports (Daily, Weekly, Monthly, Yearly Processed Reports)
│   ├── Failed Transaction Report (Real-time breakdown of partial processing errors & reasons)
│   └── EFT Return Report (Returned transaction audit log)
└── Administration
    ├── Organizations (bKash Corporate Profile & Account Tags)
    ├── Users (RBAC for bKash Checkers & Authorizers)
    └── Roles (Filament Shield Multi-Tier Permission Matrix)
```

---

## 🚀 7. Setup & Execution Commands

```bash
# 1. Run Database Migrations
php artisan migrate

# 2. Clear & Optimize Application Caches
php artisan optimize:clear

# 3. Execute SFTP Automated File Ingestion Cron Job
php artisan sftp:fetch-bkash-files

# 4. Start Local Development Server
composer run dev
```

Visit the portal locally at:
👉 **`http://127.0.0.1:8000/admin`** or **`http://127.0.0.1:8001/admin`**

---

## 🔑 8. Environment Variables Reference

| Variable | Default | Description |
|:---------|:--------|:------------|
| `DB_CONNECTION` | `oracle` | Primary database driver |
| `BKASH_CBS_API_BASE_URL` | — | CBS Host-to-Host API base URL |
| `BKASH_CBS_API_USERNAME` | — | CBS API username |
| `BKASH_CBS_API_PASSWORD` | — | CBS API password |
| `BKASH_SFTP_HOST` | — | SFTP server host/IP |
| `BKASH_SFTP_USERNAME` | — | SFTP login username |
| `BKASH_SFTP_PASSWORD` | — | SFTP login password |
| `BKASH_SFTP_PORT` | `22` | SFTP port |
| `BKASH_EMAIL_ENABLED` | `true` | Enable/disable email notifications |
| `BKASH_SMS_ENABLED` | `false` | Enable/disable SMS notifications |
| `BKASH_WHITELISTED_DEBIT_ACCOUNTS` | — | Allowed debit accounts |
| `BKASH_RTGS_MIN_LIMIT` | `100000` | Minimum RTGS amount (BDT) |
| `BKASH_TCSA_INITIAL_BALANCE` | — | TCSA account opening balance |
| `BKASH_OPS_INITIAL_BALANCE` | — | Ops account opening balance |
