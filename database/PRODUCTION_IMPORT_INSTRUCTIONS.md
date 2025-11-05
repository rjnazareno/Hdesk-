# Production Database Import Instructions

## Problem Identified

The SQL dump from `ithelp (2).sql` contains a VIEW with `DEFINER='root'@'localhost'` which **does NOT exist on production**. This causes the import to fail, preventing AUTO_INCREMENT statements from executing.

```sql
-- ❌ FAILS ON PRODUCTION
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sla_summary` ...
```

## Solution: Two-Step Import Process

### **Step 1: Import Main Schema (with manual edit)**

Before importing `ithelp (2).sql`, you need to **remove or fix the DEFINER** in the VIEW definition.

**Option A: Remove DEFINER entirely**
Search for line 466 in the SQL file and change:
```sql
-- BEFORE (line 466)
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sla_summary`

-- AFTER (remove DEFINER clause)
CREATE VIEW `v_sla_summary`
```

**Option B: Use production user**
```sql
-- Change root@localhost to your production user
CREATE ALGORITHM=UNDEFINED DEFINER=`u816220874_AyrgoResolveIT`@`localhost` SQL SECURITY DEFINER VIEW `v_sla_summary`
```

### **Step 2: Run AUTO_INCREMENT Fix**

After the main import completes, run:
```bash
mysql -u u816220874_AyrgoResolveIT -p u816220874_resolveIT < database/fix_all_auto_increment.sql
```

Or via phpMyAdmin:
1. Login to phpMyAdmin
2. Select database `u816220874_resolveIT`
3. Go to SQL tab
4. Copy/paste entire contents of `database/fix_all_auto_increment.sql`
5. Click **Go**

---

## Why This Happens

phpMyAdmin exports include the DEFINER (user who created the object). When importing to a different server:
- Local: `root@localhost` exists
- Production: `root@localhost` does NOT exist, only `u816220874_AyrgoResolveIT@localhost`

MySQL rejects the VIEW creation, causing the entire import to fail partway through, before reaching the AUTO_INCREMENT statements.

---

## Quick Test (After Import)

Run this to verify all tables have AUTO_INCREMENT:
```sql
SELECT 
  TABLE_NAME, 
  COLUMN_NAME, 
  EXTRA 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'u816220874_resolveIT' 
  AND COLUMN_NAME = 'id' 
  AND TABLE_NAME IN ('categories','employees','notifications','sla_breaches','sla_policies','sla_tracking','tickets','ticket_activity','users')
ORDER BY TABLE_NAME;
```

**Expected:** All 9 rows show `auto_increment` in EXTRA column.

**If missing:** Run `fix_all_auto_increment.sql` again.

---

## Alternative: Command Line Import (Most Reliable)

If you have SSH access to production:

```bash
# 1. Upload cleaned SQL file
scp ithelp_production.sql user@server:/tmp/

# 2. Import via command line (more reliable than phpMyAdmin)
mysql -u u816220874_AyrgoResolveIT -p u816220874_resolveIT < /tmp/ithelp_production.sql

# 3. Run AUTO_INCREMENT fix
mysql -u u816220874_AyrgoResolveIT -p u816220874_resolveIT < database/fix_all_auto_increment.sql
```

Command line imports:
- ✅ Don't timeout
- ✅ Show exact error messages
- ✅ Handle large files better
- ✅ More predictable than phpMyAdmin

---

## Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"
**Cause:** VIEW DEFINER issue  
**Fix:** Remove DEFINER from VIEW definition or use `fix_all_auto_increment.sql` which recreates the view correctly

### Error: "Duplicate entry for key 'PRIMARY'"
**Cause:** Database not empty before import  
**Fix:** Drop all tables first:
```sql
DROP DATABASE IF EXISTS u816220874_resolveIT;
CREATE DATABASE u816220874_resolveIT;
```

### Ticket creation still fails after import
**Cause:** AUTO_INCREMENT not applied  
**Fix:** Run `fix_all_auto_increment.sql`

---

**Last Updated:** November 5, 2025  
**Issue:** Production import fails at VIEW creation due to DEFINER mismatch
