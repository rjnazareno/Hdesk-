# 🧪 Firebase Photo Notifications - Testing Guide

## 🚀 **Step-by-Step Testing Process**

### **📋 Pre-Test Setup (Local):**
1. **Start your local server** (XAMPP/WAMP)
2. **Open browser**: `http://localhost/IThelp`
3. **Enable notifications** when prompted

### **🔧 Test 1: Enable FCM Notifications**
```
Visit: http://localhost/IThelp/dashboard.php
```
- Click **"Enable Notifications"** button
- Allow browser permissions when prompted
- Check console for FCM token registration
- **Expected:** Token saved to database

### **🔍 Test 2: Verify Token Storage**
```
Visit: http://localhost/IThelp/check-fcm-tokens.php
```
- **Expected:** See your FCM token in the table
- Note the `user_id` and `user_type` 
- Status should show "Active"

### **📸 Test 3: Photo Notification Demo**
```
Visit: http://localhost/IThelp/test-photo-notifications.php
```
This will test:
- ✅ New reply notification with user photo
- ✅ Status change notification with IT avatar  
- ✅ Custom photo notification with UI Avatars
- **Expected:** See notification payloads in browser

### **💬 Test 4: Real Chat Notification**
1. **Open ticket view**: `view_ticket.php?id=[ticket_id]`
2. **Send a chat message** as employee or IT staff
3. **Expected:** 
   - Message appears instantly via Firebase
   - Push notification sent to other party
   - Photo appears in notification

### **📱 Test 5: Browser Notification**
After sending message/reply:
- **Desktop:** Notification appears in system tray
- **Mobile:** Push notification on lock screen  
- **Photo visible:** User avatar in notification
- **Actions available:** Reply, View, Dismiss buttons

---

## 🌐 **Live Server Testing**

### **After Git Push + Setup:**

### **🔧 Test 1: Live FCM Setup**
```
Visit: https://ithelp.resourcestaffonline.com/IThelp/create-fcm-tables.php
```
- Creates database tables on live server
- **Expected:** "Tables created successfully"

### **📱 Test 2: Live Notification Enable**
```
Visit: https://ithelp.resourcestaffonline.com/IThelp/dashboard.php
```
- Click "Enable Notifications"  
- **Expected:** Service worker registered from domain root

### **🔍 Test 3: Live Token Check**
```
Visit: https://ithelp.resourcestaffonline.com/IThelp/check-fcm-tokens.php
```
- **Expected:** Live FCM tokens stored properly

### **📸 Test 4: Live Photo Notifications**
```
Visit: https://ithelp.resourcestaffonline.com/IThelp/test-photo-notifications.php
```
- Tests photo notifications on live server
- **Expected:** Photos load from live URLs

---

## 🐛 **Debugging Common Issues**

### **❌ "No FCM Token Found"**
**Solution:**
1. Clear browser cache
2. Visit dashboard.php 
3. Click "Enable Notifications" again
4. Check browser console for errors

### **❌ "Service Worker 404"**
**Solution:**
1. Ensure `firebase-messaging-sw.js` is in domain root
2. Check: `https://yourdomain.com/firebase-messaging-sw.js`
3. Copy file to correct location

### **❌ "Photos Not Loading"**
**Solution:**
1. Check network tab for image 404s
2. Verify UI Avatars URL works:
   ```
   https://ui-avatars.com/api/?name=Test+User&size=200&background=0D8ABC&color=fff
   ```
3. Check profile photo folder permissions

### **❌ "Notifications Not Sending"**
**Solution:**
1. Check `test-photo-notifications.php` for errors
2. Verify Firebase server key is working
3. Check browser console for FCM errors
4. Ensure user has notification permissions enabled

---

## 📊 **Expected Test Results**

### **✅ Successful Tests Show:**
- FCM tokens saved to database
- Service worker registered successfully  
- Photo URLs generated correctly
- Notifications display with images
- Real-time chat working without polling
- Browser notifications appear with photos

### **🎯 Performance Indicators:**
- **No 2-30 second polling** in network tab
- **Firebase real-time listeners** active
- **Instant message delivery** 
- **Photo notifications** with user avatars
- **95% reduction** in server requests

---

## 🔧 **Quick Test Commands**

### **Test FCM Token:**
```javascript
// Browser console:
console.log(window.firebaseNotifications?.token);
```

### **Test Service Worker:**
```javascript  
// Browser console:
navigator.serviceWorker.getRegistrations().then(console.log);
```

### **Test Photo Generation:**
```
Visit: https://ui-avatars.com/api/?name=Your+Name&size=200&background=0D8ABC&color=fff&bold=true
```

Ready to test? Start with the local tests first! 🚀