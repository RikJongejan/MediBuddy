/**
 * MediBuddy Theme & Password Visibility
 * Only handles theme switching and password visibility
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize theme
    initTheme();
    
    // Initialize password toggles
    setupPasswordToggles();
    
    // Theme management function
    function initTheme() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        
        if (!themeToggle || !themeIcon) return;
        
        // Create overlay for transitions
        const overlay = document.createElement('div');
        overlay.className = 'theme-transition-overlay';
        document.body.appendChild(overlay);
        
        // Initialize theme based on saved preference or system setting
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
        
        // Toggle theme on click
        themeToggle.addEventListener('click', function() {
            const isDark = document.body.classList.contains('dark-theme');
            const newTheme = isDark ? 'light' : 'dark';
            
            // Animation classes
            themeToggle.classList.add('animate');
            overlay.classList.add('active');
            document.body.classList.add('theme-transitioning');
            
            setTheme(newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Remove animation classes after transition
            setTimeout(() => {
                themeToggle.classList.remove('animate');
                overlay.classList.remove('active');
                document.body.classList.remove('theme-transitioning');
            }, 500);
        });
        
        // Listen to system theme changes if no manual preference is set
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }
    
    // Set theme and update icon
    function setTheme(theme) {
        const themeIcon = document.getElementById('theme-icon');
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
            if (themeIcon) themeIcon.textContent = '☀️';
        } else {
            document.body.classList.remove('dark-theme');
            if (themeIcon) themeIcon.textContent = '🌙';
        }
    }
    
    // Set up password visibility toggles for better usability
    function setupPasswordToggles() {
        document.querySelectorAll('[id$="password"]').forEach(input => {
            const toggleBtn = input.nextElementSibling;
            if (toggleBtn && toggleBtn.classList.contains('toggle-password')) {
                toggleBtn.addEventListener('click', () => {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    toggleBtn.textContent = type === 'password' ? '👁️' : '🔒';
                });
            }
        });
    }
});
