// assets/js/main.js
// This script handles global interactivity such as theme switching and multi-step forms (e.g., login).

document.addEventListener('DOMContentLoaded', () => {
    // ---------------------------------------------------------
    // Theme Switcher Logic
    // Manages transitioning between Light, Dark, and Colorblind modes
    // ---------------------------------------------------------
    const themeButtons = document.querySelectorAll('.theme-btn');
    const htmlElement = document.documentElement;

    // Load saved theme from browser local storage to maintain user preference across sessions
    const savedTheme = localStorage.getItem('mpes-theme') || 'light';
    htmlElement.setAttribute('data-theme', savedTheme);

    // Attach click event listeners to all theme toggle buttons
    themeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Get the target theme from the button's data attribute
            const theme = btn.getAttribute('data-set-theme');
            // Apply the theme to the HTML root element
            htmlElement.setAttribute('data-theme', theme);
            // Save the selection for future visits
            localStorage.setItem('mpes-theme', theme);
        });
    });

    // ---------------------------------------------------------
    // Multi-Step Form Logic (Primarily used in Login/Registration)
    // ---------------------------------------------------------
    const steps = document.querySelectorAll('.login-step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    // Tracks the current active step in the multi-step form (0-indexed)
    let currentStep = 0;

    /**
     * Updates the UI to display the specified step while hiding others.
     * Includes a subtle slide-up animation for smoother transitions.
     * @param {number} index - The index of the step to show
     */
    function showStep(index) {
        steps.forEach((step, i) => {
            if (i === index) {
                // Activate the target step
                step.classList.add('active');
                // Apply inline styles for entrance animation
                step.style.opacity = '0';
                step.style.transform = 'translateY(10px)';
                // Trigger CSS transition
                setTimeout(() => {
                    step.style.opacity = '1';
                    step.style.transform = 'translateY(0)';
                }, 10);
            } else {
                // Deactivate all other steps
                step.classList.remove('active');
            }
        });
    }

    // Handle "Next" button clicks to progress through the form
    nextButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Check if there is a next step available before advancing
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        });
    });

    // Handle "Previous" button clicks to go back in the form
    prevButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Check if we are not on the first step before going back
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        });
    });

    // Initialize the form by showing the first step if any steps exist
    if(steps.length > 0) {
        showStep(currentStep);
    }
});
