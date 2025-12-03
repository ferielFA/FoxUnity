<?php
/**
 * Script to create all tables in foxunity_db database
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "📊 Creating tables in foxunity_db database...\n\n";
    
    // Get database connection
    $conn = Database::getConnection();
    
    $successCount = 0;
    $errorCount = 0;
    
    // Create users table
    echo "Creating users table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(100) NOT NULL,
          `email` varchar(150) NOT NULL,
          `google_id` varchar(255) DEFAULT NULL,
          `dob` date NOT NULL,
          `password` varchar(255) NOT NULL,
          `gender` varchar(20) DEFAULT NULL,
          `role` varchar(50) DEFAULT 'user',
          `status` varchar(20) DEFAULT 'active',
          `image` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`),
          UNIQUE KEY `google_id` (`google_id`),
          KEY `idx_user_email` (`email`),
          KEY `idx_user_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        echo "✅ Table created: users\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  users: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Create evenement table
    echo "Creating evenement table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `evenement` (
          `id_evenement` int(11) NOT NULL AUTO_INCREMENT,
          `titre` varchar(200) NOT NULL,
          `description` text NOT NULL,
          `date_debut` datetime NOT NULL,
          `date_fin` datetime NOT NULL,
          `lieu` varchar(255) NOT NULL,
          `createur_email` varchar(150) NOT NULL,
          `statut` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id_evenement`),
          KEY `idx_statut` (`statut`),
          KEY `idx_date_debut` (`date_debut`),
          KEY `idx_createur_email` (`createur_email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created: evenement\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  evenement: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Create participation table
    echo "Creating participation table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `participation` (
          `id_participation` int(11) NOT NULL AUTO_INCREMENT,
          `id_evenement` int(11) NOT NULL,
          `nom_participant` varchar(100) NOT NULL,
          `email_participant` varchar(150) NOT NULL,
          `date_participation` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`id_participation`),
          UNIQUE KEY `unique_participation` (`id_evenement`, `email_participant`),
          KEY `idx_evenement` (`id_evenement`),
          KEY `idx_email_participant` (`email_participant`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created: participation\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  participation: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Create tickets table
    echo "Creating tickets table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `tickets` (
          `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
          `id_participation` int(11) NOT NULL,
          `id_evenement` int(11) NOT NULL,
          `token` varchar(255) NOT NULL,
          `qr_code_path` varchar(500) DEFAULT NULL,
          `status` enum('active','used','cancelled') DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id_ticket`),
          UNIQUE KEY `token` (`token`),
          UNIQUE KEY `unique_ticket_per_participant` (`id_participation`, `id_evenement`),
          KEY `idx_token` (`token`),
          KEY `idx_participation` (`id_participation`),
          KEY `idx_evenement` (`id_evenement`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created: tickets\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  tickets: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Create comment table
    echo "Creating comment table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `comment` (
          `id_comment` int(11) NOT NULL AUTO_INCREMENT,
          `id_evenement` int(11) NOT NULL,
          `user_name` varchar(100) NOT NULL,
          `user_email` varchar(150) NOT NULL,
          `content` text NOT NULL,
          `rating` tinyint(1) NOT NULL,
          `likes` int(11) DEFAULT 0,
          `dislikes` int(11) DEFAULT 0,
          `is_reported` tinyint(1) DEFAULT 0,
          `report_reason` varchar(255) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id_comment`),
          KEY `idx_evenement` (`id_evenement`),
          KEY `idx_user_email` (`user_email`),
          KEY `idx_created_at` (`created_at`),
          KEY `idx_rating` (`rating`),
          KEY `idx_reported` (`is_reported`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created: comment\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  comment: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Create comment_interaction table
    echo "Creating comment_interaction table...\n";
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS `comment_interaction` (
          `id_interaction` int(11) NOT NULL AUTO_INCREMENT,
          `id_comment` int(11) NOT NULL,
          `user_email` varchar(150) NOT NULL,
          `interaction_type` enum('like','dislike') NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id_interaction`),
          UNIQUE KEY `unique_user_comment` (`id_comment`, `user_email`),
          KEY `idx_comment` (`id_comment`),
          KEY `idx_user_email` (`user_email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✅ Table created: comment_interaction\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  comment_interaction: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Now add foreign keys
    echo "\nAdding foreign key constraints...\n";
    
    $foreignKeys = [
        "ALTER TABLE `evenement` ADD CONSTRAINT `fk_evenement_createur` FOREIGN KEY (`createur_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE",
        "ALTER TABLE `participation` ADD CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE",
        "ALTER TABLE `participation` ADD CONSTRAINT `fk_participation_user` FOREIGN KEY (`email_participant`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE",
        "ALTER TABLE `tickets` ADD CONSTRAINT `fk_ticket_participation` FOREIGN KEY (`id_participation`) REFERENCES `participation` (`id_participation`) ON DELETE CASCADE",
        "ALTER TABLE `tickets` ADD CONSTRAINT `fk_ticket_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE",
        "ALTER TABLE `comment` ADD CONSTRAINT `fk_comment_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE",
        "ALTER TABLE `comment` ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE",
        "ALTER TABLE `comment_interaction` ADD CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE",
        "ALTER TABLE `comment_interaction` ADD CONSTRAINT `fk_interaction_user` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE"
    ];
    
    foreach ($foreignKeys as $fk) {
        try {
            $conn->exec($fk);
            echo "✅ Foreign key added\n";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠️  FK: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create view
    echo "\nCreating event_rating_stats view...\n";
    try {
        $conn->exec("CREATE OR REPLACE VIEW `event_rating_stats` AS
        SELECT 
          `comment`.`id_evenement` AS `id_evenement`,
          COUNT(*) AS `total_comments`,
          AVG(`comment`.`rating`) AS `average_rating`,
          SUM(CASE WHEN `comment`.`rating` = 5 THEN 1 ELSE 0 END) AS `five_stars`,
          SUM(CASE WHEN `comment`.`rating` = 4 THEN 1 ELSE 0 END) AS `four_stars`,
          SUM(CASE WHEN `comment`.`rating` = 3 THEN 1 ELSE 0 END) AS `three_stars`,
          SUM(CASE WHEN `comment`.`rating` = 2 THEN 1 ELSE 0 END) AS `two_stars`,
          SUM(CASE WHEN `comment`.`rating` = 1 THEN 1 ELSE 0 END) AS `one_star`
        FROM `comment`
        GROUP BY `comment`.`id_evenement`");
        echo "✅ View created: event_rating_stats\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "⚠️  view: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Database setup completed!\n";
    echo "📊 Successful operations: $successCount\n";
    if ($errorCount > 0) {
        echo "⚠️  Warnings: $errorCount\n";
    }
    echo str_repeat("=", 50) . "\n\n";
    
    // Verify tables were created
    echo "📋 Verifying created tables:\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "  ✓ $table\n";
        }
        echo "\n✅ Total tables: " . count($tables) . "\n";
    } else {
        echo "❌ No tables found in database!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 All done! Your database is ready to use.\n";
?>
