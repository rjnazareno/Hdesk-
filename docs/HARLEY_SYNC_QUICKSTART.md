# Harley Sync - Quick Start Card

## 🚀 Quick Deployment (5 Minutes)

### 1️⃣ Test Locally First
```
http://localhost/ResolveIT/test_webhook_sync.php
```
✅ Should create 3 test employees successfully

### 2️⃣ Update Configuration
Edit `harley_sync_script.php` (lines 13-16):
```php
$WEBHOOK_URL = 'https://your-ithelp-domain.com/webhook_employee_sync.php';
$API_KEY = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece';
$DB_NAME = 'your_harley_database_name';
$DB_USER = 'your_database_user';
$DB_PASS = 'your_database_password';
```

### 3️⃣ Upload to Harley
- **FTP to**: `harley.resourcestaffonline.com`
- **Path**: `/public_html/Public/module/`
- **Upload**: `harley_sync_script.php`

### 4️⃣ Test on Server
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```
✅ Check for success message

### 5️⃣ Automate (Optional)
**cPanel → Cron Jobs**:
```bash
0 2 * * * /usr/bin/php /home/USERNAME/public_html/Public/module/harley_sync_script.php
```
Runs daily at 2:00 AM

---

## 📋 Files Checklist

| File | Location | Status |
|------|----------|--------|
| `webhook_employee_sync.php` | IThelp root | ✅ Ready |
| `harley_sync_script.php` | Upload to Harley | ⚠️ Configure |
| `test_webhook_sync.php` | Local testing | ✅ Ready |

---

## 🔧 Must Configure

- [ ] `$WEBHOOK_URL` - Your IThelp domain
- [ ] `$API_KEY` - Must match both files
- [ ] `$DB_NAME` - Harley database name
- [ ] `$DB_USER` - Harley database username
- [ ] `$DB_PASS` - Harley database password

---

## 🐛 Quick Troubleshooting

| Error | Fix |
|-------|-----|
| Database connection failed | Check DB credentials |
| No employees found | Check table/column names in SQL query |
| 401 Unauthorized | API keys don't match |
| 404 Not Found | Check WEBHOOK_URL |

---

## 📞 Support

Full documentation: `docs/HARLEY_SYNC_DEPLOYMENT_GUIDE.md`

---

**Generated**: November 5, 2025
