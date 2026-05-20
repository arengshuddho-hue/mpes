// assets/js/dashboard.js — shared dashboard JS (theme, sidebar)
// Handles interactive elements specific to the dashboard layout.

document.addEventListener('DOMContentLoaded', () => {

    // ── Theme switcher ──────────────────────────────────────
    // Initializes and manages the dashboard-specific theme selection UI
    const html = document.documentElement;
    // Retrieve previously saved theme preference or default to 'light'
    const saved = localStorage.getItem('mpes-theme') || 'light';
    html.setAttribute('data-theme', saved);

    // Apply active state to the currently selected theme button
    document.querySelectorAll('.theme-btn').forEach(btn => {
        const t = btn.getAttribute('data-set-theme');
        if(t === saved) btn.classList.add('active'); // Highlight active button
        
        // Handle theme change on click
        btn.addEventListener('click', () => {
            // Update the DOM to reflect new theme
            html.setAttribute('data-theme', t);
            // Persist the choice
            localStorage.setItem('mpes-theme', t);
            
            // Remove 'active' class from all buttons and apply to the clicked one
            document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // ── Close modals on overlay click ────────────────────────
    // Allows users to close popups/modals by clicking outside the modal box
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            // Ensure the click was actually on the background overlay, not the modal content itself
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
});
