-- Stage 4.2.5: prevent one M-Pesa receipt from being attached to multiple payments.
-- Run the diagnostic query first. Resolve any returned duplicate groups manually
-- before running the ALTER TABLE statement below.
--
-- SELECT UPPER(REPLACE(REPLACE(TRIM(transaction_reference), ' ', ''), '-', '')) AS normalized_code,
--        COUNT(*) AS uses_count, GROUP_CONCAT(id ORDER BY id) AS payment_ids
-- FROM payments
-- WHERE transaction_reference IS NOT NULL AND TRIM(transaction_reference) <> ''
-- GROUP BY normalized_code HAVING COUNT(*) > 1;
--
-- The generated value removes spaces and hyphens and compares case-insensitively.
-- Multiple NULL references remain allowed (e.g. historical non-M-Pesa payment rows).
ALTER TABLE payments
  ADD COLUMN transaction_reference_normalized VARCHAR(100)
    GENERATED ALWAYS AS (
      NULLIF(UPPER(REPLACE(REPLACE(TRIM(transaction_reference), ' ', ''), '-', '')), '')
    ) STORED,
  ADD UNIQUE KEY uq_payments_transaction_reference_normalized (transaction_reference_normalized);
