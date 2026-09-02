# Janata Bank Account Number Mapping & Balance Diagnostic

**Document Reference:** `DOC-JB-BKASH-ACC-001`  
**Date:** September 2, 2026  
**Status:** AWAITING BANK TREASURY / IT SIGN-OFF  
**Scope:** Read-only diagnostic report; no configuration or code modification performed.

---

## 1. Executive Summary

During transaction processing and dashboard reconciliation in the Janata Bank Corporate Portal (bKash Module), an account numbering format divergence was identified:
- **Configuration & Whitelist:** Configured with 13-digit customer account numbers (`0100202707747` and `0100224107522`).
- **Batch Transaction Files (Excel):** Uploaded files contain 15-digit internal General Ledger (GL) account numbers (`111613120722698` and `111613134119657`) under the `Debit Account` column.
- **Result:** `BkashCalculationService::calculateBalance()` groups debits strictly by exact account string. When 15-digit account transactions are processed against 13-digit initial balance keys, the debits are not matched to the configured initial balances, leaving the dashboard balance card unchanged or miscalculated.

Per governance guidelines, **no code-level alias mapping or balance calculation adjustments are applied** without explicit, written confirmation from the Janata Bank Treasury and IT Core Banking System (CBS) teams.

---

## 2. Technical Evidence & File Audit

### A. Configuration Layer (`config/bkash.php`)
```php
'whitelisted_debit_accounts' => [
    '0100202707747',
    '0100224107522',
],

'initial_balances' => [
    '0100202707747' => 50000000.00, // 5 Crore BDT
    '0100224107522' => 50000000.00, // 5 Crore BDT
],
```

### B. Transaction File Layer (`storage/app/public/Bkash_Files/` & Uploads)
Inspection of production sample batch files:
- `BEFTN_JANATA_BANK_2026_07_28_2Sloty.xlsx`
- `JANATA_BANK_2026_07_28_1Sloty.xlsx`
- `RTGS_JANATA_BANK_2026_07_28_2Sloty.xlsx`

| Column Header | Value Observed in Batch Files | Format |
|---|---|---|
| `Debit Account` | `111613120722698` | 15-digit CBS GL/Internal Ledger |
| `Debit Account` | `111613134119657` | 15-digit CBS GL/Internal Ledger |

### C. Service Calculation Layer (`app/Services/BkashCalculationService.php`)
In `calculateBalance()`:
```php
// Step 1: Initial balances keyed by configured account numbers (13-digit)
$initialBalances = config('bkash.initial_balances', []);

// Step 2: Sum debits grouped by transaction Debit Account from database
$debits = BkashTransaction::selectRaw('debit_account, SUM(amount) as total_debit')
    ->whereIn('status_id', [StatusHelper::SUCCESS, StatusHelper::APPROVED])
    ->groupBy('debit_account')
    ->pluck('total_debit', 'debit_account');

// Step 3: Exact-key lookup
foreach ($initialBalances as $account => $initialBalance) {
    $totalDebit = $debits->get($account, 0); // Exact string match!
    $currentBalance = $initialBalance - $totalDebit;
    ...
}
```
Because `'111613120722698' !== '0100202707747'`, `$debits->get('0100202707747', 0)` evaluates to `0`, causing the balance card to display the static initial balance rather than reflecting net deductions.

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
   Are the initial balances (e.g., 50,000,000.00 BDT) held at the customer account level (`0100...`) or at the internal GL clearing account level (`1116...`)?

---

## 5. Proposed Solution (Pending Approval)

Upon receipt of written confirmation from Treasury/IT, the recommended implementation approach is:
```php
// config/bkash.php
'account_aliases' => [
    '111613120722698' => '0100202707747',
    '111613134119657' => '0100224107522',
],
```
And in `BkashCalculationService::calculateBalance()`, normalize `$debit_account` through the alias map before aggregating balances.

*Action: No code changes will be committed for Item 2 until formal answers to the above questions are recorded.*
