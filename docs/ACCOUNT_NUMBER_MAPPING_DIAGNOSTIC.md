# Janata Bank Account Number Mapping & Balance Diagnostic

**Document Reference:** `DOC-JB-BKASH-ACC-001`  
**Date:** September 2, 2026  
**Status:** AWAITING BANK TREASURY / IT SIGN-OFF  
**Scope:** Read-only diagnostic report; no configuration or code modification performed.  
**Verification:** This report was verified against actual source code and local storage on September 2, 2026.

---

## 1. Executive Summary

During transaction processing and dashboard reconciliation in the Janata Bank Corporate Portal (bKash Module), an account numbering format divergence was identified:
- **Configuration & Whitelist:** Configured with 13-digit customer account numbers (`0100202707747` and `0100224107522`).
- **Batch Transaction Files (Excel):** Uploaded files contain 15-digit internal General Ledger (GL) account numbers (`111613120722698` and `111613134119657`) under the `Debit Account` column (ingested into database table `BKASH_TRANSACTIONS` as `source_account_no`).
- **Result:** In `app/Filament/Pages/Dashboard.php`, `calculateBalance(string $accountNumber): float` filters debits strictly by exact account string: `BkashTransaction::where('source_account_no', $accountNumber)...`. When 15-digit account transactions are processed against 13-digit initial balance keys, the debits are not matched to the configured initial balances, leaving the dashboard balance card unchanged (displaying the static initial balance rather than reflecting net deductions).

Per governance guidelines, **no code-level alias mapping or balance calculation adjustments are applied** without explicit, written confirmation from the Janata Bank Treasury and IT Core Banking System (CBS) teams.

---

## 2. Technical Evidence & File Audit

### A. Configuration Layer (`config/bkash.php`)
```php
'whitelisted_debit_accounts' => env('BKASH_WHITELISTED_DEBIT_ACCOUNTS', '0100202707747,0100224107522,111613120722698,111613134119657'),

'initial_balances' => [
    '0100202707747' => (float) env('BKASH_TCSA_INITIAL_BALANCE', 5420000000.50),
    '0100224107522' => (float) env('BKASH_OPS_INITIAL_BALANCE', 185000000.00),
],
```

### B. Transaction File Layer (`storage/app/public/Bkash_Files/` & Uploads)
Inspection of production sample batch files:
- `BEFTN_JANATA_BANK_2026_07_28_2Sloty.xlsx`
- `JANATA_BANK_2026_07_28_1Sloty.xlsx`
- `RTGS_JANATA_BANK_2026_07_28_2Sloty.xlsx`

*(Note: These production sample files physically reside in `storage/app/public/Bkash_Files/` as untracked fixtures; analysis is cross-referenced with both disk samples and codebase configurations.)*

| Column Header (Excel) | Value Observed in Batch Files | Database Column (`BKASH_TRANSACTIONS`) | Format |
|---|---|---|---|
| `Debit Account` | `111613120722698` | `source_account_no` | 15-digit CBS GL/Internal Ledger |
| `Debit Account` | `111613134119657` | `source_account_no` | 15-digit CBS GL/Internal Ledger |

### C. Service Calculation Layer (`app/Filament/Pages/Dashboard.php`)
In `calculateBalance(string $accountNumber): float`:
```php
private function calculateBalance(string $accountNumber): float
{
    try {
        $totalDebited = (float) BkashTransaction::where('source_account_no', $accountNumber)
            ->whereIn('status_id', [
                BkashTransaction::STATUS_FINAL_AUTHORIZED,
                BkashTransaction::STATUS_CBS_SUCCESS,
            ])
            ->sum('amount');

        $balances = config('bkash.initial_balances', []);
        $initialBalance = (float) ($balances[$accountNumber] ?? 0.00);

        return max(0.0, $initialBalance - $totalDebited);
    } catch (\Throwable $e) {
        return 0.00;
    }
}
```
Because `'111613120722698' !== '0100202707747'`, query `BkashTransaction::where('source_account_no', '0100202707747')` yields `0.00` total debited, causing the balance card to display the static initial balance rather than reflecting net deductions.

---

## 3. Account Number Architecture Analysis

In Bangladesh banking Core Banking Systems (CBS) such as Janata Bank's Infinity / Temenos / Flora / CBS:
1. **13-digit Format (`0100...`):**
   - Typically represents the customer-facing account number (Branch Code: 4 digits + Account Type: 2 digits + Serial/Check Digit: 7 digits).
2. **15-digit Format (`1116...`):**
   - Typically represents the internal CBS General Ledger (GL) or Central Clearing Account routing code used for RTGS, BEFTN, and corporate bulk disbursements.
3. **Mapping Possibility:**
   - Account `0100202707747` may internally map 1:1 to GL `111613120722698`.
   - Account `0100224107522` may internally map 1:1 to GL `111613134119657`.

---

## 4. Treasury & IT Sign-Off Questionnaire

Before applying any alias mapping or modifying `config/bkash.php`, the following points must be officially confirmed:

1. **Definitive 1:1 Mapping:**  
   Does 13-digit account `0100202707747` strictly and exclusively map to 15-digit account `111613120722698` in CBS?
2. **Second Account Mapping:**  
   Does 13-digit account `0100224107522` strictly and exclusively map to 15-digit account `111613134119657` in CBS?
3. **Source of Truth in Batch Files:**  
   Will upcoming bKash batch Excel/CSV files continue to be generated with the 15-digit CBS GL format, or will they transition to the 13-digit customer account format?
4. **Whitelist Enforcement:**  
   Should the system whitelist both 13-digit and 15-digit formats in `config/bkash.php`, or should incoming 15-digit accounts be normalized to 13-digit numbers upon file upload?
5. **Initial Balance Allocation:**  
   Are the initial balances held at the customer account level (`0100...`) or at the internal GL clearing account level (`1116...`)?

---

## 5. Proposed Solution (Pending Approval)

Upon receipt of written confirmation from Treasury/IT, the recommended implementation approach is:
```php
// config/bkash.php
'account_number_aliases' => [
    '0100202707747' => ['0100202707747', '111613120722698'],
    '0100224107522' => ['0100224107522', '111613134119657'],
],
```
And in `app/Filament/Pages/Dashboard.php`'s `calculateBalance()`, query transactions where `source_account_no` matches any alias for the specified account number before subtracting from initial balance.

*Action: No code or configuration changes will be committed for dashboard balance mapping until formal answers to the above questions are recorded.*
