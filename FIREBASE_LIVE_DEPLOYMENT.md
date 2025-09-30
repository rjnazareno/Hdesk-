# 🚀 Firebase Live Server Deployment Guide

## Files to Upload to Live Server Root

### 1. Service Worker (CRITICAL)
Upload `firebase-messaging-sw.js` to the **ROOT** of your domain:
```
https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js
```

### 2. Firebase PHP Files
Upload to your IThelp folder:
- `includes/firebase_notifications.php`
- `config/firebase_service_account.php`
- `api/save_fcm_token.php` (may already exist)

### 3. Firebase JavaScript Files
Upload to your IThelp/assets/js/ folder:
- `assets/js/firebase-config.js`
- `assets/js/firebase-notifications.js`

### 4. Database Tables
Run this on your live server:
- Upload and run `create-fcm-tables.php`

## Quick Fix for Immediate Testing

If you want to test quickly without uploading files, you can temporarily use a CDN-hosted service worker.

Update your Firebase config to use a different service worker approach.

## Step-by-Step Upload Process

1. **Upload Service Worker to ROOT:**
   - Upload `firebase-messaging-sw.js` to `/home/u816220874/domains/ithelp.resourcestaffonline.com/public_html/`
   - NOT inside the IThelp folder - it needs to be at the domain root

2. **Upload Firebase Files:**
   - Upload all Firebase PHP and JS files to your IThelp folder

3. **Create Database Tables:**
   - Visit: `https://ithelp.resourcestaffonline.com/IThelp/create-fcm-tables.php`

4. **Test:**
   - Visit: `https://ithelp.resourcestaffonline.com/IThelp/check-fcm-tokens.php`

## File Locations Summary
```
https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js (ROOT)
https://ithelp.resourcestaffonline.com/IThelp/includes/firebase_notifications.php
https://ithelp.resourcestaffonline.com/IThelp/config/firebase_service_account.php
https://ithelp.resourcestaffonline.com/IThelp/assets/js/firebase-config.js
https://ithelp.resourcestaffonline.com/IThelp/assets/js/firebase-notifications.js
```