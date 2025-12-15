# Guide d'installation et d'exécution - FoxUnity Support System

## Prérequis
- XAMPP installé et démarré (Apache + MySQL)
- PHP 7.4 ou supérieur
- Base de données MySQL

## Étapes d'installation

### 1. Vérifier que XAMPP est démarré
- Ouvrir le panneau de contrôle XAMPP
- Démarrer **Apache** et **MySQL**

### 2. Créer la table pour le système de score de confiance

**Option A : Via le navigateur (Recommandé)**
1. Ouvrir votre navigateur
2. Aller à : `http://localhost/foxunity/create_confidence_table.php`
3. Vous devriez voir le message : "Table 'user_confidence_scores' created successfully!"

**Option B : Via la ligne de commande**
```bash
cd C:\xampp\htdocs\foxunity
php create_confidence_table.php
```

### 3. Accéder à l'application

#### Dashboard Support (Admin)
- URL : `http://localhost/foxunity/view/back/reclamback.php`
- Fonctionnalités :
  - Voir toutes les réclamations
  - Filtrer par statut (New, In Progress, Resolved)
  - Filtrer par date (Today, This Week, This Month)
  - Ajouter/modifier/supprimer des réponses
  - Voir les scores de confiance des utilisateurs
  - Marquer les réclamations comme "most useful" (like)

#### Formulaire de nouvelle réclamation (Utilisateur)
- URL : `http://localhost/foxunity/view/front/contact_us.php`
- Fonctionnalités :
  - Soumettre une nouvelle réclamation
  - Animations gaming

#### Page de réclamations utilisateur
- URL : `http://localhost/foxunity/view/front/reclamation.php`
- Fonctionnalités :
  - Voir ses propres réclamations
  - Modifier ses réclamations

## Structure des fichiers

```
foxunity/
├── config/
│   └── config.php              # Configuration de la base de données
├── controllers/
│   ├── ReclamationController.php
│   ├── ResponseController.php
│   └── UserConfidenceController.php  # Nouveau : Gestion des scores
├── models/
│   ├── Reclamation.php
│   ├── Response.php
│   └── UserConfidenceScore.php       # Nouveau : Modèle de score
├── view/
│   ├── back/
│   │   └── reclamback.php      # Dashboard admin
│   └── front/
│       ├── contact_us.php      # Formulaire de réclamation
│       └── reclamation.php     # Page utilisateur
├── create_confidence_table.php # Script de création de table
└── README_SETUP.md            # Ce fichier
```

## Configuration de la base de données

La base de données utilisée est : **foxunity0**

Tables nécessaires :
- `reclamations` (déjà existante)
- `reponses` (déjà existante)
- `user_confidence_scores` (à créer avec le script)

## Fonctionnalités principales

### 1. Système de réclamations
- Création de réclamations avec statut "nouveau"
- Statut change automatiquement :
  - "en_cours" quand l'admin consulte
  - "resolu" quand une réponse est ajoutée

### 2. Système de tri et filtrage
- Tri automatique par statut puis par date
- Filtres par statut (New, In Progress, Resolved)
- Filtres par date (Today, This Week, This Month)
- Cartes de statistiques cliquables

### 3. Système de score de confiance
- Calcul automatique basé sur :
  - Nombre d'avis (40%)
  - Likes reçus (30%)
  - Taux de transparence (30%)
- Affichage avec badges colorés
- Système de likes pour marquer comme "most useful"

### 4. Animations Gaming
- Animations sur la page "New Request"
- Effets de particules, néon, glitch

## Dépannage

### Erreur de connexion à la base de données
- Vérifier que MySQL est démarré dans XAMPP
- Vérifier les identifiants dans `config/config.php`

### Table non créée
- Vérifier les permissions MySQL
- Vérifier que la base de données `foxunity0` existe

### Erreurs PHP
- Vérifier que `display_errors` est activé dans `php.ini`
- Vérifier les logs d'erreur Apache dans XAMPP

## Test rapide

1. Créer une réclamation : `http://localhost/foxunity/view/front/contact_us.php`
2. Voir dans le dashboard : `http://localhost/foxunity/view/back/reclamback.php`
3. Cliquer sur "New" dans les statistiques pour filtrer
4. Cliquer sur l'étoile pour ajouter un like
5. Vérifier que le score de confiance s'affiche sous l'email de l'utilisateur









