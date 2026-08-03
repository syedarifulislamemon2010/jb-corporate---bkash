# Janata Bank Corporate Payment Portal
## bKash Automated Payment Settlement System

An enterprise-grade, high-security automated payment settlement portal built for **Janata Bank PLC.** to ingest, verify, dual-authorize, and automatically settle bKash payment instruction files across **Account to Account (A2A)**, **BEFTN**, and **RTGS** channels.

---

## 📌 1. Core Requirements & Functional Journey

### A. Automated File Ingestion
- **3-Folder SFTP Structure**: bKash system pushes Excel files (`.xls` from Multi-Bank Tool & `.xlsx` from Oracle ERP) to Janata Bank SFTP location containing 3 separate directories:
  - `/Account to-Account`
  - `/BEFTN`
  - `/RTGS`
- **15-Minute Polling**: Automated 15-minute background worker poller fetches, validates, and ingests files into the portal.
- **Filename Regex & Uniqueness**:
  - A2A: `JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
  - BEFTN: `BEFTN_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`
  - RTGS: `RTGS_JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`

### B. Maker-Checker-Dual Authorizer Workflow
- **bKash Checker**: Logs into portal via PC, laptop, or mobile to view files in 3 separate tabs, downloads Excel files to cross-check against email files, and approves.
- **1st Authorizer**: Logs in and approves checked transactions.
- **2nd Authorizer**: Logs in and provides final approval.
- **Instant Automated Settlement**: Upon 2nd approval, funds are instantly debited from bKash's **Trust Cum Settlement Account (`0100202707747`)** or **Operational Account (`0100224107522`)** and credited to beneficiary accounts 7 days a week.

### C. 4-Stage SMS & Email Journey Notifications
SMS & Email notifications are sent to Checkers and Authorizers at 4 exact stages with **BDT Lakh/Crore comma-formatted totals** (e.g. `1,56,100.82` format):
1. **Stage 1 (SFTP -> Portal)**: Sent to all bKash Checkers (*"File is pending for Checker"*).
2. **Stage 2 (Checked by Checker)**: Sent to all Checkers & Authorizers (*"is checked by [Checker Name] & is pending for further Authorization"*).
3. **Stage 3 (1st Authorizer Approved)**: Sent to all Checkers & Authorizers (*"is Authorized by [1st Authorizer Name] & is pending for final authorization"*).
4. **Stage 4 (2nd Authorizer Approved)**: Sent to all Checkers & Authorizers (*"is Authorized by [2nd Authorizer Name] & is finally authorized"*).

### D. Partial Processing & Erroneous Item Reporting
- If a file contains invalid or dormant account entries, valid rows are processed smoothly while invalid rows are isolated into **Partial Failure Reports** detailing failure reasons.

### E. Dashboard & Reports
- **Live Account Balance Dashboard**: Real-time balance reflection for bKash TCSA (`0100202707747`) and Operational Account (`0100224107522`).
- **Reports Download Tab**: Daily, Weekly, Monthly, Yearly Transaction Process Reports and Daily EFT Return Reports (with `TXN_ID` and `REF_NO`).

---

## 🛠️ 2. Technology Stack & Overview

| Component | Technology | Description & Role |
| :--- | :--- | :--- |
| **Framework** | **Laravel 12.64.0 (PHP 8.2+)** | Enterprise PHP framework providing queue management, authentication, routing, and database abstraction. |
| **Admin Panel** | **Filament v5.7.3** | High-performance, modern admin UI providing Maker-Checker tables, dashboard widgets, and role-based workflows. |
| **Database** | **Oracle Database (12c/19c/21c)** | Oracle SQL Developer backend using `yajra/laravel-oci8` driver with strict `Decimal(18,2)` money precision and composite indexing. |
| **Background Queues** | **Laravel Queues & Systemd Worker** | Asynchronous job execution for CBS settlement and notification dispatching. |
| **SFTP Scanner** | **Flysystem SFTP v3 (`league/flysystem-sftp-v3`)** | Automated 15-minute background poller for SFTP directories. |
| **Excel Parser** | **PhpSpreadsheet / Simple-Excel** | Cell-by-cell string preservation for account numbers and routing codes. |
| **High-Precision Math**| **PHP BCMath (`bcadd`, `bcsub`)** | Eliminates floating-point rounding errors in monetary calculations. |

---

## 🏗️ 3. Runtime Architecture & System Safety Rules

```
┌─────────────────────────┐       ┌──────────────────────────┐       ┌────────────────────────┐
│  bKash SFTP Server      │ ────▶ │ Ingestion Worker Job     │ ────▶ │  Oracle Database       │
│  /a2a, /beftn, /rtgs    │       │ (ProcessBkashSftpFiles)  │       │  - BKASH_TRANSACTIONS  │
└─────────────────────────┘       └──────────────────────────┘       │  - POSTING_ATTEMPTS    │
                                                                     │  - NOTIFICATION_OUTBOX │
┌─────────────────────────┐       ┌──────────────────────────┐       └───────────▲────────────┘
│ Filament v3 Portal UI   │ ────▶ │ Asynchronous Settlement  │ ──────────────────┘
│ (Web Process)           │       │ Job (ExecuteCbsSettlement│ ───▶ CBS / T24 Direct API
└─────────────────────────┘       └──────────────────────────┘ ───▶ BACH Network
```

### Golden Architectural Safeguards:
1. **Web vs Worker Process Isolation**: The Filament Web UI **never directly posts to CBS/T24/BACH**. Final 2nd approval writes to DB and dispatches `ExecuteCbsSettlementJob`, preventing browser disconnects from interrupting payment processing.
2. **Double-Payment Defense (`POSTING_ATTEMPTS`)**: Ledger table enforcing unique constraint on `TXN_ID` (`ORA-00001` mechanism), guaranteeing no transaction is ever posted twice.
3. **Transactional Notification Outbox (`NOTIFICATION_OUTBOX`)**: Notifications are queued transactionally alongside state changes, ensuring email/SMS gateway failures never roll back financial approvals.

---

## 🗺️ 4. Portal Navigation & Routes Guide

| Navigation Item | Route | Description |
| :--- | :--- | :--- |
| **Dashboard** | `/admin` | Live CBS Account Balances & Daily Settlement Metrics |
| **bKash Transactions** | `/admin/bkash-transactions` | bKash Checker Table (Pending Verification) |
| **New Transaction Form** | `/admin/bkash-transactions/create` | Single Manual Transaction Creation Form |
| **Upload bKash Excel File** | `/admin/bkash-transactions/upload` | **Dedicated Full-Page Upload** for Bulk Excel Files |
| **bKash Authorization** | `/admin/bkash-transaction-authorizations` | 1st Authorizer Approval Resource |
| **bKash Confirmation** | `/admin/bkash-transaction-confirmations` | 2nd Authorizer Final Approval & Instant Settlement Resource |
| **Failed Transaction Report** | `/admin/bkash-failed-transactions` | Partial Processing Error & Dormant Account Reports |
| **Transaction & EFT Reports** | `/admin/bkash-reports` | Daily, Weekly, Monthly, Yearly Process & EFT Return Reports |

---

## 🚀 5. Local Setup & Execution Guide

```bash
# 1. Run Database Migrations
php artisan migrate

# 2. Create Filament Admin User (if needed)
php artisan make:filament-user

# 3. Run Development Server and Background Queue Worker
composer run dev
```

Visit the portal locally at:
👉 **`http://127.0.0.1:8000/admin`** or **`http://127.0.0.1:8001/admin`**
