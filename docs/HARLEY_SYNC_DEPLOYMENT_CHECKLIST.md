# Harley Employee Sync - Deployment Checklist

**Status**: Ready for Deployment  
**Date**: November 5, 2025

---

## ✅ Pre-Deployment Checklist

### Local Testing
- [ ] Run `http://localhost/ResolveIT/test_webhook_sync.php`
- [ ] Verify 3 test employees created successfully
- [ ] Check employees in admin panel (`/admin/customers.php`)
- [ ] Delete test employees (TEST001, TEST002, TEST003)

### Configuration
- [ ] Open `docs/harley_sync_script.php`
- [ ] Update `$WEBHOOK_URL` with production domain
- [ ] Verify `$API_KEY` matches webhook file
- [ ] Set `$DB_NAME` (Harley database name)
- [ ] Set `$DB_USER` (Harley database user)
- [ ] Set `$DB_PASS` (Harley database password)
- [ ] Save file

### Database Structure Verification
- [ ] Confirm Harley has `employees` table
- [ ] Verify column names match script expectations
- [ ] Update SQL query if column names differ (lines 31-40)
- [ ] Test query in phpMyAdmin/MySQL console

---

## 📤 Deployment Checklist

### Upload to Harley Server
- [ ] Connect to `harley.resourcestaffonline.com` via FTP/File Manager
- [ ] Navigate to `/public_html/Public/module/`
- [ ] Upload `harley_sync_script.php`
- [ ] Set file permissions to 644 or 755
- [ ] Verify file is accessible

### First Production Run
- [ ] Access: `https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php`
- [ ] Check for "Database connection successful" message
- [ ] Verify employees found count
- [ ] Check webhook sync completed successfully
- [ ] Review created/updated counts

### Verification
- [ ] Login to IThelp admin panel
- [ ] Go to Employees page (`/admin/customers.php`)
- [ ] Verify synced employees appear with `employee_id` set
- [ ] Test login with a synced employee account
- [ ] Username: their username
- [ ] Password: `Welcome123!`
- [ ] Confirm employee can create tickets

---

## ⚙️ Automation Setup (Optional)

### Cron Job Configuration
- [ ] Login to cPanel (Hostinger)
- [ ] Navigate to: Advanced → Cron Jobs
- [ ] Add new cron job
- [ ] Enter command:
  ```
  0 2 * * * /usr/bin/php /home/YOUR_USERNAME/public_html/Public/module/harley_sync_script.php > /dev/null 2>&1
  ```
- [ ] Replace `YOUR_USERNAME` with actual username
- [ ] Set frequency (recommended: daily at 2 AM)
- [ ] Save cron job
- [ ] Test cron job runs successfully

### Monitoring Setup
- [ ] Add email notification in cPanel cron settings
- [ ] Or setup log file:
  ```
  0 2 * * * /usr/bin/php /path/to/harley_sync_script.php >> /path/to/sync.log 2>&1
  ```
- [ ] Schedule regular log reviews

---

## 🔒 Security Checklist

### API Key Security
- [ ] Generate new API key for production (optional but recommended)
  ```php
  echo bin2hex(random_bytes(32));
  ```
- [ ] Update `WEBHOOK_SECRET_KEY` in `webhook_employee_sync.php`
- [ ] Update `$API_KEY` in `harley_sync_script.php`
- [ ] Never commit API key to public repositories

### HTTPS Verification
- [ ] Confirm webhook URL uses HTTPS (not HTTP)
- [ ] Verify SSL certificate is valid
- [ ] Test webhook over secure connection

### File Permissions
- [ ] `webhook_employee_sync.php` → 644
- [ ] `harley_sync_script.php` → 644 or 755
- [ ] No world-writable permissions (777)

---

## 📊 Post-Deployment Checklist

### First 24 Hours
- [ ] Monitor first sync results
- [ ] Check for any failed employees
- [ ] Verify no duplicate records created
- [ ] Test employee login access
- [ ] Check tickets can be created by synced employees

### First Week
- [ ] Review sync logs daily
- [ ] Verify all new employees appear in IThelp
- [ ] Check for any data inconsistencies
- [ ] Gather feedback from employees
- [ ] Address any issues promptly

### First Month
- [ ] Reduce monitoring frequency to weekly
- [ ] Document any issues and solutions
- [ ] Consider enhancements (email notifications, etc.)
- [ ] Review employee feedback
- [ ] Update documentation if needed

