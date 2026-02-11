# HDesk - Hostinger Deployment Guide

## Quick Deployment Steps

### 1. Prepare Files
```bash
# Remove development-only files (optional)
rm -rf node_modules/
rm -rf _backup/
```

### 2. Upload to Hostinger
- Use File Manager or FTP
- Upload all files to `public_html/` directory
- Make sure `.htaccess` is uploaded (may be hidden)

### 3. Create Environment File
1. Copy `.env.production` to `.env`
2. Update with your actual credentials:

```env
APP_ENV=production
DB_HOST=localhost
DB_USER=u816220874_AyrgoResolveIT
DB_PASS=YOUR_ACTUAL_PASSWORD
DB_NAME=u816220874_resolveit
BASE_URL=https://yourdomain.com/
```

### 4. Import Database
1. Go to Hostinger hPanel → MySQL Databases
2. Open phpMyAdmin for your database
3. Import `database/Main DB/u816220874_resolveIT.sql`
4. See `database/PRODUCTION_IMPORT_INSTRUCTIONS.md` if you encounter VIEW errors

### 5. Set Permissions
```bash
chmod 755 /home/u816220874/public_html/
chmod -R 755 /home/u816220874/public_html/uploads/
chmod -R 755 /home/u816220874/public_html/logs/
```

### 6. Verify Configuration
- `.htaccess` RewriteBase should be `/` for root deployment
- Check that `mod_rewrite` is enabled (default on Hostinger)

### 7. Test
- Visit https://yourdomain.com/
- Login with admin credentials: `admin / admin123`
- CHANGE THE PASSWORD immediately after first login

---

## Production Settings Applied

| Setting | Value |
|---------|-------|
| Error Display | OFF (logged to `logs/php_errors.log`) |
| Environment | production |
| Base URL | Auto-detected or from .env |
| Database | From .env file |

---

## Troubleshooting

### Blank Page
- Check `logs/php_errors.log` for errors
- Verify `.env` file exists with correct credentials

### 500 Error
- Check `.htaccess` syntax
- Verify PHP version (7.4+ required)

### Database Connection Failed
- Verify DB credentials in `.env`
- Check database exists in Hostinger hPanel

### CSS/JS Not Loading
- Check BASE_URL in `.env`
- Verify file permissions
