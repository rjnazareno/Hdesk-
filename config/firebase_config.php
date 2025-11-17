<?php
/**
 * Firebase Configuration
 * FCM Push Notification Settings - ResolveIT
 * 
 * Firebase Project: resolveit-417da
 * Created: November 17, 2025
 */

// Firebase Cloud Messaging Configuration
// NOTE: Get Server Key from Firebase Console → Project Settings → Cloud Messaging → Server key
define('FCM_SERVER_KEY', 'YOUR_FCM_SERVER_KEY_HERE'); // Will get from Cloud Messaging settings
define('FCM_SENDER_ID', '14301030590');

// Firebase Web Configuration
$firebaseConfig = [
    'apiKey' => "AIzaSyD-vvCkZbG7fKwwH4QSVReSEJSBPWAUZ_4",
    'authDomain' => "resolveit-417da.firebaseapp.com",
    'projectId' => "resolveit-417da",
    'storageBucket' => "resolveit-417da.firebasestorage.app",
    'messagingSenderId' => "14301030590",
    'appId' => "1:14301030590:web:cf4900b203385add4256ef",
    'measurementId' => "G-GDC09XTMMP"
];

// FCM API Endpoint
define('FCM_API_URL', 'https://fcm.googleapis.com/fcm/send');

// VAPID Public Key (For web push)
// NOTE: Get from Firebase Console → Project Settings → Cloud Messaging → Web Push certificates
define('FCM_VAPID_PUBLIC_KEY', 'BO3LtJTs6d9JzKVhNWIaz6wKbptPkvGfALQa5MLGLEnhB92leeLMO6sNIRbv4RyGpUAB5Zg4pPYyRe8eIoP_UXY');

/**
 * Export as JSON for JavaScript
 */
function getFirebaseConfigJSON() {
    global $firebaseConfig;
    return json_encode($firebaseConfig);
}

