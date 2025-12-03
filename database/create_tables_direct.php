<?php
// Script direct pour créer les tables EVENTS avec FK
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== CRÉATION DES TABLES EVENTS AVEC FK ===\n\n";

try {
    // Connexion directe
    $db = new PDO(
        "mysql:host=localhost;dbname=foxunity_db;charset=utf8mb4",
        "root",
        "",
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    
    echo "✓ Connexion à foxunity_db réussie\n\n";
    
    // Désactiver les FK temporairement
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Suppression des anciennes tables
    echo "Suppression des anciennes tables...\n";
    $db->exec("DROP TABLE IF EXISTS tickets");
    $db->exec("DROP TABLE IF EXISTS participation");
    $db->exec("DROP TABLE IF EXISTS comment_interaction");
    $db->exec("DROP TABLE IF EXISTS comment");
    $db->exec("DROP VIEW IF EXISTS event_rating_stats");
    $db->exec("DROP TABLE IF EXISTS evenement");
    echo "✓ Tables supprimées\n\n";
    
    // Création EVENEMENT
    echo "Création table EVENEMENT avec FK...\n";
    $db->exec("
        CREATE TABLE evenement (
          id_evenement int(11) NOT NULL AUTO_INCREMENT,
          titre varchar(200) NOT NULL,
          description text NOT NULL,
          date_debut datetime NOT NULL,
          date_fin datetime NOT NULL,
          lieu varchar(255) NOT NULL,
          createur_email varchar(150) NOT NULL,
          statut enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (id_evenement),
          KEY idx_statut (statut),
          KEY idx_date_debut (date_debut),
          KEY idx_createur_email (createur_email),
          CONSTRAINT fk_evenement_createur 
            FOREIGN KEY (createur_email) 
            REFERENCES users (email) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table EVENEMENT créée\n";
    
    // Création PARTICIPATION
    echo "Création table PARTICIPATION avec FK...\n";
    $db->exec("
        CREATE TABLE participation (
          id_participation int(11) NOT NULL AUTO_INCREMENT,
          id_evenement int(11) NOT NULL,
          nom_participant varchar(100) NOT NULL,
          email_participant varchar(150) NOT NULL,
          date_participation datetime DEFAULT current_timestamp(),
          PRIMARY KEY (id_participation),
          UNIQUE KEY unique_participation (id_evenement, email_participant),
          KEY idx_evenement (id_evenement),
          KEY idx_email_participant (email_participant),
          CONSTRAINT fk_participation_evenement 
            FOREIGN KEY (id_evenement) 
            REFERENCES evenement (id_evenement) 
            ON DELETE CASCADE,
          CONSTRAINT fk_participation_user 
            FOREIGN KEY (email_participant) 
            REFERENCES users (email) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table PARTICIPATION créée\n";
    
    // Création COMMENT
    echo "Création table COMMENT avec FK...\n";
    $db->exec("
        CREATE TABLE comment (
          id_comment int(11) NOT NULL AUTO_INCREMENT,
          id_evenement int(11) NOT NULL,
          user_name varchar(100) NOT NULL,
          user_email varchar(150) NOT NULL,
          content text NOT NULL,
          rating tinyint(1) NOT NULL CHECK (rating between 1 and 5),
          likes int(11) DEFAULT 0,
          dislikes int(11) DEFAULT 0,
          is_reported tinyint(1) DEFAULT 0,
          report_reason varchar(255) DEFAULT NULL,
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (id_comment),
          KEY idx_evenement (id_evenement),
          KEY idx_user_email (user_email),
          KEY idx_created_at (created_at),
          KEY idx_rating (rating),
          KEY idx_reported (is_reported),
          CONSTRAINT fk_comment_evenement 
            FOREIGN KEY (id_evenement) 
            REFERENCES evenement (id_evenement) 
            ON DELETE CASCADE,
          CONSTRAINT fk_comment_user 
            FOREIGN KEY (user_email) 
            REFERENCES users (email) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table COMMENT créée\n";
    
    // Création COMMENT_INTERACTION
    echo "Création table COMMENT_INTERACTION avec FK...\n";
    $db->exec("
        CREATE TABLE comment_interaction (
          id_interaction int(11) NOT NULL AUTO_INCREMENT,
          id_comment int(11) NOT NULL,
          user_email varchar(150) NOT NULL,
          interaction_type enum('like','dislike') NOT NULL,
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (id_interaction),
          UNIQUE KEY unique_user_comment (id_comment, user_email),
          KEY idx_comment (id_comment),
          KEY idx_user_email (user_email),
          CONSTRAINT fk_interaction_comment 
            FOREIGN KEY (id_comment) 
            REFERENCES comment (id_comment) 
            ON DELETE CASCADE,
          CONSTRAINT fk_interaction_user 
            FOREIGN KEY (user_email) 
            REFERENCES users (email) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table COMMENT_INTERACTION créée\n";
    
    // Création TICKETS
    echo "Création table TICKETS...\n";
    $db->exec("
        CREATE TABLE tickets (
          id_ticket int(11) NOT NULL AUTO_INCREMENT,
          id_participation int(11) NOT NULL,
          id_evenement int(11) NOT NULL,
          token varchar(255) NOT NULL,
          qr_code_path varchar(500) DEFAULT NULL,
          status enum('active','used','cancelled') DEFAULT 'active',
          created_at timestamp NOT NULL DEFAULT current_timestamp(),
          updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (id_ticket),
          UNIQUE KEY token (token),
          UNIQUE KEY unique_ticket_per_participant (id_participation, id_evenement),
          KEY idx_token (token),
          KEY idx_participation (id_participation),
          KEY idx_evenement (id_evenement),
          KEY idx_status (status),
          CONSTRAINT fk_ticket_participation 
            FOREIGN KEY (id_participation) 
            REFERENCES participation (id_participation) 
            ON DELETE CASCADE,
          CONSTRAINT fk_ticket_evenement 
            FOREIGN KEY (id_evenement) 
            REFERENCES evenement (id_evenement) 
            ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table TICKETS créée\n";
    
    // Création VUE
    echo "Création vue EVENT_RATING_STATS...\n";
    $db->exec("
        CREATE VIEW event_rating_stats AS
        SELECT 
          comment.id_evenement AS id_evenement,
          COUNT(*) AS total_comments,
          AVG(comment.rating) AS average_rating,
          SUM(CASE WHEN comment.rating = 5 THEN 1 ELSE 0 END) AS five_stars,
          SUM(CASE WHEN comment.rating = 4 THEN 1 ELSE 0 END) AS four_stars,
          SUM(CASE WHEN comment.rating = 3 THEN 1 ELSE 0 END) AS three_stars,
          SUM(CASE WHEN comment.rating = 2 THEN 1 ELSE 0 END) AS two_stars,
          SUM(CASE WHEN comment.rating = 1 THEN 1 ELSE 0 END) AS one_star
        FROM comment
        GROUP BY comment.id_evenement
    ");
    echo "✓ Vue EVENT_RATING_STATS créée\n\n";
    
    // Réactiver les FK
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insertion données test
    echo "Insertion des données de test...\n";
    $db->exec("
        INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, createur_email, statut) VALUES
        ('Fortnite Championship 2025', 'Join us for the biggest Fortnite tournament of the year with amazing prizes!', '2025-12-15 14:00:00', '2025-12-15 18:00:00', 'Paris Gaming Arena', 'dhrifmeriem1231230@gmail.com', 'upcoming'),
        ('Valorant Team Battle', 'Competitive 5v5 Valorant tournament for teams', '2025-12-20 16:00:00', '2025-12-20 20:00:00', 'Online', 'ferielayari19@gmail.com', 'upcoming'),
        ('Charity Gaming Marathon', 'Gaming for Good - 24h gaming marathon to support charity', '2026-01-10 10:00:00', '2026-01-11 10:00:00', 'FoxUnity HQ', 'dhrifmeriem1231230@gmail.com', 'upcoming')
    ");
    echo "✓ 3 événements insérés\n\n";
    
    // Vérification
    echo "=== VÉRIFICATION ===\n\n";
    
    $stmt = $db->query("SHOW TABLES LIKE '%event%'");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    echo "Tables créées : " . implode(', ', $tables) . "\n";
    
    $count = $db->query("SELECT COUNT(*) FROM evenement")->fetchColumn();
    echo "Nombre d'événements : $count\n\n";
    
    // Afficher les FK
    echo "=== FOREIGN KEYS ===\n\n";
    $fks = $db->query("
        SELECT 
          TABLE_NAME,
          COLUMN_NAME,
          CONSTRAINT_NAME,
          REFERENCED_TABLE_NAME,
          REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'foxunity_db'
          AND TABLE_NAME IN ('evenement', 'participation', 'comment', 'comment_interaction')
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ")->fetchAll();
    
    foreach ($fks as $fk) {
        echo "✓ {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
    }
    
    echo "\n=== ✅ SUCCÈS ! TOUTES LES TABLES SONT CRÉÉES AVEC LES FK ===\n";
    echo "\nVous pouvez maintenant :\n";
    echo "1. Créer des événements (createur_email doit exister dans users)\n";
    echo "2. Participer aux événements (email_participant doit exister dans users)\n";
    echo "3. Commenter les événements (user_email doit exister dans users)\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    echo "\nFichier : " . $e->getFile() . "\n";
    echo "Ligne : " . $e->getLine() . "\n";
}

echo "</pre>";
?>
