<?php
/**
 * Email Configuration for FoxUnity Newsletter System
 * 
 * This file contains email settings for sending newsletter notifications
 * via XAMPP's sendmail to Gmail SMTP.
 */

return [
    // Email delivery method: 'phpmailer' (recommended) or 'mail' (requires XAMPP config)
    'method' => 'phpmailer',
    
    // Gmail SMTP Configuration for PHPMailer
    'gmail' => [
        'username' => 'rayenkabar780@gmail.com',
        'password' => 'ootzdictxgxiyrun', // App password (no spaces)
        'from_email' => 'rayenkabar780@gmail.com',
        'from_name' => 'FoxUnity News'
    ],
    
    // SMTP Configuration
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls', // or 'ssl' for port 465
        'auth' => true,
    ],
    
    // From address (sender)
    'from' => [
        'address' => 'noreply@foxunity.com',
        'name' => 'FoxUnity News'
    ],
    
    // Email templates
    'templates' => [
        'welcome' => [
            'subject' => 'Welcome to FoxUnity News!',
            'heading' => 'Subscription Successful!',
        ],
        'article_notification' => [
            'subject_prefix' => '🔥 Hot News: ',
            'heading' => 'New Hot Article Released!',
        ]
    ],
    
    // Logging
    'log_file' => __DIR__ . '/../view/back/data/email_logs.txt',
    'log_enabled' => true,
    
    // Email styling
    'styles' => [
        'background' => '#0a0a0a',
        'text_color' => '#dddddd',
        'accent_color' => '#ff9900',
        'button_bg' => '#ff9900',
        'button_text' => '#000000',
    ]
];
