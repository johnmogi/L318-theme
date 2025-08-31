# Quiz Database Tables Repair

## Implementation Details
- **File**: `/includes/quiz/repair-db-tables.php`
- **Activation**: Runs on admin_init for users with manage_options capability
- **Frequency**: Maximum once per week (cached)

## Verification Steps
1. Check debug.log for entries containing `[LD_HINTS]`
2. Run SQL verification:
   ```sql
   SHOW TABLES LIKE 'wp_pro_quiz_answer';
   DESCRIBE wp_pro_quiz_answer;
   ```
3. Test quiz hint functionality

## Rollback Procedure
1. Delete the include from functions.php
2. Remove /includes/quiz/repair-db-tables.php
3. Clear transients:
   ```sql
   DELETE FROM wp_options WHERE option_name = '_transient_ld_quiz_tables_verified';
   ```
