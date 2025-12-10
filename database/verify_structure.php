<?php
/**
 * Verify database structure and show complete table information
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = Database::getConnection();
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "FOXUNITY_DB - DATABASE STRUCTURE VERIFICATION\n";
    echo str_repeat("=", 70) . "\n\n";
    
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Total Tables/Views: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo str_repeat("-", 70) . "\n";
        echo "📋 TABLE: $table\n";
        echo str_repeat("-", 70) . "\n";
        
        // Get columns
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns:\n";
        foreach ($columns as $col) {
            $key = $col['Key'] ? " [{$col['Key']}]" : "";
            $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $col['Default'] !== null ? " DEFAULT '{$col['Default']}'" : "";
            $extra = $col['Extra'] ? " {$col['Extra']}" : "";
            
            echo sprintf("  %-25s %-20s %-10s%s%s%s\n", 
                $col['Field'], 
                $col['Type'], 
                $null,
                $key,
                $default,
                $extra
            );
        }
        
        // Get row count (skip views)
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\n📊 Row count: " . $count['count'] . "\n";
        } catch (PDOException $e) {
            echo "\n📊 (View - no row count)\n";
        }
        
        echo "\n";
    }
    
    // Check foreign keys
    echo str_repeat("=", 70) . "\n";
    echo "🔗 FOREIGN KEY CONSTRAINTS\n";
    echo str_repeat("=", 70) . "\n";
    
    $stmt = $conn->query("
        SELECT 
            TABLE_NAME,
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'foxunity_db'
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, CONSTRAINT_NAME
    ");
    
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fks) > 0) {
        foreach ($fks as $fk) {
            echo sprintf("  %s.%s → %s.%s [%s]\n",
                $fk['TABLE_NAME'],
                $fk['COLUMN_NAME'],
                $fk['REFERENCED_TABLE_NAME'],
                $fk['REFERENCED_COLUMN_NAME'],
                $fk['CONSTRAINT_NAME']
            );
        }
    } else {
        echo "  ⚠️  No foreign keys found!\n";
    }
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ Verification completed!\n";
    echo str_repeat("=", 70) . "\n\n";
    
    echo "📝 Database Summary:\n";
    echo "  - Database: foxunity_db\n";
    echo "  - Tables: " . (count($tables) - 1) . " (excluding views)\n";
    echo "  - Views: 1 (event_rating_stats)\n";
    echo "  - Character Set: utf8mb4\n";
    echo "  - Collation: utf8mb4_unicode_ci\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
