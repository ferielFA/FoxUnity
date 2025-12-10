<?php
/**
 * Add missing foreign key constraints to users table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = Database::getConnection();
    
    echo "🔗 Adding missing foreign key constraints to users table...\n\n";
    
    $foreignKeys = [
        [
            'table' => 'evenement',
            'constraint' => 'fk_evenement_createur',
            'sql' => "ALTER TABLE `evenement` ADD CONSTRAINT `fk_evenement_createur` FOREIGN KEY (`createur_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE"
        ],
        [
            'table' => 'participation',
            'constraint' => 'fk_participation_user',
            'sql' => "ALTER TABLE `participation` ADD CONSTRAINT `fk_participation_user` FOREIGN KEY (`email_participant`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE"
        ],
        [
            'table' => 'comment',
            'constraint' => 'fk_comment_user',
            'sql' => "ALTER TABLE `comment` ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE"
        ],
        [
            'table' => 'comment_interaction',
            'constraint' => 'fk_interaction_user',
            'sql' => "ALTER TABLE `comment_interaction` ADD CONSTRAINT `fk_interaction_user` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE"
        ]
    ];
    
    foreach ($foreignKeys as $fk) {
        echo "Adding FK: {$fk['constraint']} on {$fk['table']}...\n";
        
        try {
            $conn->exec($fk['sql']);
            echo "  ✅ Successfully added\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ℹ️  Already exists\n";
            } else {
                echo "  ⚠️  Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Foreign key setup completed!\n";
    
    // Verify
    echo "\nVerifying foreign keys...\n";
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
    echo "\nTotal Foreign Keys: " . count($fks) . "\n";
    foreach ($fks as $fk) {
        echo "  ✓ {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
