<?php
/**
 * Check and enable PHP GD extension
 */

echo "<h2>PHP GD Extension Status Check</h2>\n";
echo "<pre>\n";

// Check if GD is loaded
if (extension_loaded('gd')) {
    echo "✅ SUCCESS: GD extension is ENABLED\n\n";
    
    // Show GD info
    echo "GD Information:\n";
    echo "===============\n";
    $gdInfo = gd_info();
    foreach ($gdInfo as $key => $value) {
        echo sprintf("%-30s: %s\n", $key, is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
    }
    
    echo "\n✅ QR Code generation should work now!\n";
} else {
    echo "❌ ERROR: GD extension is NOT enabled\n\n";
    echo "To enable GD extension:\n";
    echo "=======================\n";
    echo "1. Locate your php.ini file at: " . php_ini_loaded_file() . "\n";
    echo "2. Find the line: ;extension=gd\n";
    echo "3. Remove the semicolon (;) to uncomment it: extension=gd\n";
    echo "4. Restart Apache\n\n";
    
    echo "OR run this command in PowerShell (as Administrator):\n";
    echo "------------------------------------------------------\n";
    $phpIni = php_ini_loaded_file();
    echo "(Get-Content '$phpIni') -replace ';extension=gd', 'extension=gd' | Set-Content '$phpIni'\n\n";
    
    echo "Then restart Apache using XAMPP Control Panel.\n";
}

echo "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "PHP INI File: " . php_ini_loaded_file() . "\n";

echo "</pre>\n";

// Try to create a simple test image
if (extension_loaded('gd')) {
    echo "<h3>GD Test - Creating test image:</h3>\n";
    
    try {
        $img = imagecreate(100, 100);
        $bgColor = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 0, 0, 0);
        imagestring($img, 5, 10, 40, 'GD Works!', $textColor);
        
        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);
        
        echo "<img src='data:image/png;base64," . base64_encode($imageData) . "' alt='Test Image' />\n";
        echo "<p style='color: green;'>✅ GD is working correctly!</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    }
}
?>
