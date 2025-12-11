# Installation du système de satisfaction client

## ⚠️ IMPORTANT : Création de la table

Avant d'utiliser le système de satisfaction, vous devez créer la table dans votre base de données.

### Étape 1 : Exécuter le script SQL

1. Ouvrez votre gestionnaire de base de données (phpMyAdmin, MySQL Workbench, etc.)
2. Sélectionnez votre base de données (`foxunity` ou le nom de votre base)
3. Exécutez le script SQL suivant :

```sql
-- Table pour stocker les évaluations de satisfaction client (CSAT)
CREATE TABLE IF NOT EXISTS satisfactions (
    id_satisfaction INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    commentaire TEXT,
    date_evaluation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reclamation) REFERENCES reclamations(id_reclamation) ON DELETE CASCADE,
    UNIQUE KEY unique_reclamation (id_reclamation),
    INDEX idx_email (email),
    INDEX idx_rating (rating),
    INDEX idx_date (date_evaluation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Étape 2 : Vérifier la création

Après l'exécution, vérifiez que la table existe :

```sql
SHOW TABLES LIKE 'satisfactions';
DESCRIBE satisfactions;
```

### Étape 3 : Tester le système

1. Résolvez une réclamation dans le back-end
2. Allez sur la page front-end de réclamations
3. L'enquête de satisfaction devrait apparaître automatiquement
4. Testez l'envoi d'une évaluation

## 🔧 Résolution des problèmes

### Erreur : "Table satisfactions non trouvée"

**Solution** : Exécutez le script SQL ci-dessus pour créer la table.

### Erreur : "Erreur lors de l'enregistrement"

Vérifiez :
1. Que la table `satisfactions` existe
2. Que la table `reclamations` existe (clé étrangère)
3. Les logs d'erreur PHP pour plus de détails

### Vérifier les logs

Les erreurs sont enregistrées dans les logs PHP. Vérifiez :
- Fichier de log PHP (selon votre configuration)
- Console du navigateur (F12) pour les erreurs JavaScript

## 📝 Notes

- La table utilise une contrainte UNIQUE sur `id_reclamation` : une seule évaluation par réclamation
- La note doit être entre 1 et 5 (contrainte CHECK)
- La clé étrangère garantit l'intégrité référentielle avec la table `reclamations`








