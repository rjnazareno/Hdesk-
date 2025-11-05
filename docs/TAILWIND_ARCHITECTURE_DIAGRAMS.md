# Implementation Architecture Diagram

## System Architecture - Tailwind CSS Loading

```
┌─────────────────────────────────────────────────────────────┐
│              Application Request Flow                       │
└─────────────────────────────────────────────────────────────┘

   Request to page (e.g., /login.php)
            ↓
   ┌──────────────────────────┐
   │ Check APP_ENV variable   │
   └──────────────────────────┘
            ↓
   ┌────────────────────────────────────────┐
   │ config/config.php: getTailwindCSS()    │
   └────────────────────────────────────────┘
            ↓
   ┌─────────────────────────────────────┐
   │ if ENVIRONMENT === 'production' ?   │
   └─────────────────────────────────────┘
       ↙                              ↘
    YES                                NO
     ↓                                 ↓
┌──────────────────────────┐   ┌──────────────────────────┐
│  PRODUCTION PATH         │   │  DEVELOPMENT PATH        │
│  ────────────────────    │   │  ────────────────────    │
│  Check for local CSS:    │   │  Return CDN script:      │
│  /assets/css/            │   │                          │
│  tailwind.min.css        │   │  <script src=             │
│                          │   │  "https://cdn.           │
│  If exists:              │   │  tailwindcss.com">        │
│  Return <link> tag       │   │  </script>               │
│  to local CSS            │   │                          │
│                          │   │  No build step needed    │
│  If missing:             │   │  Instant iteration       │
│  Fallback to CDN +       │   │                          │
│  log error               │   │                          │
└──────────────────────────┘   └──────────────────────────┘
     ↓                              ↓
     │                              │
     └──────────────┬───────────────┘
                    ↓
           ┌─────────────────────┐
           │ Return CSS          │
           │ (local or CDN)      │
           └─────────────────────┘
                    ↓
           ┌─────────────────────┐
           │ Page renders with   │
           │ proper styles       │
           └─────────────────────┘
```

---

## File Dependency Tree

```
index.php / login.php / admin pages
     ↓
views/layouts/header.php
     ↓
<?php echo getTailwindCSS(); ?>
     ↓
config/config.php
     ├─ define('ENVIRONMENT', getenv('APP_ENV') ?? 'development');
     └─ function getTailwindCSS() { ... }
          ├─ If production:
          │   └─ Return link to /assets/css/tailwind.min.css
          │
          └─ If development:
              └─ Return script tag for cdn.tailwindcss.com
```

---

## Environment Setup Comparison

### Development (Current / No Changes)

```
┌─────────────────────────────────────────────┐
│  Local Machine                              │
├─────────────────────────────────────────────┤
│  APP_ENV = not set (defaults to dev)        │
│                                             │
│  Page Request                               │
│    ↓                                        │
│  getTailwindCSS() called                    │
│    ↓                                        │
│  Returns: <script src="https://cdn...">    │
│    ↓                                        │
│  Browser downloads from CDN                 │
│    ↓                                        │
│  ✅ Page renders with styles               │
│  ✅ No build step required                 │
│  ✅ Instant changes take effect             │
│  ⚠️ Console: Can show warnings (OK here)   │
└─────────────────────────────────────────────┘
```

### Production (After Setup)

```
┌──────────────────────────────────────────────────┐
│  Production Server                               │
├──────────────────────────────────────────────────┤
│  Step 1: Build CSS                              │
│    npx tailwindcss -i input.css -o              │
│    assets/css/tailwind.min.css --minify          │
│    → Generates 50KB file                        │
│                                                 │
│  Step 2: Upload tailwind.min.css to server      │
│    → Placed at /var/www/ithelp/assets/css/      │
│                                                 │
│  Step 3: Set environment                       │
│    export APP_ENV=production                   │
│                                                 │
│  ─────────────────────────────────────────────  │
│                                                 │
│  Page Request                                  │
│    ↓                                           │
│  getTailwindCSS() called                       │
│    ↓                                           │
│  Checks ENVIRONMENT === 'production'           │
│    ↓                                           │
│  Looks for /assets/css/tailwind.min.css        │
│    ↓ (exists)                                  │
│  Returns: <link rel="stylesheet" href="...">  │
│    ↓                                           │
│  Browser loads local CSS file                  │
│    ↓                                           │
│  ✅ Page renders with styles                  │
│  ✅ 50KB file (5x smaller)                    │
│  ✅ No external CDN dependency                │
│  ✅ No console warning                        │
│  ✅ Faster page load                          │
└──────────────────────────────────────────────────┘
```

