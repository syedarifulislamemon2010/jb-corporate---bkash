# Janata Bank Corporate Payment Portal
## bKash Automated Payment Settlement System

An enterprise-grade, high-security automated payment settlement portal built with **Laravel 12 (PHP 8.2+)**, **Filament v5**, and **Oracle Database (12c/19c/21c)** for automated settlement of bKash payments from Janata Bank accounts via A2A, BEFTN, and RTGS.

---

## 📌 Features & 10/10 Architecture Highlights

- **Triple-Folder SFTP Ingestion Engine (`ProcessBkashSftpFiles`)**:
  - Automated 15-minute polling for 3 channels: `Account to-Account` (A2A), `BEFTN`, `RTGS`.
  - Regex filename pattern enforcement (`JANATA_BANK_YYYY_MM_DD_xSloty.xlsx`, `BEFTN_...`, `RTGS_...`).
  - Filename uniqueness & SHA-256 integrity hash verification (`sha256`).

- **Partial Processing & Erroneous/Dormant Item Management**:
  - Erroneous or dormant rows are automatically isolated into `BkashFailedTransaction` with failure reasons.
  - Valid rows continue to process smoothly without canceling the entire file batch.
  - Dedicated **Failed Transaction Report** view and export in the portal.

- **Maker-Checker-Dual Authorizer Pipeline**:
  - **bKash Checker**: Verify ingested files against email files & approve (`status_id = 1000 -> 1001`).
  - **1st Authorizer**: Dual-authorization step 1 (`status_id = 1001 -> 1002`).
  - **2nd Authorizer**: Final authorization step 2 (`status_id = 1002 -> 1003`).

- **Web vs Settlement Process Isolation (`ExecuteCbsSettlementJob`)**:
  - Web UI never directly posts to CBS/T24/BACH.
  - Final approval dispatches an asynchronous background settlement queue job.
  - `POSTING_ATTEMPTS` ledger prevents double-posting (`ORA-00001` mechanism).

- **Transactional SMS & Email Outbox Engine (`NotificationService`)**:
  - BDT Lakh/Crore comma formatting (`1,56,100.82` format).
  - 4 Journey Stage notifications:
    1. SFTP -> Pending Checker
    2. Checked by Checker -> Pending Authorization
    3. 1st Authorizer Approved -> Pending 2nd Authorization
    4. 2nd Authorizer Approved -> Final Authorized

- **Live CBS Balance Dashboard & Reports**:
  - Live reflection of bKash Trust Cum Settlement Account (`0100202707747`) and Operational Account (`0100224107522`).
  - Daily, Weekly, Monthly, Yearly Transaction Process Reports & Daily EFT Return Reports with `TXN_ID` & `REF_NO`.

---

## 🛠️ Portal Navigation & Routes

| Portal Feature / Route | Purpose |
| :--- | :--- |
| **`/admin`** | Dashboard with Live CBS Balances & Transaction Metrics |
| **`/admin/bkash-transactions`** | bKash Checker Table (Pending Verification) |
| **`/admin/bkash-transactions/create`** | Single Manual Transaction Creation Form |
| **`/admin/bkash-transactions/upload`** | Dedicated **Upload bKash Excel File** Full Page |
| **`/admin/bkash-transaction-authorizations`** | 1st Authorizer Approval Table |
| **`/admin/bkash-transaction-confirmations`** | 2nd Authorizer Final Approval Table |
| **`/admin/bkash-failed-transactions`** | Partial Processing Error & Dormant Account Reports |
| **`/admin/bkash-reports`** | Transaction Process & Daily EFT Return Export Reports |

---

## 🚀 Local Installation & Running Guide

```bash
# 1. Run Oracle / Database Migrations
php artisan migrate

# 2. Create Filament Admin User (if needed)
php artisan make:filament-user

# 3. Run Development Server and Background Queue Worker
composer run dev
```

Visit the portal at: `http://127.0.0.1:8000/admin`
