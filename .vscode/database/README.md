# Configuration de la base de données pour les réponses

## Installation

Pour activer le système de gestion des réponses dans le dashboard support, vous devez créer la table `responses` dans votre base de données.

### Option 1 : Via phpMyAdmin ou votre gestionnaire de base de données

Exécutez le script SQL suivant :

```sql
CREATE TABLE IF NOT EXISTS `responses` (
  `id_response` int(11) NOT NULL AUTO_INCREMENT,
  `id_reclamation` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `admin_name` varchar(255) NOT NULL DEFAULT 'Admin',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_response`),
  KEY `id_reclamation` (`id_reclamation`),
  CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamations` (`id_reclamation`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Option 2 : Via la ligne de commande MySQL

```bash
mysql -u root -p foxunity < create_responses_table.sql
```

## Fonctionnalités

Une fois la table créée, l'admin pourra :

1. **Ajouter une réponse** à une réclamation via le bouton "Ajouter une réponse"
2. **Modifier une réponse** existante via le bouton "Modifier" sur chaque réponse
3. **Supprimer une réponse** via le bouton "Supprimer" sur chaque réponse

Toutes les réponses sont affichées sous chaque réclamation dans le dashboard support.









