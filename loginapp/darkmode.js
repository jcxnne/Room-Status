// darkmode.js - Include this file in all your pages
// Add: <script src="darkmode.js"></script> before closing </body> tag

(function() {
  'use strict';

  // Check for saved dark mode preference on page load
  function initDarkMode() {
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    
    if (isDarkMode) {
      document.body.classList.add('dark');
    }
    
    // Update icon and text after page loads
    updateDarkModeUI(isDarkMode);
  }

  // Update the toggle button UI
  function updateDarkModeUI(isDark) {
    const toggle = document.getElementById('darkModeToggle');
    if (!toggle) return;

    const icon = toggle.querySelector('i');
    const modeText = toggle.querySelector('span');

    if (icon) {
      icon.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
      // Re-render icons
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    }

    if (modeText) {
      modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    }
  }

  // Toggle dark mode
  function toggleDarkMode(e) {
    e.preventDefault();

    const body = document.body;
    const isDark = body.classList.toggle('dark');

    // Save preference to localStorage
    localStorage.setItem('darkMode', isDark);

    // Update UI
    updateDarkModeUI(isDark);
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    });
  } else {
    // DOM already loaded
    initDarkMode();
    
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
      toggle.addEventListener('click', toggleDarkMode);
    }
  }
})();