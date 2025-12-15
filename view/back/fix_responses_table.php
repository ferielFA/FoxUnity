<?php
/**
 * Script pour créer/corriger la table reponses avec la bonne structure
 * Accédez à ce fichier via: http://localhost/foxunity/view/back/fix_responses_table.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Table Reponses</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.warning{color:#ff9800;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "button{background:#4caf50;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:16px;margin:10px 0;}";
echo "button:hover{background:#45a049;}</style></head><body>";
echo "<h1>🔧 Correction Table Reponses</h1>";

try {
    $db = Config::getConnexion();
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
    
    // Vérifier toutes les tables possibles
    $allTables = $db->query("SHOW TABLES");
    $tables = $allTables->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>🔍 Tables trouvées dans la base de données:</h2><pre>";
    foreach ($tables as $table) {
        echo "- " . htmlspecialchars($table) . "\n";
    }
    echo "</pre>";
    
    // Vérifier si la table reponses existe
    $checkTable = $db->query("SHOW TABLES LIKE 'reponses'");
    $tableExists = $checkTable->rowCount() > 0;
    
    // Vérifier si des tables alternatives existent
    $hasResponses = in_array('responses', $tables);
    $hasAnswers = in_array('answers', $tables);
    
    if ($hasResponses || $hasAnswers) {
        echo "<p class='warning'>⚠️ Table(s) alternative(s) trouvée(s): ";
        if ($hasResponses) echo "'responses' ";
        if ($hasAnswers) echo "'answers' ";
        echo "</p>";
        
        // Vérifier la structure de la table alternative
        $altTableName = $hasResponses ? 'responses' : 'answers';
        echo "<p class='warning'>⚠️ Vérification de la structure de la table '$altTableName'...</p>";
        
        $altStructure = $db->query("DESCRIBE $altTableName");
        $altColumns = $altStructure->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>📋 Structure de la table '$altTableName':</h2><pre>";
        foreach ($altColumns as $col) {
            echo htmlspecialchars($col['Field']) . " | " . htmlspecialchars($col['Type']) . " | " . htmlspecialchars($col['Null']) . " | " . htmlspecialchars($col['Key']) . "\n";
        }
        echo "</pre>";
        
        // Mapper les colonnes de l'ancienne table vers la nouvelle
        $columnMapping = [
            'response_id' => 'id_reponse',
            'complaint_id' => 'id_reclamation',
            'id_admin' => 'id_admin',
            'message' => 'message',
            'response_date' => 'date_reponse',
            'response_status' => 'statut_reponse'
        ];
        
        // Vérifier si on peut migrer les données
        $canMigrate = true;
        $missingCols = [];
        foreach (['id_admin', 'message'] as $requiredCol) {
            $found = false;
            foreach ($altColumns as $col) {
                if ($col['Field'] === $requiredCol || isset($columnMapping[$col['Field']]) && $columnMapping[$col['Field']] === $requiredCol) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $canMigrate = false;
                $missingCols[] = $requiredCol;
            }
        }
        
        if ($canMigrate && !$tableExists) {
            echo "<p class='warning'>⚠️ Migration des données de '$altTableName' vers 'reponses'...</p>";
            // On va créer la table reponses d'abord, puis migrer
        }
    }
    
    if ($tableExists) {
        echo "<p class='success'>✅ La table 'reponses' existe</p>";
        
        // Vérifier la structure de la table
        $structure = $db->query("DESCRIBE reponses");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>📋 Structure actuelle de la table 'reponses':</h2><pre>";
        foreach ($columns as $col) {
            echo htmlspecialchars($col['Field']) . " | " . htmlspecialchars($col['Type']) . " | " . htmlspecialchars($col['Null']) . " | " . htmlspecialchars($col['Key']) . "\n";
        }
        echo "</pre>";
        
        // Vérifier les colonnes requises
        $requiredColumns = [
            'id_reponse' => ['type' => 'int(11)', 'null' => 'NO', 'key' => 'PRI', 'extra' => 'auto_increment'],
            'id_reclamation' => ['type' => 'int(11)', 'null' => 'NO', 'key' => 'MUL'],
            'id_admin' => ['type' => 'int(11)', 'null' => 'NO', 'key' => 'MUL'],
            'message' => ['type' => 'text', 'null' => 'NO'],
            'date_reponse' => ['type' => 'datetime', 'null' => 'NO'],
            'statut_reponse' => ['type' => 'varchar(50)', 'null' => 'YES']
        ];
        
        $existingColumns = [];
        foreach ($columns as $col) {
            $existingColumns[$col['Field']] = [
                'type' => $col['Type'],
                'null' => $col['Null'],
                'key' => $col['Key'],
                'extra' => $col['Extra'] ?? ''
            ];
        }
        
        $missingColumns = [];
        $wrongColumns = [];
        
        foreach ($requiredColumns as $colName => $colSpec) {
            if (!isset($existingColumns[$colName])) {
                $missingColumns[] = $colName;
            } else {
                // Vérifier si le type correspond approximativement
                $existingType = strtolower($existingColumns[$colName]['type']);
                $requiredType = strtolower($colSpec['type']);
                
                // Vérifications de compatibilité
                if (strpos($requiredType, 'int') !== false && strpos($existingType, 'int') === false) {
                    $wrongColumns[] = $colName . " (type attendu: " . $colSpec['type'] . ", type actuel: " . $existingColumns[$colName]['type'] . ")";
                } elseif (strpos($requiredType, 'text') !== false && strpos($existingType, 'text') === false && strpos($existingType, 'varchar') === false) {
                    $wrongColumns[] = $colName . " (type attendu: " . $colSpec['type'] . ", type actuel: " . $existingColumns[$colName]['type'] . ")";
                }
            }
        }
        
        if (!empty($wrongColumns)) {
            echo "<p class='warning'>⚠️ Colonnes avec types incompatibles: " . implode(', ', $wrongColumns) . "</p>";
            echo "<p class='warning'>⚠️ Ces colonnes existent mais avec un type différent. Vérifiez manuellement si nécessaire.</p>";
        }
        
        if (!empty($missingColumns)) {
            echo "<p class='warning'>⚠️ Colonnes manquantes: " . implode(', ', $missingColumns) . "</p>";
            
            // Ajouter les colonnes manquantes
            foreach ($missingColumns as $colName) {
                try {
                    $colSpec = $requiredColumns[$colName];
                    $sql = "ALTER TABLE reponses ADD COLUMN `$colName` " . $colSpec['type'];
                    
                    if ($colName === 'id_reponse') {
                        // Pour la clé primaire, on doit d'abord vérifier s'il y a déjà une clé primaire
                        $hasPK = false;
                        foreach ($existingColumns as $col => $spec) {
                            if (strpos($spec['key'], 'PRI') !== false) {
                                $hasPK = true;
                                break;
                            }
                        }
                        if (!$hasPK) {
                            $sql .= " NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
                        } else {
                            $sql .= " NOT NULL AUTO_INCREMENT FIRST";
                            echo "<p class='warning'>⚠️ Une clé primaire existe déjà. La colonne '$colName' sera ajoutée sans clé primaire.</p>";
                        }
                    } elseif ($colName === 'id_reclamation') {
                        $sql .= " NOT NULL";
                    } elseif ($colName === 'id_admin') {
                        $sql .= " NOT NULL";
                    } elseif ($colName === 'message') {
                        $sql .= " NOT NULL";
                    } elseif ($colName === 'date_reponse') {
                        $sql .= " NOT NULL DEFAULT CURRENT_TIMESTAMP";
                    } elseif ($colName === 'statut_reponse') {
                        $sql .= " DEFAULT 'sent'";
                    }
                    
                    $db->exec($sql);
                    echo "<p class='success'>✅ Colonne '$colName' ajoutée avec succès</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>❌ Erreur lors de l'ajout de la colonne '$colName': " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p class='warning'>💡 Essayez d'ajouter la colonne manuellement via phpMyAdmin si nécessaire.</p>";
                }
            }
        } else {
            echo "<p class='success'>✅ Toutes les colonnes requises sont présentes</p>";
        }
        
        // Vérifier si id_reponse est la clé primaire
        $primaryKeyCheck = $db->query("SHOW KEYS FROM reponses WHERE Key_name = 'PRIMARY'");
        $hasPrimaryKey = $primaryKeyCheck->rowCount() > 0;
        
        if (!$hasPrimaryKey) {
            echo "<p class='warning'>⚠️ Aucune clé primaire trouvée. Ajout de la clé primaire...</p>";
            try {
                $db->exec("ALTER TABLE reponses ADD PRIMARY KEY (id_reponse)");
                echo "<p class='success'>✅ Clé primaire ajoutée</p>";
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Erreur lors de l'ajout de la clé primaire: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        // Vérifier les clés étrangères
        $foreignKeyCheck = $db->query("SHOW CREATE TABLE reponses");
        $createTable = $foreignKeyCheck->fetch(PDO::FETCH_ASSOC);
        $hasForeignKey = strpos($createTable['Create Table'], 'FOREIGN KEY') !== false;
        
        if (!$hasForeignKey) {
            echo "<p class='warning'>⚠️ Aucune clé étrangère trouvée. Ajout des clés étrangères...</p>";
            try {
                // Vérifier si la table users existe
                $checkUsers = $db->query("SHOW TABLES LIKE 'users'");
                if ($checkUsers->rowCount() > 0) {
                    $db->exec("ALTER TABLE reponses ADD CONSTRAINT fk_reponses_admin FOREIGN KEY (id_admin) REFERENCES users(id) ON DELETE CASCADE");
                    echo "<p class='success'>✅ Clé étrangère vers users ajoutée</p>";
                } else {
                    echo "<p class='warning'>⚠️ La table 'users' n'existe pas, impossible d'ajouter la clé étrangère</p>";
                }
                
                // Vérifier si la table reclamations existe
                $checkReclamations = $db->query("SHOW TABLES LIKE 'reclamations'");
                if ($checkReclamations->rowCount() > 0) {
                    $db->exec("ALTER TABLE reponses ADD CONSTRAINT fk_reponses_reclamation FOREIGN KEY (id_reclamation) REFERENCES reclamations(id_reclamation) ON DELETE CASCADE");
                    echo "<p class='success'>✅ Clé étrangère vers reclamations ajoutée</p>";
                } else {
                    echo "<p class='warning'>⚠️ La table 'reclamations' n'existe pas, impossible d'ajouter la clé étrangère</p>";
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                    echo "<p class='warning'>⚠️ Les clés étrangères existent déjà</p>";
                } else {
                    echo "<p class='error'>❌ Erreur lors de l'ajout des clés étrangères: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
        
    } else {
        echo "<p class='warning'>⚠️ La table 'reponses' n'existe pas. Création en cours...</p>";
        
        // Créer la table avec la bonne structure
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `reponses` (
          `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
          `id_reclamation` int(11) NOT NULL,
          `id_admin` int(11) NOT NULL,
          `message` text NOT NULL,
          `date_reponse` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `statut_reponse` varchar(50) DEFAULT 'sent',
          PRIMARY KEY (`id_reponse`),
          KEY `idx_id_reclamation` (`id_reclamation`),
          KEY `idx_id_admin` (`id_admin`),
          CONSTRAINT `fk_reponses_reclamation` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamations` (`id_reclamation`) ON DELETE CASCADE,
          CONSTRAINT `fk_reponses_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        try {
            $db->exec($createTableSQL);
            echo "<p class='success'>✅ Table 'reponses' créée avec succès</p>";
        } catch (PDOException $e) {
            // Si les clés étrangères échouent, créer sans elles
            $createTableSQLSimple = "
            CREATE TABLE IF NOT EXISTS `reponses` (
              `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
              `id_reclamation` int(11) NOT NULL,
              `id_admin` int(11) NOT NULL,
              `message` text NOT NULL,
              `date_reponse` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `statut_reponse` varchar(50) DEFAULT 'sent',
              PRIMARY KEY (`id_reponse`),
              KEY `idx_id_reclamation` (`id_reclamation`),
              KEY `idx_id_admin` (`id_admin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            try {
                $db->exec($createTableSQLSimple);
                echo "<p class='success'>✅ Table 'reponses' créée avec succès (sans clés étrangères)</p>";
                echo "<p class='warning'>⚠️ Les clés étrangères n'ont pas pu être créées. Vous pouvez les ajouter manuellement plus tard.</p>";
            } catch (PDOException $e2) {
                echo "<p class='error'>❌ Erreur lors de la création de la table: " . htmlspecialchars($e2->getMessage()) . "</p>";
                throw $e2;
            }
        }
    }
    
    // Vérifier si les colonnes ont les bons noms (même si elles existent)
    if ($tableExists) {
        $structure = $db->query("DESCRIBE reponses");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Mapping des anciens noms vers les nouveaux noms
        $renameMap = [
            'response_id' => 'id_reponse',
            'complaint_id' => 'id_reclamation',
            'response_date' => 'date_reponse',
            'response_status' => 'statut_reponse',
            'response_text' => 'message'
        ];
        
        $needsRename = false;
        foreach ($renameMap as $oldName => $newName) {
            if (in_array($oldName, $columnNames) && !in_array($newName, $columnNames)) {
                $needsRename = true;
                echo "<p class='warning'>⚠️ Colonne '$oldName' doit être renommée en '$newName'</p>";
                
                try {
                    // Récupérer le type de la colonne
                    $colInfo = null;
                    foreach ($columns as $col) {
                        if ($col['Field'] === $oldName) {
                            $colInfo = $col;
                            break;
                        }
                    }
                    
                    if ($colInfo) {
                        $type = $colInfo['Type'];
                        $null = $colInfo['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                        $default = $colInfo['Default'] !== null ? "DEFAULT '" . $colInfo['Default'] . "'" : '';
                        if ($colInfo['Extra'] === 'auto_increment') {
                            $default = 'AUTO_INCREMENT';
                        }
                        
                        $sql = "ALTER TABLE reponses CHANGE COLUMN `$oldName` `$newName` $type $null $default";
                        $db->exec($sql);
                        echo "<p class='success'>✅ Colonne '$oldName' renommée en '$newName'</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p class='error'>❌ Erreur lors du renommage de '$oldName' en '$newName': " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }
    
    // Afficher la structure finale
    echo "<h2>📋 Structure finale de la table 'reponses':</h2>";
    try {
        $structure = $db->query("DESCRIBE reponses");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        foreach ($columns as $col) {
            echo htmlspecialchars($col['Field']) . " | " . htmlspecialchars($col['Type']) . " | " . htmlspecialchars($col['Null']) . " | " . htmlspecialchars($col['Key']) . "\n";
        }
        echo "</pre>";
        
        // Vérifier s'il y a des données
        $countQuery = $db->query("SELECT COUNT(*) as count FROM reponses");
        $count = $countQuery->fetch(PDO::FETCH_ASSOC);
        echo "<p class='success'>✅ Nombre de réponses dans la table: " . $count['count'] . "</p>";
        
        // Test de connexion avec une requête simple
        echo "<h2>🧪 Test de la table:</h2>";
        $testQuery = $db->query("SELECT id_reponse, id_reclamation, id_admin, message, date_reponse, statut_reponse FROM reponses LIMIT 1");
        echo "<p class='success'>✅ La table 'reponses' est accessible et fonctionnelle</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur lors de la vérification finale: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p class='warning'>⚠️ Il y a peut-être un problème avec les noms de colonnes. Vérifiez manuellement la structure.</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>✅ Correction terminée !</strong></p>";
    echo "<p><a href='reclamback.php' style='color:#4caf50;'>← Retour aux Réclamations</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur PDO: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h2>🔧 Solution manuelle:</h2>";
    echo "<p>Exécutez le script SQL suivant dans phpMyAdmin:</p>";
    echo "<pre>";
    echo "CREATE TABLE IF NOT EXISTS `reponses` (\n";
    echo "  `id_reponse` int(11) NOT NULL AUTO_INCREMENT,\n";
    echo "  `id_reclamation` int(11) NOT NULL,\n";
    echo "  `id_admin` int(11) NOT NULL,\n";
    echo "  `message` text NOT NULL,\n";
    echo "  `date_reponse` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
    echo "  `statut_reponse` varchar(50) DEFAULT 'sent',\n";
    echo "  PRIMARY KEY (`id_reponse`),\n";
    echo "  KEY `idx_id_reclamation` (`id_reclamation`),\n";
    echo "  KEY `idx_id_admin` (`id_admin`)\n";
    echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

