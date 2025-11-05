# Production Deployment Checklist

## Tailwind CSS Setup ✅ COMPLETED

### What Was Changed
The application now supports both **development** and **production** Tailwind CSS loading:

- **Development**: Uses CDN (`https://cdn.tailwindcss.com`) — faster iteration, no build needed
- **Production**: Uses local compiled CSS (`assets/css/tailwind.min.css`) — optimized, ~50KB vs 250KB

### Files Modified
1. ✅ `config/config.php` - Added `ENVIRONMENT` config and `getTailwindCSS()` helper
2. ✅ `views/layouts/header.php` - Updated to use helper function
3. ✅ `login.php` - Updated to use helper function
4. ✅ `admin/view_ticket.php` - Updated to use helper function
5. ✅ `article.php` - Updated to use helper function

### How It Works
```php
// In config/config.php - reads APP_ENV environment variable
define('ENVIRONMENT', getenv('APP_ENV') ?? 'development');

// Helper function automatically switches based on environment
function getTailwindCSS() {
    if (ENVIRONMENT === 'production') {
        // Load local compiled CSS (must exist at assets/css/tailwind.min.css)
        return '<link rel="stylesheet" href="' . ASSETS_URL . 'css/tailwind.min.css">';
    } else {
        // Development: Use CDN
        return '<script src="https://cdn.tailwindcss.com"><\/script>';
    }
}
```

---

## Pre-Production Setup Steps

### Step 1: Build Tailwind CSS (One-time)
**Requirement**: Node.js and npm must be installed

```bash
# Navigate to project root
cd /path/to/IThelp

# Install Tailwind dependencies
npm install -D tailwindcss postcss autoprefixer

# Create Tailwind config
npx tailwindcss init -p

# Update tailwind.config.js to scan PHP files:
# content: [
#   "./**/*.php",
#   "!./vendor/**/*.php",
#   "!./debug_archive/**/*.php"
# ]

# Build production CSS (minified)
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.min.css --minify

# Verify output file was created
ls -lh assets/css/tailwind.min.css  # Should be ~50KB
```

### Step 2: Deploy to Production
```bash
# Upload compiled CSS file
scp assets/css/tailwind.min.css user@production:/var/www/ithelp/assets/css/

# Verify file exists on server
ssh user@production 'ls -lh /var/www/ithelp/assets/css/tailwind.min.css'

# Set environment variable on server
export APP_ENV=production  # Add to .bashrc or .env

# Restart web server (Apache/Nginx)
sudo systemctl restart apache2  # or nginx
```

### Step 3: Test Production Build Locally
```bash
# Simulate production environment
export APP_ENV=production

# Verify CSS loads from local file (not CDN)
curl http://localhost/IThelp/login.php | grep 'tailwind.min.css'
```

---

## Post-Deployment Verification

### Check Browser Console
- ❌ Should NOT see: "cdn.tailwindcss.com should not be used in production"
- ✅ Should have: `<link rel="stylesheet" href="...tailwind.min.css">`

### Performance Check
```bash
# Check file sizes
ls -lh assets/css/tailwind.min.css  # Should be ~50KB
                                      # (vs 250KB+ for CDN)

# Check page load waterfall
# Open DevTools > Network tab
# Verify tailwind.min.css loads from local server (not cdn.tailwindcss.com)
```

### CSS Functionality Test
Visit each page type and verify:
- ✅ Minimalist design is intact (gray palette)
- ✅ Cards have correct borders (`border-gray-200`)
- ✅ Buttons have correct styling (`bg-gray-900 hover:bg-gray-800`)
- ✅ Status badges show correct colors
- ✅ Layout and responsive design work

---

## Development Workflow (Local)

**No changes needed!** Development continues to use CDN:

```bash
# Just work normally - no build step required
# Local development automatically uses CDN
export APP_ENV=development  # or just don't set it
php -S localhost:8000
```

---

## Fallback Handling

If `tailwind.min.css` is missing in production, the system gracefully falls back:
```php
if (file_exists($css_file)) {
    // Use local CSS
} else {
    error_log('WARNING: tailwind.min.css not found. Falling back to CDN.');
    // Fallback to CDN (not ideal but prevents broken styling)
}
```

---

## Maintenance

### After Adding New Classes
If you add new Tailwind classes to your HTML:

1. **Development**: No action needed (CDN includes all)
2. **Production**: Must rebuild CSS to include new classes

```bash
# Rebuild production CSS after changes
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.min.css --minify
```

### Setting Up CI/CD
For automated builds, add to deployment script:
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.min.css --minify
```

---

## Troubleshooting

### Issue: Styles not loading in production
**Solution**: 
1. Verify `APP_ENV=production` is set
2. Check that `assets/css/tailwind.min.css` exists on server
3. Check browser DevTools Network tab for HTTP 404 errors

### Issue: Some classes missing in production
**Reason**: The CSS was built before those classes were added to templates
**Solution**: Rebuild CSS with `npx tailwindcss ...` command and redeploy

### Issue: Production site looks different than development
**Reason**: Development using CDN includes extra development utilities not in production build
**Solution**: This is expected. Test thoroughly in production to catch any issues.

---

## Configuration Files

- **`.env.example`** - Environment variable template
- **`config/config.php`** - Reads `APP_ENV` and provides `getTailwindCSS()` helper
- **`tailwind.config.js`** - Tailwind configuration (created after `npm init`)
- **`postcss.config.js`** - PostCSS configuration (auto-created by Tailwind)

---

## Related Documentation
- See `TAILWIND_PRODUCTION_SETUP.md` for implementation details and options
- See `.github/copilot-instructions.md` for general development patterns
