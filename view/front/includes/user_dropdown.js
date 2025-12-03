/**
 * User Dropdown Menu JavaScript
 * 
 * Handles the dropdown menu toggle functionality
 * Include this script at the bottom of your page or in your main JS file
 */

document.addEventListener('DOMContentLoaded', function () {
    const userDropdown = document.getElementById('userDropdown');

    if (userDropdown) {
        const usernameDisplay = userDropdown.querySelector('.username-display');

        // Toggle dropdown on click
        usernameDisplay.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('active');
            }
        });
    }
});
