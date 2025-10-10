// COMPLETE NOTIFICATION DROPDOWN DIAGNOSTIC
// Add this to your dashboard temporarily to debug

console.log('=== COMPLETE DIAGNOSTIC START ===');

// Wait for everything to load
setTimeout(function() {
    console.log('\n--- 1. CHECKING DROPDOWN ELEMENT ---');
    const dropdown = document.getElementById('notifications-dropdown');
    console.log('Dropdown exists:', !!dropdown);
    
    if (dropdown) {
        console.log('Dropdown element:', dropdown);
        console.log('Dropdown innerHTML length:', dropdown.innerHTML.length);
        console.log('Dropdown parent:', dropdown.parentElement?.tagName);
        
        console.log('\n--- 2. CHECKING STYLES ---');
        const computed = window.getComputedStyle(dropdown);
        console.log('Position:', computed.position);
        console.log('Display:', computed.display);
        console.log('Visibility:', computed.visibility);
        console.log('Opacity:', computed.opacity);
        console.log('Z-index:', computed.zIndex);
        console.log('Top:', computed.top);
        console.log('Right:', computed.right);
        console.log('Left:', computed.left);
        console.log('Width:', computed.width);
        console.log('Height:', computed.height);
        console.log('Background:', computed.backgroundColor);
        console.log('Border:', computed.border);
        
        console.log('\n--- 3. CHECKING POSITION ---');
        const rect = dropdown.getBoundingClientRect();
        console.log('BoundingClientRect:', {
            top: rect.top,
            right: rect.right,
            bottom: rect.bottom,
            left: rect.left,
            width: rect.width,
            height: rect.height
        });
        console.log('Is on screen:', {
            top: rect.top >= 0 && rect.top <= window.innerHeight,
            left: rect.left >= 0 && rect.left <= window.innerWidth,
            visible: rect.width > 0 && rect.height > 0
        });
        
        console.log('\n--- 4. CHECKING CLASSES ---');
        console.log('classList:', Array.from(dropdown.classList));
        console.log('Has hidden class:', dropdown.classList.contains('hidden'));
        
        console.log('\n--- 5. MANUAL VISIBILITY TEST ---');
        // Force make it visible
        dropdown.style.position = 'fixed';
        dropdown.style.top = '100px';
        dropdown.style.right = '20px';
        dropdown.style.zIndex = '99999';
        dropdown.style.display = 'block';
        dropdown.style.visibility = 'visible';
        dropdown.style.opacity = '1';
        dropdown.style.width = '384px';
        dropdown.style.backgroundColor = 'red';
        dropdown.style.border = '10px solid yellow';
        dropdown.style.padding = '20px';
        dropdown.classList.remove('hidden');
        
        console.log('✅ FORCED dropdown to be visible at top:100px, right:20px with RED background and YELLOW border');
        console.log('If you still cant see it, there is a browser/system issue!');
    } else {
        console.error('❌ DROPDOWN DOES NOT EXIST IN DOM!');
        console.log('All divs with IDs:', Array.from(document.querySelectorAll('div[id]')).map(d => d.id));
    }
    
    console.log('\n--- 6. CHECKING BUTTON ---');
    const button = document.querySelector('[title="Notifications"]') || 
                   document.querySelector('.fa-bell')?.closest('button');
    console.log('Button exists:', !!button);
    if (button) {
        const buttonRect = button.getBoundingClientRect();
        console.log('Button position:', buttonRect);
    }
    
}, 2000);

console.log('=== DIAGNOSTIC WILL RUN IN 2 SECONDS ===');