---

## Decision Flow Chart

```
                    New Environment Setup
                           ↓
                ┌──────────────────────────┐
                │ What's your use case?    │
                └──────────────────────────┘
                    ↙          ↙          ↖
                   /            \          \
            LOCAL DEV      TESTING        PRODUCTION
              ↓              ↓              ↓
        ┌─────────────┐ ┌─────────────┐ ┌──────────────┐
        │   Nothing   │ │  Optional:  │ │ Required:    │
        │   to do!    │ │  - Build    │ │ - Build CSS  │
        │             │ │    CSS      │ │ - Upload     │
        │ ✅ Keep     │ │  - Test     │ │ - Set env    │
        │    using    │ │   with      │ │   variable   │
        │    CDN      │ │   APP_ENV   │ │              │
        │             │ │   =prod     │ │ See: PROD_   │
        │ Default     │ │             │ │ DEPLOY...md  │
        │ behavior    │ │ (this file) │ │              │
        └─────────────┘ └─────────────┘ └──────────────┘
```

---

## Code Flow - getTailwindCSS() Function

```javascript
function getTailwindCSS() {
    // Step 1: Check environment
    if (ENVIRONMENT === 'production') {
        // Step 2a: Production path
        $css_file = __DIR__ . '/../assets/css/tailwind.min.css';
        
        // Step 3a: Check if file exists
        if (file_exists($css_file)) {
            // Step 4a: Return local CSS link
            return '<link rel="stylesheet" href="' . ASSETS_URL . 'css/tailwind.min.css">';
        } else {
            // Step 4b: File missing - log error and fallback
            error_log('WARNING: tailwind.min.css not found. Falling back to CDN.');
            return '<script src="https://cdn.tailwindcss.com"><\/script>';
        }
    } else {
        // Step 2b: Development path
        return '<script src="https://cdn.tailwindcss.com"><\/script>';
    }
}
```

---

## Integration Points

```
┌──────────────────────────────────────────────────────────┐
│            Page Request → Stylesheet Loading             │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  login.php                                              │
│  ├─ require_once 'config/config.php'                   │
│  └─ <?php echo getTailwindCSS(); ?>                    │
│       ↑                                                 │
│       └─ Calls function from config/config.php         │
│                                                        │
│  views/layouts/header.php (used by admin + customer)   │
│  ├─ require_once 'config/config.php'                   │
│  └─ <?php echo getTailwindCSS(); ?>                    │
│       ↑                                                 │
│       └─ Calls function from config/config.php         │
│                                                        │
│  admin/view_ticket.php                                 │
│  ├─ require_once 'config/config.php'                   │
│  └─ <?php echo getTailwindCSS(); ?>                    │
│       ↑                                                 │
│       └─ Calls function from config/config.php         │
│                                                        │
│  article.php                                           │
│  ├─ require_once 'config/config.php'                   │
│  └─ <?php echo getTailwindCSS(); ?>                    │
│       ↑                                                 │
│       └─ Calls function from config/config.php         │
│                                                        │
└──────────────────────────────────────────────────────────┘
       All funnel through ONE central function:
       config/config.php::getTailwindCSS()
```

---

## CSS File Size Comparison

```
Development (CDN)          Production (Local)
┌────────────────────┐    ┌────────────────────┐
│  Tailwind CDN      │    │  tailwind.min.css  │
│  ────────────────  │    │  ──────────────── │
│  ~250 KB total     │    │  ~50 KB total      │
│  (full utilities)  │    │  (tree-shaken)     │
│  + network latency │    │  (local file)      │
│  + JS parsing      │    │                    │
│  ────────────────  │    │  ────────────────  │
│  Load time: ~500ms │    │  Load time: ~50ms  │
└────────────────────┘    └────────────────────┘
  (varies by CDN)           (typical local)
```

---

## Summary

The implementation uses a **single centralized function** (`getTailwindCSS()`) that intelligently switches between CDN (development) and local CSS (production) based on the `APP_ENV` environment variable. This eliminates the production warning while maintaining zero-friction development experience.
