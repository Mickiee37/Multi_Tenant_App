/**
 * Theme Support Helper
 * Ensures theme changes take effect and fixes UI elements
 */
(function() {
    // Fix select dropdown styling for all themes
    function fixSelectDropdowns() {
        // Get current theme from URL or data attribute
        const urlParams = new URLSearchParams(window.location.search);
        const currentTheme = urlParams.get('force_theme') || document.body.dataset.theme || 'default';
        
        // Get all select elements
        const selects = document.querySelectorAll('select');
        
        selects.forEach(select => {
            // Add theme-specific class
            select.classList.add(`theme-${currentTheme}`);
            
            // For dark theme, add special styling
            if (currentTheme === 'dark') {
                // Create a style element if it doesn't exist
                if (!document.getElementById('dropdown-fix-style')) {
                    const style = document.createElement('style');
                    style.id = 'dropdown-fix-style';
                    style.innerHTML = `
                        select.theme-dark option {
                            background-color: #2c3034 !important;
                            color: #f8f9fa !important;
                        }
                        .dropdown-menu {
                            background-color: #2c3034 !important;
                            color: #f8f9fa !important;
                        }
                        .dropdown-item {
                            color: #f8f9fa !important;
                        }
                        .dropdown-item:hover {
                            background-color: #495057 !important;
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        });
        
        // Listen for dropdown open events
        document.addEventListener('click', function(event) {
            if (event.target.tagName === 'SELECT') {
                // Add the current theme as a class to the containing form
                const form = event.target.closest('form');
                if (form) {
                    form.classList.add(`theme-form-${currentTheme}`);
                }
            }
        });
    }

    // Fix dropdown visibility when page loads
    document.addEventListener('DOMContentLoaded', fixSelectDropdowns);
    
    // Also run immediately in case DOM is already loaded
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(fixSelectDropdowns, 1);
    }
    
    // Check if we have a theme update flag in URL
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('theme_refresh') || urlParams.has('force_theme')) {
        console.log("Theme refresh parameter detected");
        
        // Mark body with current theme
        const theme = urlParams.get('force_theme') || 'default';
        document.body.dataset.theme = theme;
        
        // Force strict no-cache to the server
        fetch(window.location.pathname, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            },
            credentials: 'same-origin' // Include cookies
        }).then(() => {
            console.log("Forced cache refresh complete");
        }).catch(error => {
            console.error("Error during cache refresh:", error);
        });
    }

    // Listen for theme changes through a custom event
    document.addEventListener('themeChanged', function(e) {
        console.log('Theme changed to:', e.detail.theme);
        // You could add additional theme adjustment logic here
    });
})(); 