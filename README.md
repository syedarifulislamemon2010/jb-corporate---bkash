# Janata Bank Corporate Payment Portal
## bKash Automated Payment Settlement System

An enterprise-grade, high-security automated payment settlement portal built for **Janata Bank PLC.** to ingest, verify, dual-authorize, and automatically settle bKash payment instruction files across **Account to Account (A2A)**, **BEFTN**, and **RTGS** channels.

---

## 📌 1. Core Requirements & Functional Journey

### A. Automated File Ingestion & Validation
- **3-Folder SFTP Structure**: bKash system pushes Excel files (`.xls` from Multi-Bank Tool & `.xlsx` from Oracle ERP) to Janata Bank SFTP location containing 3 separate directories:
  - `/Account-to-Account`
  - `/BEFTN`
  - `/RTGS`
- **15-Minute Polling (`sftp:fetch-bkash-files`)**: Automated 15-minute background worker poller fetches, validates, and ingests files into the portal 7 days a week.
- **Strict Filename Formats**:
  - A2A: `JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
  - BEFTN: `BEFTN_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
  - RTGS: `RTGS_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
- **Validation Engine Rules**:
  1. **File Uniqueness**: Enforces SHA256 integrity hash & file name uniqueness to reject duplicate files.
  2. **Global Duplicate Txn ID**: Cross-verifies `txn_id` against the global ledger while allowing in-file duplicate accounts.
  3. **Single Debit Account Rule**: Every transaction in a single file must share the exact same `credit_account_no`, which must match either bKash TCSA (`0100202707747`) or Operational Account (`0100224107522`).
  4. **RTGS Minimum Threshold**: Enforces `amount >= 100,000 BDT` validation for RTGS transactions.
  5. **Partial Processing Protocol**: Isolates dormant or invalid account rows directly into `bkash_failed_transactions` with detailed `reject_reason` descriptions, while processing all valid transactions instantly.

### B. Maker-Checker-Dual Authorizer Workflow
- **bKash Checker**: Logs into portal via PC, laptop, or mobile to view files under separate dynamic tabs, downloads Excel files to cross-check against email files, and approves.
- **1st Authorizer**: Logs in and approves checked transactions (`status_id = 1002`).
- **2nd Authorizer**: Logs in and provides final approval (`status_id = 1003` -> `1004` CBS Settled).
- **Instant Automated Settlement**: Upon 2nd approval, funds are instantly debited from bKash's **Trust Cum Settlement Account (`0100202707747`)** or **Operational Account (`0100224107522`)** and credited to beneficiary accounts line item by line item 7 days a week.

### C. Multi-Channel Database & SMS/Email Notifications
- **Organization-Scoped Database Notifications**: Using Filament Database Notifications (`databaseNotifications()`), real-time bell alerts are dispatched to all users in the maker's organization (excluding the maker).
- **4-Stage SMS & Email Journey Notifications**: Alerts sent to Checkers and Authorizers at 4 exact stages with **BDT Lakh/Crore comma-formatted totals** (e.g., `1,56,100.82` format):
  1. **Stage 1 (SFTP -> Portal)**: Sent to all bKash Checkers (*"File is pending for Checker"*).
  2. **Stage 2 (Checked by Checker)**: Sent to all Checkers & Authorizers (*"is checked by [Checker Name] & is pending for further Authorization"*).
  3. **Stage 3 (1st Authorizer Approved)**: Sent to all Checkers & Authorizers (*"is Authorized by [1st Authorizer Name] & is pending for final authorization"*).
  4. **Stage 4 (2nd Authorizer Approved)**: Sent to all Checkers & Authorizers (*"is Authorized by [2nd Authorizer Name] & is finally authorized"*).

---

## 🛠️ 2. Database Schema & Field Mapping (Strict Compliance)

| Field Label | Database Column (`snake_case`) | Data Type | Notes |
| :--- | :--- | :--- | :--- |
| **Ref / Ref No** | `reference_id` | `VARCHAR(255)` | Transmitted to Bangladesh Bank for BEFTN/RTGS |
| **Date / Execution Date** | `create_date` | `TIMESTAMP` | Transaction execution date |
| **Return Date** | `return_date` | `TIMESTAMP` | Nullable return timestamp |
| **Bank Account Name / Bene. Name** | `debit_account_title` | `VARCHAR(150)` | Beneficiary account title |
| **Bank Account No / Beneficiary A/C No** | `debit_account_no` | `VARCHAR(100)` | Beneficiary account number |
| **Amount / Amount(BDT)** | `amount` | `DECIMAL(18,2)` | BDT monetary precision |
| **Routing Code / Bene. Routing No** | `debit_routing` | `VARCHAR(20)` | 9-digit routing number |
| **Bank Name / Bene. Bank Name** | `credit_routing` | `VARCHAR(100)` | Beneficiary bank name |
| **Branch Name / Bene. Branch Name** | `credit_bank` | `VARCHAR(255)` | Beneficiary branch name |
| **Debit Account** | `credit_account_no` | `VARCHAR(100)` | Originator bKash TCSA / Ops account |
| **Txn ID** | `txn_id` | `VARCHAR(100)` | Unique global transaction identifier |
| **Reject Reason** | `reject_reason` | `TEXT` | Failure cause description |
| **Status ID** | `status_id` | `INTEGER` | Lifecycle state tracking |
| **Audit Logs** | `created_by`, `approved_by`, `confirmed_by`, `admin_approved_by` | `VARCHAR(255)` | User action audit logs |
| **Timestamps** | `approved_at`, `confirmed_at`, `admin_approved_at`, `cbs_success_at` | `TIMESTAMP` | Audit timestamps |

---

## 🗺️ 3. Portal Navigation & UX Architecture

```
├── Dashboard (Live TCSA 0100202707747 & Operational 0100224107522 Account Balance Widgets)
├── Transaction Pipeline
│   ├── Create Transactions
│   │   ├── Dynamic Tabs: All Transmissions | Account to Account (A2A) - Janata Bank PLC. | BEFTN | RTGS
│   │   └── Manual Creation & Bulk Excel Upload
│   ├── Transaction Confirmation (Checker Verification Panel)
│   └── Transaction Authorization (1st & 2nd Authorizer Approval Panel)
├── Audits & Reports
│   ├── Transaction Process & EFT Reports (Daily, Weekly, Monthly, Yearly Downloads)
│   └── Failed Transaction Report (Partial Processing Errors & Dormant Accounts)
└── Administration
    ├── Organizations (bKash Corporate Profile & Account Tags)
    └── Users (RBAC for Checkers & Authorizers)
```

---

## 🚀 4. Local Setup & Command Execution

```bash
# 1. Run Database Migrations (including notifications table)
php artisan migrate

# 2. Clear & Optimize Application Caches
php artisan optimize:clear

# 3. Test SFTP File Ingestion Scanner Command
php artisan sftp:fetch-bkash-files

# 4. Run Development Server
composer run dev
```

Visit the portal locally at:
👉 **`http://127.0.0.1:8000/admin`** or **`http://127.0.0.1:8001/admin`**
