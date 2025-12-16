# 🎮 Projet Web CRUD - FoxUnity Gaming Platform

## 📋 Description
Système complet de gestion d'événements gaming avec architecture **MVC** et **Programmation Orientée Objet**.

## ✅ Conformité aux exigences

### 1. Architecture MVC
```
projet_web/
├── model/              # Modèles (Entités métier)
│   ├── Evenement.php   
│   └── Participation.php
├── view/               # Vues (Interface utilisateur)
│   ├── back/           # BackOffice (Administration)
│   │   ├── evenements.php
│   │   └── participations.php
│   └── front/          # FrontOffice (Utilisateurs)
│       └── events_list.php
└── controller/         # Contrôleurs (Logique métier)
    ├── EvenementController.php
    └── ParticipationController.php
```

### 2. Programmation Orientée Objet (POO)
- ✅ **Classes** : Evenement, Participation, EvenementController, ParticipationController, Database
- ✅ **Encapsulation** : Propriétés privées avec getters/setters
- ✅ **Méthodes métier** : creer(), modifier(), supprimer(), inscrire(), desinscrire()
- ✅ **Héritage implicite** via PDO
- ✅ **Abstraction** via classes séparées

### 3. Utilisation de PDO
```php
// Exemple dans Database.php
self::$connection = new PDO($dsn, self::$username, self::$password);
self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Exemple dans EvenementController.php
$stmt = $this->db->prepare($sql);
$stmt->execute([':titre' => $evenement->getTitre()]);
```

## 🗂️ Structure de la Base de Données

### Table: evenement
```sql
- id_evenement (PK)
- titre
- description
- date_debut (datetime)
- date_fin (datetime)
- lieu
- createur_id (FK vers gamer)
- statut (enum: upcoming, ongoing, completed, cancelled)
```

### Table: participation
```sql
- id_participation (PK)
- id_evenement (FK vers evenement)
- id_gamer (FK vers gamer)
- date_participation (datetime)
```

## 🚀 Installation

### 1. Prérequis
- XAMPP (Apache + MySQL + PHP 8.0+)
- Navigateur web moderne

### 2. Configuration
```bash
# 1. Copier le projet dans htdocs
C:\xampp\htdocs\pw\projet_web\

# 2. Démarrer XAMPP
- Apache
- MySQL

# 3. Créer la base de données
- Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
- Importer le fichier: database.sql
```

### 3. Configuration de la connexion
Fichier: `config/database.php`
```php
private static $host = 'localhost';
private static $dbname = 'foxunity_db';
private static $username = 'root';
private static $password = '';
```

## 📱 Interfaces

### BackOffice (Administration)
**URL**: `http://localhost/pw/projet_web/view/back/evenements.php`

#### Fonctionnalités:
- ✅ **CREATE** : Créer un nouvel événement
- ✅ **READ** : Afficher tous les événements
- ✅ **UPDATE** : Modifier un événement existant
- ✅ **DELETE** : Supprimer un événement
- ✅ Gérer les participants par événement

**URL Participations**: `http://localhost/pw/projet_web/view/back/participations.php`

### FrontOffice (Utilisateurs)
**URL**: `http://localhost/pw/projet_web/view/front/events_list.php`

#### Fonctionnalités:
- ✅ Consulter tous les événements
- ✅ S'inscrire à un événement
- ✅ Se désinscrire d'un événement
- ✅ Voir mes participations

## 🔧 Fonctionnalités CRUD

### Événements (BackOffice)

#### CREATE
```php
$evenement = new Evenement(null, $titre, $description, $date_debut, $date_fin, $lieu, $createur_id, $statut);
$controller->creer($evenement);
```

#### READ
```php
$evenements = $controller->lireTous();        // Tous
$evenement = $controller->lireParId($id);     // Un seul
```

#### UPDATE
```php
$evenement->setTitre('Nouveau titre');
$controller->modifier($evenement);
```

#### DELETE
```php
$controller->supprimer($id);
```

### Participations

#### CREATE (Inscription)
```php
$participation = new Participation(null, $id_evenement, $id_gamer, new DateTime());
$participationController->inscrire($participation);
```

#### READ
```php
$participations = $participationController->lireParEvenement($id_evenement);
$mesParticipations = $participationController->lireParGamer($id_gamer);
```

#### DELETE (Désinscription)
```php
$participationController->desinscrire($id_gamer, $id_evenement);
```

#### VERIFY
```php
$estInscrit = $participationController->verifierInscription($id_gamer, $id_evenement);
```

## 📊 Méthodes du Modèle Evenement

### Méthodes métier (selon le diagramme)
- ✅ `creer()` - Créer un événement
- ✅ `modifier()` - Modifier un événement
- ✅ `supprimer()` - Supprimer un événement
- ✅ `calculerTempsRestant()` - Calculer le temps avant l'événement
- ✅ `changerStatut()` - Changer le statut de l'événement
- ✅ `obtenirParticipants()` - Obtenir le nombre de participants

## 📊 Méthodes du Modèle Participation

### Méthodes métier (selon le diagramme)
- ✅ `inscrire()` - Inscrire un participant
- ✅ `desinscrire()` - Désinscrire un participant
- ✅ `verifierInscription()` - Vérifier si déjà inscrit
- ✅ `obtenirDetails()` - Obtenir les détails de la participation

## 🎯 Points de validation

### ✅ Architecture MVC respectée
- **Model** : Classes Evenement.php et Participation.php
- **View** : Fichiers PHP dans view/back/ et view/front/
- **Controller** : EvenementController.php et ParticipationController.php

### ✅ POO appliquée
- Classes avec propriétés privées
- Constructeurs
- Getters et Setters
- Méthodes métier

### ✅ PDO utilisé
- Connexion via PDO dans Database.php
- Requêtes préparées (prepare/execute)
- Gestion des erreurs via PDOException
- Fetch modes configurés

### ✅ CRUD complet
- **BackOffice** : CRUD complet sur événements
- **FrontOffice** : Consultation + Inscription/Désinscription
- Gestion des participations

## 🔒 Sécurité

- ✅ Requêtes préparées (protection SQL Injection)
- ✅ htmlspecialchars() pour affichage (protection XSS)
- ✅ Validation des données
- ✅ Gestion des erreurs

## 📝 Données de test

Le fichier `database.sql` inclut:
- 3 gamers de test
- 3 événements pré-créés
- Quelques participations

### Comptes de test
- **Admin** : ID 1
- **JohnGamer** : ID 2 (utilisé par défaut dans le FrontOffice)
- **AlicePlay** : ID 3

## 🎨 Technologies utilisées

- **Backend** : PHP 8.0+
- **Base de données** : MySQL (via XAMPP)
- **Connexion** : PDO
- **Architecture** : MVC
- **Paradigme** : POO
- **Frontend** : HTML5, CSS3
- **Icons** : Font Awesome 6.0

## 📞 Support

Pour toute question sur le projet, vérifiez:
1. La base de données est bien importée
2. XAMPP est démarré (Apache + MySQL)
3. Les chemins dans config/database.php sont corrects
4. PHP 8.0+ est installé

## ✨ Auteur

Projet développé dans le cadre du cours de Développement Web
