<?php
/**
 * Automatic GD Extension Enabler
 * This script will enable the GD extension in php.ini
 */

echo "<h2>FoxUnity - PHP GD Extension Auto-Enable</h2>\n";
echo "<pre>\n";

$phpIniPath = php_ini_loaded_file();

if (!$phpIniPath) {
    die("❌ ERROR: Could not locate php.ini file\n");
}

echo "📍 PHP INI Location: $phpIniPath\n\n";

// Check if GD is already enabled
if (extension_loaded('gd')) {
    echo "✅ GD extension is already ENABLED\n";
    echo "✅ No action needed - QR codes will work!\n";
} else {
    echo "⚠️  GD extension is currently DISABLED\n\n";
    echo "Attempting to enable GD extension...\n";
    echo "=====================================\n\n";
    
    // Read php.ini
    $iniContent = file_get_contents($phpIniPath);
    
    if ($iniContent === false) {
        die("❌ ERROR: Could not read php.ini file\n");
    }
    
    // Check if ;extension=gd exists
    if (strpos($iniContent, ';extension=gd') !== false) {
        // Create backup
        $backupPath = $phpIniPath . '.backup.' . date('Y-m-d_His');
        if (copy($phpIniPath, $backupPath)) {
            echo "✅ Backup created: $backupPath\n";
        }
        
        // Uncomment the line
        $newContent = str_replace(';extension=gd', 'extension=gd', $iniContent);
        
        if (file_put_contents($phpIniPath, $newContent) !== false) {
            echo "✅ php.ini updated successfully\n\n";
            echo "🔄 Please restart Apache for changes to take effect:\n";
            echo "   1. Open XAMPP Control Panel\n";
            echo "   2. Click 'Stop' on Apache\n";
            echo "   3. Click 'Start' on Apache\n\n";
            echo "After restarting, refresh this page to verify.\n";
        } else {
            echo "❌ ERROR: Could not write to php.ini\n";
            echo "   Please run this script as Administrator or manually edit php.ini\n";
        }
    } elseif (strpos($iniContent, 'extension=gd') !== false) {
        echo "✅ GD extension is already uncommented in php.ini\n";
        echo "🔄 Please restart Apache to load the extension\n";
    } else {
        echo "⚠️  GD extension line not found in php.ini\n";
        echo "   You may need to add this line manually:\n";
        echo "   extension=gd\n";
    }
}

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "Status Summary:\n";
echo str_repeat("=", 60) . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "GD Loaded: " . (extension_loaded('gd') ? 'YES ✅' : 'NO ❌') . "\n";
echo "PHP INI: $phpIniPath\n";
echo str_repeat("=", 60) . "\n";

echo "</pre>\n";

// If GD is enabled, show info
if (extension_loaded('gd')) {
    echo "<h3>GD Extension Information:</h3>\n";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Feature</th><th>Status</th></tr>\n";
    
    $gdInfo = gd_info();
    foreach ($gdInfo as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : htmlspecialchars($value);
        echo "<tr><td>$key</td><td>$displayValue</td></tr>\n";
    }
    
    echo "</table>\n";
}

echo "<p><a href='check_gd.php'>Test GD Functionality</a> | <a href='events.php'>Go to Events</a></p>\n";
?>
