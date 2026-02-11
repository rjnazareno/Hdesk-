# ✅ PRODUCTION RESET CHECKLIST
**URL:** https://hdesk.resourcestaffonline.com/  
**Date:** _______________  
**Done by:** _______________

---

## Pre-Reset

- [ ] Read SIMPLE_PRODUCTION_STEPS.md
- [ ] (Optional) Backup database via phpMyAdmin Export
- [ ] Notify users system will be reset
- [ ] Close all other browser tabs with the system

---

## Database Cleanup

- [ ] Open https://hpanel.hostinger.com/
- [ ] Navigate to Databases → u816220874_ticketing
- [ ] Click "Enter phpMyAdmin"
- [ ] Select database: u816220874_ticketing
- [ ] Click SQL tab
- [ ] Copy contents of: database/CLEAN_RESET_TICKETS.sql
- [ ] Paste into query box
- [ ] Click "Go"
- [ ] Check result (some "table doesn't exist" errors are NORMAL)
- [ ] Verify main result: tickets = 0, users > 0

---

## File Cleanup

Choose ONE method:

### Method A: Web Interface
- [ ] Upload cleanup_uploads.php to server (if needed)
- [ ] Visit: https://hdesk.resourcestaffonline.com/cleanup_uploads.php
- [ ] Copy confirmation key
- [ ] Paste and submit
- [ ] Wait for "Cleanup completed successfully!"

### Method B: File Manager
- [ ] Login to Hostinger File Manager
- [ ] Navigate to uploads/ directory
- [ ] Select all files
- [ ] Click Delete
- [ ] Confirm deletion

---

## Verification

- [ ] Visit: https://hdesk.resourcestaffonline.com/verify_reset.php
- [ ] Verify shows: "Reset Successful! ✅"
- [ ] All checks are green ✓
- [ ] Press Ctrl+Shift+Delete (clear cache)
- [ ] Clear "Cached images and files"
- [ ] Close browser completely
- [ ] Reopen browser

---

## Testing

- [ ] Logout from system
- [ ] Login as employee account
- [ ] Create test ticket: "Testing after reset"
- [ ] Verify ticket number: TKT-000001
- [ ] Note ticket ID: _______
- [ ] Logout
- [ ] Login as IT staff account
- [ ] Find the test ticket in queue
- [ ] Assign ticket to yourself
- [ ] Refresh page
- [ ] Verify assignment persists correctly
- [ ] Update status to "In Progress"
- [ ] Add comment: "Testing complete"
- [ ] Verify: No assignment changes
- [ ] Verify: No tracking errors

---

## Security Cleanup

- [ ] Login to Hostinger File Manager
- [ ] Delete: cleanup_uploads.php
- [ ] Delete: verify_reset.php (optional)
- [ ] Confirm files deleted

---

## Post-Reset

- [ ] Dashboard shows 0 tickets (before test)
- [ ] Test ticket created successfully
- [ ] Assignment tracking works correctly
- [ ] Notifications work (if enabled)
- [ ] No console errors (F12 → Console)
- [ ] Notify users: System is ready

---

## Optional: Schema Simplification

If you want to prevent future tracking issues:

- [ ] Read about OPTIONAL_SIMPLIFY_SCHEMA.sql
- [ ] Decide if you want to run it
- [ ] (If yes) Run in phpMyAdmin
- [ ] (If yes) Update code to remove submitter_type logic

---

## Notes / Issues Encountered

```
____________________________________________

____________________________________________

____________________________________________

____________________________________________
```

---

## Sign-Off

**Reset completed successfully:** [ ] YES  [ ] NO

**Test ticket works correctly:** [ ] YES  [ ] NO

**Ready for production use:** [ ] YES  [ ] NO

**Date completed:** _______________

**Time taken:** _______ minutes

---

## For Future Reference

Next ticket number will be: **TKT-000001**

If issues occur, check:
- Browser cache cleared?
- Logged out and back in?
- verify_reset.php shows all green?
- Console errors? (F12 → Console)

Docs: See PRODUCTION_RESET_GUIDE.md