---

## 🐛 Troubleshooting Checklist

### If Sync Fails

- [ ] Check error message in browser
- [ ] Verify database credentials
- [ ] Test database connection manually
- [ ] Check API key matches in both files
- [ ] Verify webhook URL is correct
- [ ] Check Apache/PHP error logs
- [ ] Test webhook locally first
- [ ] Review failed employees list

### If Employees Not Appearing

- [ ] Check webhook response for errors
- [ ] Verify IThelp database connection
- [ ] Check employees table structure
- [ ] Look for SQL errors in logs
- [ ] Verify required fields are present
- [ ] Check email format validation

### If Duplicates Created

- [ ] Check `employee_id` uniqueness
- [ ] Verify `findByEmployeeId()` method
- [ ] Check `findByEmail()` fallback
- [ ] Review database constraints
- [ ] Clean up duplicates manually:
  ```sql
  -- Find duplicates
  SELECT email, COUNT(*) FROM employees GROUP BY email HAVING COUNT(*) > 1;
  ```

---

## 📝 Documentation Checklist

### Files to Keep
- [ ] `HARLEY_SYNC_DEPLOYMENT_GUIDE.md` - Full guide
- [ ] `HARLEY_SYNC_QUICKSTART.md` - Quick reference
- [ ] `HARLEY_SYNC_VISUAL_GUIDE.md` - Diagrams
- [ ] `HARLEY_SYNC_CONFIGURATION_COMPLETE.md` - Summary
- [ ] This checklist - Track progress

### Files to Archive
- [ ] `test_webhook_sync.php` - Keep for future testing
- [ ] `harley_sync_script.php` - Keep copy in docs/

### Knowledge Transfer
- [ ] Share deployment guide with team
- [ ] Document custom configurations
- [ ] Create runbook for common issues
- [ ] Train team on manual sync process

---

## ✅ Sign-Off Checklist

### Technical Lead
- [ ] Code reviewed and approved
- [ ] Security measures verified
- [ ] Testing completed successfully
- [ ] Documentation is comprehensive
- [ ] Rollback plan in place

### System Administrator
- [ ] Files uploaded to production
- [ ] Cron job configured correctly
- [ ] Monitoring setup and working
- [ ] Backup procedures in place
- [ ] Access controls verified

### Project Manager
- [ ] Deployment timeline confirmed
- [ ] Stakeholders notified
- [ ] Success criteria defined
- [ ] Support plan established
- [ ] Budget approved (if applicable)

---

## 📞 Emergency Contacts

### Issues During Deployment
- **Technical Support**: [Your IT Contact]
- **Database Admin**: [DBA Contact]
- **Hosting Support**: Hostinger Support (24/7)

### Escalation Path
1. Check troubleshooting guide
2. Review error logs
3. Contact technical support
4. Rollback if critical

---

## 🎯 Success Criteria

Deployment is successful when:

✅ All items in "Pre-Deployment Checklist" completed  
✅ All items in "Deployment Checklist" completed  
✅ At least 5 employees synced successfully  
✅ Synced employee can login and create ticket  
✅ No critical errors in logs  
✅ Team trained on system usage  
✅ Documentation reviewed and approved  

---

## 📅 Milestone Tracking

| Milestone | Target Date | Completed | Notes |
|-----------|-------------|-----------|-------|
| Local testing | Nov 5, 2025 | ⚪ | |
| Configuration | Nov 5, 2025 | ⚪ | |
| Upload to server | ___________ | ⚪ | |
| First production run | ___________ | ⚪ | |
| Verification | ___________ | ⚪ | |
| Cron job setup | ___________ | ⚪ | |
| Team training | ___________ | ⚪ | |
| Go-live | ___________ | ⚪ | |

---

## Notes & Comments

```
Use this space to track issues, decisions, and important notes during deployment:

[Date] [Name]: 



```

---

**Prepared by**: GitHub Copilot  
**Date**: November 5, 2025  
**Version**: 1.0  

---

## Quick Links

- **Local Test**: `http://localhost/ResolveIT/test_webhook_sync.php`
- **Production Script**: `https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php`
- **Admin Panel**: `http://your-ithelp-domain.com/admin/customers.php`
- **Full Guide**: `docs/HARLEY_SYNC_DEPLOYMENT_GUIDE.md`

---

**Print this checklist and mark off items as you complete them! ✓**
