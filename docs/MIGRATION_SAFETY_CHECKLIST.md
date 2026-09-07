# Production Migration Safety & Deployment Checklist

**Target Migration:** `2026_09_01_000001_rename_account_columns_in_bkash_transactions.php`  
**Database Engine:** Oracle Database (12c / 19c / 21c / 23ai)  
**Tables Affected:** `BKASH_TRANSACTIONS`, `BKASH_FAILED_TRANSACTIONS`  
**Renamed Columns:**
- `credit_account_no` $\rightarrow$ `source_account_no` (TCSA / Operational source account)
- `debit_account_no` $\rightarrow$ `beneficiary_account_no` (Beneficiary destination account)

---

## 1. Pre-Migration Database Backup (Mandatory)

Before executing any DDL or migration on the Production Oracle database, take a full backup or export of the affected tables:

```bash
# Option A: Oracle Data Pump Export (expdp)
expdp corporate/corporate@cdb \
  TABLES=BKASH_TRANSACTIONS,BKASH_FAILED_TRANSACTIONS \
  DIRECTORY=DATA_PUMP_DIR \
  DUMPFILE=bkash_tables_pre_rename_$(date +%Y%m%d_%H%M%S).dmp \
  LOGFILE=bkash_tables_pre_rename.log

# Option B: Fast Table-Level Copy (SQL*Plus / PL/SQL Developer)
CREATE TABLE bkash_txns_backup_20260902 AS SELECT * FROM bkash_transactions;
CREATE TABLE bkash_failed_backup_20260902 AS SELECT * FROM bkash_failed_transactions;
```

---

## 2. Dry-Run Verification on Staging / UAT (Pretend Mode)

In a Staging/UAT environment configured with Oracle (matching production data volume), run the migration in preview mode:

```bash
php artisan migrate --pretend
```

### Expected DDL Output for Oracle:
```sql
ALTER TABLE bkash_transactions RENAME COLUMN credit_account_no TO source_account_no;
ALTER TABLE bkash_transactions RENAME COLUMN debit_account_no TO beneficiary_account_no;
ALTER TABLE bkash_failed_transactions RENAME COLUMN credit_account_no TO source_account_no;
ALTER TABLE bkash_failed_transactions RENAME COLUMN debit_account_no TO beneficiary_account_no;
```

> [!NOTE]
> Under Oracle, `ALTER TABLE ... RENAME COLUMN` is a fast metadata operation. It does not rewrite table data blocks and executes in under 1 second even on tables with millions of rows.

---

## 3. Staging Execution & Timing Benchmark

Run the migration on Staging/UAT to verify DDL execution and record execution time:

```bash
time php artisan migrate
```

- Verify that migration status marks as `[Ran]` without errors.
- Note the time taken to establish the maintenance window duration for Production.

---

## 4. Post-Migration Data & Schema Inspection

Verify in Oracle that the columns and existing indexes have been renamed correctly:

```sql
-- 1. Check columns on BKASH_TRANSACTIONS
SELECT column_name, data_type, data_length, nullable 
FROM user_tab_cols 
WHERE table_name = 'BKASH_TRANSACTIONS' 
  AND column_name IN ('SOURCE_ACCOUNT_NO', 'BENEFICIARY_ACCOUNT_NO', 'CREDIT_ACCOUNT_NO', 'DEBIT_ACCOUNT_NO');

-- 2. Check indexes (Oracle preserves indexes automatically on renamed columns)
SELECT index_name, column_name, column_position 
FROM user_ind_columns 
WHERE table_name = 'BKASH_TRANSACTIONS'
ORDER BY index_name, column_position;

-- 3. Sample row inspection
SELECT id, reference_id, source_account_no, beneficiary_account_no, amount 
FROM bkash_transactions 
WHERE ROWNUM <= 10;
```

---

## 5. End-to-End Application Workflow Validation on Staging

Perform a complete workflow verification cycle before approving production release:
1. **Upload File:** Upload a sample bKash Excel file via `/admin/bkash-transactions/upload`.
2. **Checker Verification:** Verify transactions in `Checker - Verify Files` queue.
3. **1st Authorization:** Approve in `Transaction Authorization` queue (Stage 2).
4. **2nd Authorization & CBS Settlement:** Confirm in `Transaction Confirmation` queue (Stage 3/4).
5. **Reports:** Export and verify report generation in `Audits & Reports`.

---

## 6. Production Maintenance Window Execution

Once Staging verification passes 100%:
1. Enable maintenance mode:
   ```bash
   php artisan down --message="System maintenance in progress. Please wait."
   ```
2. Run database migration:
   ```bash
   php artisan migrate --force
   ```
3. Clear application caches:
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. Disable maintenance mode:
   ```bash
   php artisan up
   ```

---

## 7. Rollback Procedure (Emergency Only)

If any unforeseen issue arises, rollback to the previous column names:

```bash
php artisan migrate:rollback --step=1
```

Or via direct Oracle SQL:
```sql
ALTER TABLE bkash_transactions RENAME COLUMN source_account_no TO credit_account_no;
ALTER TABLE bkash_transactions RENAME COLUMN beneficiary_account_no TO debit_account_no;
ALTER TABLE bkash_failed_transactions RENAME COLUMN source_account_no TO credit_account_no;
ALTER TABLE bkash_failed_transactions RENAME COLUMN beneficiary_account_no TO debit_account_no;
```