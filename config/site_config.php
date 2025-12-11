<?php
/**
 * Site Configuration
 * Update SERVER_IP with your computer's local network IP address
 */

// Get server IP automatically (works for local network access)
// Option 1: Use your actual local network IP (most reliable for phones)
// Find your IP: Run 'ipconfig' in cmd and use the IPv4 address (e.g., 192.168.1.xxx)
// define('SERVER_IP', $_SERVER['SERVER_ADDR'] ?? '10.139.97.30'); // Default to detected IP

// Option 2: Or set it manually if auto-detection doesn't work
define('SERVER_IP', '172.16.5.91'); // Replace with your actual IP

// Base URL for the site
define('BASE_URL', 'http://' . SERVER_IP . '/pw/projet_web');

// Verification URL for QR codes
define('VERIFY_TICKET_URL', BASE_URL . '/view/front/verify_ticket.php');
