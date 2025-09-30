# 🔥 Firebase Setup Guide - Get Your Keys

## Step 1: Get VAPID Key (for Web Push)

1. **Open Firebase Console:**
   - Go to https://console.firebase.google.com/
   - Select your project: `rssticket-a8d0a`

2. **Navigate to Cloud Messaging:**
   - Click on ⚙️ Settings (gear icon) in left sidebar
   - Select "Project settings"
   - Click on "Cloud Messaging" tab at the top

3. **Generate VAPID Key:**
   - Scroll down to "Web configuration"
   - Under "Web Push certificates", click "Generate key pair"
   - Copy the generated VAPID key (starts with "B...")

4. **Update Firebase Config:**
   - Open `assets/js/firebase-notifications.js`
   - Find line: `const vapidKey = 'BLC_your_vapid_key_here';`
   - Replace with your actual VAPID key

## Step 2: Get Server Key (for Backend)

1. **Still in Firebase Console:**
   - Stay in Project Settings → Cloud Messaging
   - Find "Project credentials" section
   - Copy the "Server key" (long string starting with "AAAA...")

2. **Update Server Config:**
   - Open `includes/firebase_notifications.php`  
   - Find line: `$this->serverKey = 'YOUR_FIREBASE_SERVER_KEY_HERE';`
   - Replace with your actual server key

## Step 3: Test the Setup

1. **Visit Your Ticket Page:**
   - Go to any ticket (e.g., `view_ticket.php?id=1`)
   - Check browser console for Firebase initialization messages

2. **Grant Notification Permission:**
   - Browser should prompt for notification permission
   - Click "Allow" to enable push notifications

3. **Test Real-Time Messaging:**
   - Open ticket in two browser windows
   - Send a message from one window
   - Should appear instantly in both windows + show notification

4. **Test Status Change Notifications:**
   - Change a ticket status
   - Should trigger notification to affected users

---

## Example Configuration Files:

### After getting VAPID key, update `firebase-notifications.js`:
```javascript
const vapidKey = 'BCiJHPL9fndBDdOA_your_actual_vapid_key_here_xyz123';
```

### After getting Server key, update `firebase_notifications.php`:
```php
$this->serverKey = 'AAAAAbCdEfG:APA91bF_your_actual_server_key_here_xyz123';
```

---

## 🔧 Troubleshooting:

### If notifications don't work:
1. **Check browser console** for Firebase errors
2. **Verify keys** are correctly copied (no extra spaces)
3. **Check notification permission** in browser settings
4. **Test with debug page:** `firebase-debug.php`

### Common Issues:
- **VAPID key format:** Should start with "B" and be ~88 characters long
- **Server key format:** Should start with "AAAA" and be ~152+ characters long
- **Permission denied:** Make sure notification permission is granted
- **CORS errors:** Make sure you're accessing via localhost, not file://

---

## 🚀 Next Steps After Setup:

1. **Test all notification types:**
   - New replies (employee ↔ IT staff)
   - Status changes (close, reopen, etc.)
   - New ticket creation (employee → IT staff)

2. **Customize notification preferences:**
   - Users can control what notifications they receive
   - Set active hours for notifications
   - Choose delivery methods (browser, email, etc.)

3. **Monitor notification delivery:**
   - Check PHP error logs for Firebase API responses
   - Use browser dev tools to debug service worker
   - Test on different devices/browsers

---

Ready to get your Firebase keys? Let me know if you need help with any specific step! 🔥