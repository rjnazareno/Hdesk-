# Firebase Cloud Messaging (FCM) Setup & Testing Guide

## ✅ Completed Setup

### 1. Firebase Project Configuration
- **Project ID**: resolveit-417da
- **VAPID Key**: BO3LtJTs6d9JzKVhNWIaz6wKbptPkvGfALQa5MLGLEnhB92leeLMO6sNIRbv4RyGpUAB5Zg4pPYyRe8eIoP_UXY
- **Service Account**: Configured in `config/firebase-service-account.json`
- **API**: Using Firebase Admin SDK V1 (modern, secure)

### 2. Files Created
- ✅ `config/firebase_config.php` - Firebase configuration
- ✅ `config/firebase-service-account.json` - Service account credentials
- ✅ `assets/js/firebase-init.js` - Frontend initialization
- ✅ `firebase-messaging-sw.js` - Service worker for background notifications
- ✅ `api/save_fcm_token.php` - Token storage endpoint
- ✅ `includes/FCMNotification.php` - PHP helper class with modern V1 API
- ✅ `composer.json` - Added `kreait/firebase-php` v7.23.0

### 3. Integration Points
- ✅ Customer dashboard has Firebase SDK loaded
- ✅ Service worker registered in root directory
- ✅ Auto-requests permission after 3 seconds
- ✅ Custom notification prompt with glass morphism design
- ✅ Token auto-saved to database on permission grant

---

## 🧪 Testing Checklist

### Phase 1: Permission & Token (Do This First!)
1. **Open Customer Dashboard**
   - Go to: https://resolveit.resourcestaffonline.com/customer/dashboard.php
   - Login as employee: `john.doe` / `admin123`

2. **Check Console**
   - Press F12 → Console tab
   - Should see: `✅ Firebase initialized successfully`
   - Should see: `✅ Firebase Cloud Messaging initialized`

3. **Grant Permission**
   - After 3 seconds, notification prompt appears (glass morphism design)
   - Click: **"Enable"** button
   - Browser will ask: "Allow notifications?" → Click **Allow**

4. **Verify Token Saved**
   - Console should show: `✅ FCM Token: ey...` (long string)
   - Console should show: `✅ FCM token saved to server`

5. **Check Database**
   ```sql
   SELECT id, fname, lname, fcm_token 
   FROM employees 
   WHERE username = 'john.doe';
   ```
   - `fcm_token` column should have value (not NULL)

---

### Phase 2: Test Notification (Next Step)

**Option A: Manual Test (Quick)**
1. Go to: Firebase Console → Cloud Messaging → Send Test Message
2. Enter your FCM token from database
3. Title: "Test Notification"
4. Body: "This is a test from Firebase Console"
5. Click: Send test message
6. **Expected**: Notification appears on your device

**Option B: Test with Ticket Creation (Full Integration - After Task #5)**
1. Login as employee (`john.doe`)
2. Create a new ticket
3. **Expected**: All IT staff receive push notification
4. Click notification → Opens ticket page

---

## 📋 Current Status

### ✅ Completed (Tasks 1-4)
- [x] Employee profile & password change
- [x] Profile navigation added
- [x] Firebase project created
- [x] Firebase Admin SDK installed
- [x] VAPID key configured
- [x] Service account integrated
- [x] Frontend initialized
- [x] Service worker created
- [x] Token storage API endpoint

### 🚧 Pending (Tasks 5-6)
- [ ] **Task #5**: Integrate FCM with ticket events
  - Modify `TicketController` to send notifications on:
    - Ticket created → Notify IT staff
    - Ticket assigned → Notify assigned user
    - Status changed → Notify submitter
  
- [ ] **Task #6**: Full testing & deployment
  - Test permission flow
  - Test background notifications
  - Test notification click actions
  - Verify on production

---

## 🔧 Technical Details

### How It Works

**1. Frontend Flow:**
```javascript
// firebase-init.js
1. Load Firebase SDK (10.7.0)
2. Initialize Firebase app with config
3. Request notification permission
4. Get FCM token from Firebase
5. Save token to server via API
6. Listen for foreground messages
```

**2. Backend Flow:**
```php
// FCMNotification.php
1. Load Firebase Admin SDK
2. Create CloudMessage with notification
3. Send via messaging->send($message)
4. Firebase delivers to device
```

**3. Service Worker:**
```javascript
// firebase-messaging-sw.js
1. Runs in background (even when browser closed)
2. Receives push events from Firebase
3. Shows notification with custom UI
4. Handles notification clicks
5. Opens correct ticket page
```

### Database Schema
```sql
-- Auto-created when token is saved
ALTER TABLE employees ADD COLUMN fcm_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN fcm_token VARCHAR(255) NULL;
```

### API Endpoint
```
POST /api/save_fcm_token.php
Headers: Session cookie (authentication)
Body: {
    "token": "ey...",
    "device_type": "web",
    "browser": "Mozilla/5.0..."
}
Response: {
    "success": true,
    "user_type": "employee"
}
```

---

## 🔐 Security Notes

1. **Service Account JSON**: 
   - Contains private key
   - Located in `config/` (outside web root would be better)
   - DO NOT commit to public repos
   - Already in `.gitignore` (should be)

2. **VAPID Key**:
   - Public key (safe to expose in frontend)
   - Used for web push authentication

3. **FCM Tokens**:
   - Stored per device/browser
   - Auto-refresh when expired
   - Deleted on logout (TODO: implement)

---

## 🐛 Troubleshooting

### Permission Not Requested
- Check: Browser supports notifications (Chrome, Firefox, Edge)
- Check: HTTPS required (localhost works too)
- Check: Console for errors

### Token Not Saved
- Check: User logged in (session active)
- Check: `fcm_token` column exists in database
- Check: `/api/save_fcm_token.php` response in Network tab

### Notification Not Received
- Check: Permission granted (not denied/blocked)
- Check: FCM token in database
- Check: Service worker registered (`chrome://serviceworker-internals`)
- Check: Firebase project credentials correct

### Firebase Not Initialized
- Check: Firebase SDK loaded (10.7.0)
- Check: `firebase-init.js` loaded
- Check: Console for initialization errors

---

## 📝 Next Steps (Task #5)

1. **Open**: `controllers/admin/TicketController.php`
2. **Find**: `createTicket()` method
3. **Add** after ticket creation:
   ```php
   // Send FCM notification
   require_once __DIR__ . '/../../includes/FCMNotification.php';
   $fcm = new FCMNotification();
   $fcm->notifyTicketCreated(
       $ticketId, 
       $ticketNumber, 
       $submitterName, 
       $category
   );
   ```

4. **Repeat** for:
   - `assignTicket()` → `$fcm->notifyTicketAssigned(...)`
   - `updateStatus()` → `$fcm->notifyTicketStatusChanged(...)`

---

## 📚 References

- Firebase Admin SDK: https://firebase.google.com/docs/admin/setup
- Cloud Messaging: https://firebase.google.com/docs/cloud-messaging
- Service Workers: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API
- Web Push Protocol: https://web.dev/push-notifications/

---

**Last Updated**: November 17, 2025
**Status**: FCM Setup Complete ✅ | Integration Pending 🚧
