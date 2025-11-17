    <!-- Common Scripts -->
    <script src="<?php echo $baseUrl ?? '../'; ?>assets/js/helpers.js"></script>
    <script src="<?php echo $baseUrl ?? '../'; ?>assets/js/notifications.js"></script>
    
    <?php if (isset($includeFirebase) && $includeFirebase): ?>
    <!-- Firebase Initialization -->
    <script src="<?php echo $baseUrl ?? '../'; ?>assets/js/firebase-init.js"></script>
    <script>
        // Set logged in status for Firebase
        document.body.dataset.userLoggedIn = '<?php echo isLoggedIn() ? 'true' : 'false'; ?>';
    </script>
    <?php endif; ?>
    
    <?php if (isset($customScripts)): ?>
    <?php echo $customScripts; ?>
    <?php endif; ?>
    
    <!-- Common Functions -->
    <script>
        // Greeting based on time
        function updateGreeting() {
            const hour = new Date().getHours();
            let greeting = 'Good Evening';
            
            if (hour < 12) greeting = 'Good Morning';
            else if (hour < 18) greeting = 'Good Afternoon';
            
            const greetingEl = document.getElementById('greetingText');
            if (greetingEl) greetingEl.textContent = greeting;
        }
        
        // Update current date
        function updateCurrentDate() {
            const dateEl = document.getElementById('currentDate');
            if (dateEl) {
                const options = { weekday: 'short', month: 'short', day: 'numeric' };
                dateEl.textContent = new Date().toLocaleDateString('en-US', options);
            }
        }
        
        // Format last login time
        function updateLastLogin() {
            const lastLoginEl = document.getElementById('lastLoginDisplay');
            if (lastLoginEl && typeof lastLogin !== 'undefined') {
                const loginDate = new Date(lastLogin);
                const now = new Date();
                const diffMs = now - loginDate;
                const diffMins = Math.floor(diffMs / 60000);
                
                let displayText = 'Last login: ';
                if (diffMins < 1) displayText += 'Just now';
                else if (diffMins < 60) displayText += diffMins + ' minutes ago';
                else if (diffMins < 1440) displayText += Math.floor(diffMins / 60) + ' hours ago';
                else displayText += Math.floor(diffMins / 1440) + ' days ago';
                
                lastLoginEl.textContent = displayText;
            }
        }
        
        // Dark mode toggle
        function initDarkMode() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            const darkModeIcon = document.getElementById('dark-mode-icon');
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', () => {
                    document.body.classList.toggle('dark-mode');
                    if (document.body.classList.contains('dark-mode')) {
                        darkModeIcon.classList.remove('fa-moon');
                        darkModeIcon.classList.add('fa-sun');
                        localStorage.setItem('darkMode', 'enabled');
                    } else {
                        darkModeIcon.classList.remove('fa-sun');
                        darkModeIcon.classList.add('fa-moon');
                        localStorage.setItem('darkMode', 'disabled');
                    }
                });
                
                // Check saved preference
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.body.classList.add('dark-mode');
                    darkModeIcon.classList.remove('fa-moon');
                    darkModeIcon.classList.add('fa-sun');
                }
            }
        }
        
        // Dropdown management
        function initDropdowns() {
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                // Find dropdown MENUS (elements ending with "Menu" not "Dropdown")
                const dropdownMenus = document.querySelectorAll('[id$="Menu"]');
                dropdownMenus.forEach(menu => {
                    // Get the parent container (the one with "Dropdown" ID)
                    const container = menu.closest('[id$="Dropdown"]');
                    if (container && !container.contains(event.target)) {
                        menu.classList.add('hidden');
                    }
                });
            });
        }
        
        // Toggle dropdown
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Print function
        function printPage() {
            window.print();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateGreeting();
            updateCurrentDate();
            updateLastLogin();
            initDarkMode();
            initDropdowns();
            
            // Update time every minute
            setInterval(updateCurrentDate, 60000);
        });
    </script>
</body>
</html>
