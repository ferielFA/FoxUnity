# 📚 Documentation Complète - Projet Web FoxUnity

## 🎯 Vue d'ensemble du Projet

### Description
FoxUnity est une plateforme web de gestion d'événements gaming développée avec une architecture **MVC (Model-View-Controller)** complète en **PHP orienté objet** et **PDO** pour la gestion de la base de données.

### Objectifs Principaux
- ✅ Respect strict de l'architecture MVC
- ✅ Programmation Orientée Objet (POO)
- ✅ Utilisation obligatoire de PDO
- ✅ CRUD complet (Create, Read, Update, Delete)
- ✅ Validation côté client sans HTML5
- ✅ Interface FrontOffice et BackOffice
- ✅ Intégration base de données temps réel

---

## 🗂️ Architecture du Projet

```
projet_web/
├── config/
│   └── database.php          # Connexion PDO centralisée
├── model/
│   ├── Evenement.php          # Entité Événement
│   └── Participation.php      # Entité Participation
├── controller/
│   ├── EvenementController.php      # Logique métier événements
│   └── ParticipationController.php  # Logique métier participations
├── view/
│   ├── back/                  # BackOffice (Administration)
│   │   ├── dashboard.php      # Tableau de bord
│   │   ├── eventsb.php        # Gestion des événements
│   │   └── style.css          # Styles BackOffice
│   └── front/                 # FrontOffice (Utilisateurs)
│       ├── index.php          # Page d'accueil
│       ├── events.php         # Liste événements + formulaires
│       └── style.css          # Styles FrontOffice
└── README.md                  # Documentation générale
```

---

## 🗄️ Base de Données

### Schéma MySQL

#### Table: `evenement`
```sql
CREATE TABLE evenement (
    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    statut ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_debut (date_debut),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Champs:**
- `id_evenement`: Identifiant unique (PK)
- `titre`: Nom de l'événement (5-200 caractères)
- `description`: Description détaillée (10-1000 caractères)
- `date_debut`: Date et heure de début
- `date_fin`: Date et heure de fin
- `lieu`: Localisation de l'événement
- `statut`: État de l'événement (à venir, en cours, terminé, annulé)
- `created_at`: Date de création automatique

#### Table: `participation`
```sql
CREATE TABLE participation (
    id_participation INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT NOT NULL,
    nom_participant VARCHAR(100) NOT NULL,
    email_participant VARCHAR(255) NOT NULL,
    date_participation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Champs:**
- `id_participation`: Identifiant unique (PK)
- `id_evenement`: Référence à l'événement (FK)
- `nom_participant`: Nom complet du participant (2-100 caractères)
- `email_participant`: Email du participant (format email valide)
- `date_participation`: Date d'inscription automatique

**Relations:**
- Un événement peut avoir plusieurs participations (1:N)
- Suppression en cascade: si un événement est supprimé, toutes ses participations le sont aussi

---

## 🏗️ Architecture MVC Détaillée

### 1. Configuration (`config/`)

#### `database.php` - Connexion PDO
```php
class Database {
    private static $host = 'localhost';
    private static $dbname = 'foxunity_db';
    private static $username = 'root';
    private static $password = '';
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
            self::$connection = new PDO($dsn, self::$username, self::$password);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$connection;
    }
}
```

**Fonctionnalités:**
- ✅ Singleton Pattern (une seule instance de connexion)
- ✅ Gestion des erreurs avec exceptions
- ✅ Charset UTF-8 pour le support international
- ✅ Connexion sécurisée et réutilisable

---

### 2. Modèles (`model/`)

#### `Evenement.php` - Entité Événement

**Propriétés Privées:**
```php
private ?int $id_evenement;
private string $titre;
private string $description;
private DateTime $date_debut;
private DateTime $date_fin;
private string $lieu;
private string $statut;
```

**Méthodes Principales:**

1. **Constructeur**
```php
public function __construct(
    ?int $id_evenement = null,
    string $titre = '',
    string $description = '',
    ?DateTime $date_debut = null,
    ?DateTime $date_fin = null,
    string $lieu = '',
    string $statut = 'upcoming'
)
```

2. **Getters & Setters** (Encapsulation)
- `getTitre()` / `setTitre($titre)`
- `getDescription()` / `setDescription($description)`
- `getDateDebut()` / `setDateDebut(DateTime $date)`
- `getDateFin()` / `setDateFin(DateTime $date)`
- `getLieu()` / `setLieu($lieu)`
- `getStatut()` / `setStatut($statut)`

3. **Méthodes Métier**
```php
public function calculerTempsRestant(): string
public function changerStatut(string $nouveauStatut): bool
public function obtenirParticipants(): int
```

**Principes POO Appliqués:**
- ✅ Encapsulation (propriétés privées)
- ✅ Abstraction (méthodes publiques)
- ✅ Type Hinting strict
- ✅ Gestion des objets DateTime

---

#### `Participation.php` - Entité Participation

**Propriétés Privées:**
```php
private ?int $id_participation;
private int $id_evenement;
private string $nom_participant;
private string $email_participant;
private DateTime $date_participation;
```

**Méthodes Principales:**

1. **Constructeur**
```php
public function __construct(
    ?int $id_participation = null,
    int $id_evenement = 0,
    string $nom_participant = '',
    string $email_participant = '',
    ?DateTime $date_participation = null
)
```

2. **Getters & Setters**
- `getNomParticipant()` / `setNomParticipant($nom)`
- `getEmailParticipant()` / `setEmailParticipant($email)`
- `getIdEvenement()` / `setIdEvenement($id)`
- `getDateParticipation()` / `setDateParticipation(DateTime $date)`

3. **Méthodes Métier**
```php
public function inscrire(): bool
public function desinscrire(): bool
public function verifierInscription(string $email, int $id_evenement): bool
public function obtenirDetails(): array
```

---

### 3. Contrôleurs (`controller/`)

#### `EvenementController.php` - CRUD Complet

**Connexion à la Base:**
```php
private $db;

public function __construct() {
    $this->db = Database::getConnection();
}
```

**Méthodes CRUD:**

##### 1. CREATE - Créer un événement
```php
public function creer(Evenement $evenement): bool
```
- Insère un nouvel événement dans la base
- Utilise des requêtes préparées (protection SQL Injection)
- Retourne `true` en cas de succès

**Requête SQL:**
```sql
INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, statut) 
VALUES (:titre, :description, :date_debut, :date_fin, :lieu, :statut)
```

##### 2. READ - Lire les événements

**a) Lire tous les événements:**
```php
public function lireTous(): array
```
- Récupère tous les événements avec le nombre de participants
- JOIN avec la table participation
- Retourne un tableau d'objets Evenement avec `nb_participants`

**Requête SQL:**
```sql
SELECT e.*, COUNT(p.id_participation) as nb_participants 
FROM evenement e 
LEFT JOIN participation p ON e.id_evenement = p.id_evenement 
GROUP BY e.id_evenement 
ORDER BY e.date_debut ASC
```

**b) Lire un événement par ID:**
```php
public function lireParId(int $id): ?Evenement
```
- Récupère un événement spécifique
- Retourne `null` si non trouvé

##### 3. UPDATE - Modifier un événement
```php
public function modifier(Evenement $evenement): bool
```
- Met à jour tous les champs d'un événement
- Utilise l'ID pour identifier l'enregistrement

**Requête SQL:**
```sql
UPDATE evenement 
SET titre = :titre, description = :description, 
    date_debut = :date_debut, date_fin = :date_fin, 
    lieu = :lieu, statut = :statut 
WHERE id_evenement = :id
```

##### 4. DELETE - Supprimer un événement
```php
public function supprimer(int $id): bool
```
- Supprime un événement par ID
- Suppression en cascade des participations (contrainte FK)

**Requête SQL:**
```sql
DELETE FROM evenement WHERE id_evenement = :id
```

---

#### `ParticipationController.php` - Gestion des Inscriptions

**Méthodes Principales:**

##### 1. Inscrire un participant
```php
public function inscrire(Participation $participation): bool
```
- Vérifie d'abord si le participant n'est pas déjà inscrit
- Insère une nouvelle participation
- Prévient les doublons

**Requête SQL:**
```sql
INSERT INTO participation (id_evenement, nom_participant, email_participant, date_participation) 
VALUES (:id_evenement, :nom_participant, :email_participant, :date_participation)
```

##### 2. Vérifier une inscription
```php
public function verifierInscription(string $email, int $id_evenement): bool
```
- Vérifie si un email est déjà inscrit à un événement
- Utilise COUNT pour la vérification

**Requête SQL:**
```sql
SELECT COUNT(*) FROM participation 
WHERE email_participant = :email AND id_evenement = :id_evenement
```

##### 3. Lire les participations par événement
```php
public function lireParEvenement(int $id_evenement): array
```
- Liste tous les participants d'un événement
- Retourne un tableau d'objets Participation

##### 4. Désinscrire un participant
```php
public function desinscrire(string $email, int $id_evenement): bool
```
- Supprime une participation par email et ID événement

**Requête SQL:**
```sql
DELETE FROM participation 
WHERE email_participant = :email AND id_evenement = :id_evenement
```

##### 5. Lire toutes les participations
```php
public function lireTous(): array
```
- Récupère toutes les participations avec les titres des événements
- JOIN avec la table evenement

**Requête SQL:**
```sql
SELECT p.*, e.titre 
FROM participation p 
INNER JOIN evenement e ON p.id_evenement = e.id_evenement 
ORDER BY p.date_participation DESC
```

---

### 4. Vues (`view/`)

## 🎨 FrontOffice (`view/front/`)

### `index.php` - Page d'Accueil

**Sections Principales:**

1. **Hero Section**
   - Slogan: "Unite. Buy. Give Back."
   - Présentation de la mission FoxUnity

2. **How It Works**
   - 3 étapes: Créer compte, Acheter/Trader, Donation automatique
   - Cards avec animations

3. **Features**
   - Shop Marketplace
   - Trading Hub
   - Community Events
   - Latest News

4. **Impact Section**
   - Statistiques: $125,000+ donnés, 15+ organisations, 5,000+ membres
   - Causes supportées (badges)

5. **Support Section**
   - 24/7 Support
   - Quick Response
   - Secure & Private

**Navigation:**
```html
<nav>
    <a href="index.php">Home</a>
    <a href="events.php">Events</a>
    <a href="shop.html">Shop</a>
    <a href="trading.html">Trading</a>
    <a href="news.html">News</a>
    <a href="reclamation.html">Support</a>
    <a href="about.html">About Us</a>
</nav>
```

---

### `events.php` - Gestion des Événements (Page Principale)

**Fonctionnalités Intégrées:**

#### 1. **Affichage des Événements**
```php
$evenements = $eventController->lireTous();
foreach ($evenements as $item):
    $event = $item['evenement'];
    $nbParticipants = $item['nb_participants'];
    // Affichage carte événement
endforeach;
```

**Informations Affichées:**
- Titre de l'événement
- Description complète
- Date et heure de début
- Date et heure de fin
- Lieu
- Nombre de participants
- Statut (badge coloré)
- Bouton d'action (Join/Unavailable)

#### 2. **Formulaire Création d'Événement** (Modal)

**Déclenchement:**
```html
<a href="?create=1" class="btn-create-event">
    <i class="fas fa-plus-circle"></i> Create New Event
</a>
```

**Champs du Formulaire:**
```html
<form id="createEventForm" method="POST" novalidate>
    <input type="hidden" name="action" value="create_event">
    
    <!-- Titre -->
    <input type="text" id="titre" name="titre">
    <div class="error-message" id="error-titre"></div>
    
    <!-- Description -->
    <textarea id="description" name="description"></textarea>
    <div class="error-message" id="error-description"></div>
    
    <!-- Date Début (Format: YYYY-MM-DD HH:MM) -->
    <input type="text" id="date_debut" name="date_debut">
    <div class="error-message" id="error-date_debut"></div>
    
    <!-- Date Fin -->
    <input type="text" id="date_fin" name="date_fin">
    <div class="error-message" id="error-date_fin"></div>
    
    <!-- Lieu -->
    <input type="text" id="lieu" name="lieu">
    <div class="error-message" id="error-lieu"></div>
    
    <button type="submit">Create Event</button>
</form>
```

**Traitement PHP:**
```php
if ($_POST['action'] === 'create_event') {
    $evenement = new Evenement(
        null,
        htmlspecialchars($_POST['titre']),
        htmlspecialchars($_POST['description']),
        new DateTime($_POST['date_debut']),
        new DateTime($_POST['date_fin']),
        htmlspecialchars($_POST['lieu']),
        'upcoming'
    );
    
    if ($eventController->creer($evenement)) {
        // Succès
        header("Location: events.php");
    }
}
```

#### 3. **Formulaire Participation** (Modal)

**Déclenchement:**
```html
<a href="?join=<?= $event->getIdEvenement() ?>" class="btn-join">
    <i class="fas fa-user-plus"></i> Join Event
</a>
```

**Champs du Formulaire:**
```html
<form id="participationForm" method="POST" novalidate>
    <input type="hidden" name="action" value="participate">
    <input type="hidden" name="id_evenement" value="<?= $event->getId() ?>">
    
    <!-- Nom -->
    <input type="text" id="nom_participant" name="nom_participant">
    <div class="error-message" id="error-nom_participant"></div>
    
    <!-- Email -->
    <input type="text" id="email_participant" name="email_participant">
    <div class="error-message" id="error-email_participant"></div>
    
    <button type="submit">Confirm Registration</button>
</form>
```

**Traitement PHP:**
```php
if ($_POST['action'] === 'participate') {
    $participation = new Participation(
        null,
        (int)$_POST['id_evenement'],
        htmlspecialchars($_POST['nom_participant']),
        htmlspecialchars($_POST['email_participant']),
        new DateTime()
    );
    
    if ($participationController->inscrire($participation)) {
        $message = "Registration confirmed!";
    } else {
        $message = "Already registered or error occurred.";
    }
}
```

---

## 🔐 Validation JavaScript (Sans HTML5)

### Système de Validation Personnalisé

**Objet Validator:**
```javascript
const Validator = {
    isEmpty: function(value) {
        return value.trim() === '';
    },
    
    isValidLength: function(value, min, max) {
        const length = value.trim().length;
        return length >= min && length <= max;
    },
    
    isValidEmail: function(email) {
        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailPattern.test(email.trim());
    },
    
    isValidDateTime: function(dateTimeStr) {
        // Format: YYYY-MM-DD HH:MM
        const pattern = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/;
        if (!pattern.test(dateTimeStr.trim())) {
            return false;
        }
        // Validation des valeurs (année, mois, jour, heure, minute)
        // ...
        return true;
    },
    
    isDateAfter: function(date1Str, date2Str) {
        const d1 = new Date(date1Str.replace(' ', 'T'));
        const d2 = new Date(date2Str.replace(' ', 'T'));
        return d1 > d2;
    },
    
    isDateInFuture: function(dateTimeStr) {
        const inputDate = new Date(dateTimeStr.replace(' ', 'T'));
        const now = new Date();
        return inputDate > now;
    },
    
    showError: function(fieldId, message) {
        // Affiche message d'erreur
        // Ajoute classe 'error' au champ
    },
    
    clearError: function(fieldId) {
        // Efface message d'erreur
        // Ajoute classe 'success' au champ
    }
};
```

### Validation du Formulaire Création d'Événement

**Règles de Validation:**

1. **Titre:**
   - ❌ Ne peut pas être vide
   - ❌ Doit contenir entre 5 et 200 caractères
   ```javascript
   if (Validator.isEmpty(titre)) {
       Validator.showError('titre', 'Le titre est obligatoire');
       isValid = false;
   } else if (!Validator.isValidLength(titre, 5, 200)) {
       Validator.showError('titre', 'Le titre doit contenir entre 5 et 200 caractères');
       isValid = false;
   }
   ```

2. **Description:**
   - ❌ Ne peut pas être vide
   - ❌ Doit contenir entre 10 et 1000 caractères
   ```javascript
   if (!Validator.isValidLength(description, 10, 1000)) {
       Validator.showError('description', 'La description doit contenir entre 10 et 1000 caractères');
       isValid = false;
   }
   ```

3. **Date Début:**
   - ❌ Ne peut pas être vide
   - ❌ Format obligatoire: `YYYY-MM-DD HH:MM`
   - ❌ Doit être dans le futur
   ```javascript
   if (!Validator.isValidDateTime(dateDebut)) {
       Validator.showError('date_debut', 'Format invalide. Utilisez: YYYY-MM-DD HH:MM');
       isValid = false;
   } else if (!Validator.isDateInFuture(dateDebut)) {
       Validator.showError('date_debut', 'La date de début doit être dans le futur');
       isValid = false;
   }
   ```

4. **Date Fin:**
   - ❌ Ne peut pas être vide
   - ❌ Format obligatoire: `YYYY-MM-DD HH:MM`
   - ❌ Doit être après la date de début
   ```javascript
   if (!Validator.isDateAfter(dateFin, dateDebut)) {
       Validator.showError('date_fin', 'La date de fin doit être après la date de début');
       isValid = false;
   }
   ```

5. **Lieu:**
   - ❌ Ne peut pas être vide
   - ❌ Doit contenir entre 3 et 255 caractères

### Validation du Formulaire Participation

**Règles de Validation:**

1. **Nom Participant:**
   - ❌ Ne peut pas être vide
   - ❌ Entre 2 et 100 caractères
   - ❌ Seulement lettres, espaces, apostrophes et tirets
   ```javascript
   if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(nom.trim())) {
       Validator.showError('nom_participant', 
           'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets');
       isValid = false;
   }
   ```

2. **Email Participant:**
   - ❌ Ne peut pas être vide
   - ❌ Format email valide (regex personnalisée)
   ```javascript
   if (!Validator.isValidEmail(email)) {
       Validator.showError('email_participant', 
           'Format d\'email invalide (ex: exemple@domaine.com)');
       isValid = false;
   }
   ```

### Interface Visuelle des Erreurs

**CSS pour les États:**
```css
.form-group.error input,
.form-group.error textarea {
    border-color: #ff6b6b !important;
    background: rgba(255, 107, 107, 0.1) !important;
}

.form-group.success input,
.form-group.success textarea {
    border-color: #10b981 !important;
}

.error-message {
    color: #ff6b6b;
    font-size: 0.85rem;
    margin-top: 6px;
    display: none;
}

.error-message.show {
    display: block;
}
```

**Gestion des Événements:**
```javascript
// Validation à la soumission
createEventForm.addEventListener('submit', function(e) {
    e.preventDefault();
    // Validation complète
    if (isValid) {
        this.submit();
    }
});

// Effacement d'erreur à la saisie
field.addEventListener('input', function() {
    if (errorDiv.classList.contains('show')) {
        Validator.clearError(fieldId);
    }
});
```

---

## 🎛️ BackOffice (`view/back/`)

### `eventsb.php` - Gestion des Événements

**Fonctionnalités:**

#### 1. **Statistiques en Temps Réel**

**Calcul PHP:**
```php
$totalEvents = count($evenements);
$upcomingEvents = 0;
$expiredEvents = 0;
$totalParticipants = 0;
$now = new DateTime();

foreach ($evenements as $item) {
    $event = $item['evenement'];
    $totalParticipants += $item['nb_participants'];
    
    if ($event->getDateFin() < $now) {
        $expiredEvents++;
    } else {
        $upcomingEvents++;
    }
}
```

**Affichage:**
```html
<div class="stat-card">
    <div class="stat-icon total">
        <i class="fas fa-calendar-alt"></i>
    </div>
    <div class="stat-content">
        <div class="stat-label">Total Events</div>
        <div class="stat-value"><?= $totalEvents ?></div>
    </div>
</div>

<!-- Carte Upcoming Events -->
<div class="stat-value"><?= $upcomingEvents ?></div>

<!-- Carte Expired Events -->
<div class="stat-value"><?= $expiredEvents ?></div>

<!-- Carte Total Participants -->
<div class="stat-value"><?= $totalParticipants ?></div>
```

#### 2. **Tableau des Événements**

**Colonnes:**
- Titre
- Localisation
- Date de début (format: `M d, Y - H:i`)
- Date de fin
- Nombre de participants
- Statut (badge)
- Actions (View, Delete)

**Génération Dynamique:**
```php
<?php foreach ($evenements as $item): 
    $event = $item['evenement'];
    $nbParticipants = $item['nb_participants'];
    
    // Détermination du statut
    if ($event->getDateFin() < $now) {
        $statusClass = 'status-expired';
        $statusLabel = 'Expired';
    } else {
        $statusClass = 'status-available';
        $statusLabel = 'Available';
    }
?>
<tr>
    <td><?= htmlspecialchars($event->getTitre()) ?></td>
    <td><?= htmlspecialchars($event->getLieu()) ?></td>
    <td><?= $event->getDateDebut()->format('M d, Y - H:i') ?></td>
    <td><?= $event->getDateFin()->format('M d, Y - H:i') ?></td>
    <td><?= $nbParticipants ?></td>
    <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
    <td>
        <a href="../front/events.php">View</a>
        <form method="POST" onsubmit="return confirm('Confirmer la suppression?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id_evenement" value="<?= $event->getIdEvenement() ?>">
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
```

#### 3. **Suppression d'Événement**

**Traitement PHP:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $eventController->supprimer((int)$_POST['id_evenement']);
    header("Location: eventsb.php");
    exit;
}
```

---

### `dashboard.php` - Tableau de Bord

**Sections:**
- Vue d'ensemble générale
- Liens rapides vers les différentes sections
- Accès à la gestion des événements

**Navigation Sidebar:**
```html
<div class="sidebar">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="eventsb.php" class="active">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
</div>
```

---

## 🔒 Sécurité Implémentée

### 1. **Protection SQL Injection**

**Requêtes Préparées PDO:**
```php
// MAUVAIS (vulnérable)
$sql = "SELECT * FROM evenement WHERE id = " . $_GET['id'];

// BON (sécurisé)
$sql = "SELECT * FROM evenement WHERE id_evenement = :id";
$stmt = $this->db->prepare($sql);
$stmt->execute([':id' => $id]);
```

**Tous les paramètres utilisent des placeholders:**
- `:titre`, `:description`, `:date_debut`, etc.
- Protection automatique contre les injections SQL

### 2. **Protection XSS (Cross-Site Scripting)**

**Échappement des données:**
```php
// Affichage sécurisé
<?= htmlspecialchars($event->getTitre()) ?>
<?= htmlspecialchars($event->getLieu()) ?>
<?= htmlspecialchars($_POST['nom_participant']) ?>
```

**Fonction `htmlspecialchars()`:**
- Convertit les caractères spéciaux en entités HTML
- Prévient l'exécution de scripts malveillants

### 3. **Validation Côté Serveur**

**Sanitization:**
```php
$titre = htmlspecialchars($_POST['titre']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
```

### 4. **Gestion des Erreurs**

**Try-Catch dans les Contrôleurs:**
```php
try {
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return true;
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    return false;
}
```

**Avantages:**
- Les erreurs sont loggées (error_log)
- L'utilisateur ne voit pas les détails techniques
- Retour booléen pour gérer le flux

---

## 📊 Flux de Données Complet

### Scénario 1: Création d'un Événement

```
1. USER clique "Create New Event"
   ↓
2. FRONTEND affiche modal avec formulaire
   ↓
3. USER remplit les champs
   ↓
4. JAVASCRIPT valide les données (sans HTML5)
   ↓
5. FORMULAIRE soumis en POST
   ↓
6. PHP events.php reçoit les données
   ↓
7. Création objet Evenement avec données sanitisées
   ↓
8. EvenementController->creer($evenement)
   ↓
9. CONTROLLER prépare requête SQL
   ↓
10. PDO exécute INSERT INTO evenement
    ↓
11. DATABASE sauvegarde l'événement
    ↓
12. REDIRECT vers events.php
    ↓
13. AFFICHAGE de la liste mise à jour
```

### Scénario 2: Inscription à un Événement

```
1. USER clique "Join Event" sur une carte
   ↓
2. FRONTEND affiche modal participation
   ↓
3. USER saisit nom et email
   ↓
4. JAVASCRIPT valide:
   - Nom (2-100 chars, lettres uniquement)
   - Email (format valide)
   ↓
5. FORMULAIRE soumis en POST
   ↓
6. PHP events.php reçoit les données
   ↓
7. Création objet Participation
   ↓
8. ParticipationController->inscrire($participation)
   ↓
9. CONTROLLER vérifie si déjà inscrit
   ↓
10. Si non inscrit: INSERT INTO participation
    ↓
11. DATABASE sauvegarde la participation
    ↓
12. MESSAGE de succès affiché
    ↓
13. Compteur de participants mis à jour
```

### Scénario 3: Affichage BackOffice

```
1. ADMIN accède à eventsb.php
   ↓
2. PHP inclut EvenementController et ParticipationController
   ↓
3. EvenementController->lireTous()
   ↓
4. SQL JOIN entre evenement et participation
   ↓
5. DATABASE retourne événements + nb_participants
   ↓
6. PHP calcule statistiques:
   - Total events
   - Upcoming (date_fin > now)
   - Expired (date_fin < now)
   - Total participants (SUM)
   ↓
7. AFFICHAGE des cartes statistiques
   ↓
8. GÉNÉRATION du tableau dynamique
   ↓
9. Pour chaque événement:
   - Détermination du statut
   - Affichage badge coloré
   - Boutons View/Delete
```

---

## 🎨 Design et Expérience Utilisateur

### Thème Visuel

**Palette de Couleurs:**
- **Primaire:** `#f5c242` (Or) - Éléments importants
- **Secondaire:** `#f39c12` (Or foncé) - Hover states
- **Succès:** `#10b981` (Vert) - Messages de succès
- **Erreur:** `#ff6b6b` (Rouge) - Messages d'erreur
- **Background:** `#0f0f11` → `#111216` (Dégradé sombre)
- **Texte:** `#fff` (Blanc), `#cfd3d8` (Gris clair)

### Animations

**Transitions CSS:**
```css
.event-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(245,194,66,0.3);
}

.btn-join {
    transition: all 0.3s ease;
}

.btn-join:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245,194,66,0.4);
}
```

**Animations Keyframes:**
```css
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}
```

### Responsive Design

**Breakpoints:**
```css
@media (max-width: 768px) {
    .events-container {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .events-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
```

---

## 📋 Guide d'Installation

### Prérequis

1. **XAMPP** (ou WAMP/MAMP)
   - Apache
   - MySQL
   - PHP 8.0+

2. **Navigateur Web Moderne**
   - Chrome, Firefox, Edge, Safari

### Étapes d'Installation

#### 1. Configuration de l'Environnement

```bash
# 1. Copier le projet dans htdocs
C:\xampp\htdocs\pw\projet_web\

# 2. Démarrer XAMPP
- Lancer Apache
- Lancer MySQL
```

#### 2. Création de la Base de Données

**Via phpMyAdmin:**
```
1. Ouvrir http://localhost/phpmyadmin
2. Créer une nouvelle base: foxunity_db
3. Charset: utf8mb4_general_ci
```

**Via SQL:**
```sql
CREATE DATABASE foxunity_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE foxunity_db;

-- Table evenement
CREATE TABLE evenement (
    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    statut ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_debut (date_debut),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table participation
CREATE TABLE participation (
    id_participation INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT NOT NULL,
    nom_participant VARCHAR(100) NOT NULL,
    email_participant VARCHAR(255) NOT NULL,
    date_participation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données de test
INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, statut) VALUES
('Tournament Gaming 2025', 'Grand tournoi de gaming avec prix', '2025-11-22 20:00:00', '2025-11-23 02:00:00', 'Gaming Arena', 'upcoming'),
('Workshop Dev', 'Atelier de développement de jeux', '2025-11-27 20:00:00', '2025-11-28 00:00:00', 'Online - Discord', 'upcoming'),
('LAN Party', 'Soirée LAN entre amis', '2025-11-20 20:00:00', '2025-11-21 04:00:00', 'Community Center', 'upcoming');

INSERT INTO participation (id_evenement, nom_participant, email_participant, date_participation) VALUES
(1, 'John Doe', 'john@example.com', NOW()),
(1, 'Alice Smith', 'alice@example.com', NOW()),
(2, 'Bob Martin', 'bob@example.com', NOW());
```

#### 3. Configuration de la Connexion

**Fichier:** `config/database.php`

```php
private static $host = 'localhost';      // Hôte MySQL
private static $dbname = 'foxunity_db';  // Nom de la base
private static $username = 'root';        // Utilisateur MySQL
private static $password = '';            // Mot de passe (vide par défaut)
```

**Si vous avez un mot de passe MySQL:**
```php
private static $password = 'votre_mot_de_passe';
```

#### 4. Accès au Projet

**URLs:**
- **Page d'accueil:** `http://localhost/pw/projet_web/view/front/index.php`
- **Événements:** `http://localhost/pw/projet_web/view/front/events.php`
- **BackOffice:** `http://localhost/pw/projet_web/view/back/eventsb.php`
- **Dashboard:** `http://localhost/pw/projet_web/view/back/dashboard.php`

---

## 🧪 Tests et Utilisation

### Test 1: Créer un Événement

**Procédure:**
```
1. Aller sur events.php
2. Cliquer "Create New Event"
3. Remplir le formulaire:
   - Titre: "Test Event" (minimum 5 caractères)
   - Description: "Description de test avec 10+ caractères"
   - Date Début: "2025-12-25 14:30"
   - Date Fin: "2025-12-25 18:30"
   - Lieu: "Test Location"
4. Cliquer "Create Event"
5. Vérifier que l'événement apparaît dans la liste
```

**Validations Testées:**
- ✅ Champs vides refusés
- ✅ Format date incorrect refusé
- ✅ Date passée refusée
- ✅ Date fin avant date début refusée
- ✅ Longueurs min/max respectées

### Test 2: S'inscrire à un Événement

**Procédure:**
```
1. Sur events.php, cliquer "Join Event" sur une carte
2. Remplir:
   - Nom: "Test User"
   - Email: "test@example.com"
3. Cliquer "Confirm Registration"
4. Vérifier le message de succès
5. Vérifier que le compteur de participants augmente
```

**Validations Testées:**
- ✅ Email invalide refusé
- ✅ Nom avec chiffres refusé
- ✅ Double inscription empêchée
- ✅ Champs vides refusés

### Test 3: BackOffice

**Procédure:**
```
1. Aller sur eventsb.php
2. Vérifier les statistiques:
   - Total Events affiche le bon nombre
   - Upcoming/Expired calculés correctement
   - Total Participants = somme correcte
3. Vérifier le tableau:
   - Tous les événements affichés
   - Nombre de participants correct
   - Statut correct (Available/Expired)
4. Tester la suppression:
   - Cliquer Delete sur un événement
   - Confirmer
   - Vérifier qu'il disparaît
   - Vérifier que les participations sont supprimées
```

---

## 🚀 Fonctionnalités Avancées

### 1. Gestion des Statuts Automatique

**Logique:**
```php
$now = new DateTime();

if ($event->getDateFin() < $now) {
    $status = 'expired';
} elseif ($event->getDateDebut() <= $now && $event->getDateFin() >= $now) {
    $status = 'ongoing';
} else {
    $status = 'upcoming';
}
```

### 2. Compteur de Participants en Temps Réel

**Requête Optimisée:**
```sql
SELECT e.*, COUNT(p.id_participation) as nb_participants 
FROM evenement e 
LEFT JOIN participation p ON e.id_evenement = p.id_evenement 
GROUP BY e.id_evenement
```

**Avantages:**
- Une seule requête au lieu de N+1
- Performance optimale
- Données toujours à jour

### 3. Prévention des Doublons

**Vérification avant insertion:**
```php
public function inscrire(Participation $participation): bool {
    // Vérifier si déjà inscrit
    if ($this->verifierInscription(
        $participation->getEmailParticipant(), 
        $participation->getIdEvenement()
    )) {
        return false; // Déjà inscrit
    }
    
    // Insérer la participation
    // ...
}
```

### 4. Suppression en Cascade

**Contrainte Foreign Key:**
```sql
FOREIGN KEY (id_evenement) 
REFERENCES evenement(id_evenement) 
ON DELETE CASCADE
```

**Résultat:**
- Suppression d'un événement → toutes ses participations supprimées automatiquement
- Intégrité référentielle garantie

---

## 📈 Performance et Optimisation

### Optimisations Implémentées

1. **Singleton pour la Connexion DB**
   ```php
   if (self::$connection === null) {
       // Créer connexion une seule fois
   }
   ```

2. **Index sur les Colonnes Fréquemment Recherchées**
   ```sql
   INDEX idx_date_debut (date_debut),
   INDEX idx_statut (statut)
   ```

3. **Requêtes Préparées (Cache)**
   - PDO met en cache les requêtes préparées
   - Gain de performance sur requêtes répétées

4. **LEFT JOIN vs Requêtes Multiples**
   - Une seule requête avec JOIN
   - Évite le problème N+1

### Bonnes Pratiques Suivies

1. **Séparation des Responsabilités**
   - Model: Structure des données
   - Controller: Logique métier
   - View: Présentation

2. **Don't Repeat Yourself (DRY)**
   - Fonctions de validation réutilisables
   - Objet Validator centralisé

3. **Single Responsibility Principle**
   - Chaque classe a une responsabilité unique
   - EvenementController gère seulement les événements

4. **Encapsulation**
   - Propriétés privées
   - Accès via getters/setters

---

## 🛠️ Technologies Utilisées

### Backend
- **PHP 8.0+**
  - POO (Classes, Objets, Héritage)
  - PDO (PHP Data Objects)
  - Type Hinting
  - Exceptions

### Base de Données
- **MySQL 5.7+**
  - InnoDB Engine
  - Foreign Keys
  - Indexes
  - Transactions

### Frontend
- **HTML5**
  - Sémantique
  - Formulaires
  - Attribut `novalidate`

- **CSS3**
  - Flexbox
  - Grid Layout
  - Animations
  - Transitions
  - Media Queries

- **JavaScript ES6+**
  - Arrow Functions
  - Template Literals
  - Destructuring
  - Promises
  - DOM Manipulation

### Bibliothèques
- **Font Awesome 6.0.0** - Icônes
- **Google Fonts** - Typographie (Poppins, Orbitron)

---

## 📊 Métriques du Projet

### Code
- **Lignes de Code PHP:** ~800
- **Lignes de Code JavaScript:** ~300
- **Lignes de Code CSS:** ~600
- **Nombre de Fichiers:** 10

### Base de Données
- **Tables:** 2
- **Relations:** 1 (Foreign Key)
- **Index:** 2

### Fonctionnalités
- **Opérations CRUD:** 8 (4 pour événements, 4 pour participations)
- **Validations JavaScript:** 10+
- **Vues:** 4 (index, events, dashboard, eventsb)

---

## 🔄 Évolutions Futures Possibles

### 1. Authentification Utilisateur
```php
// Session management
$_SESSION['user_id'] = $user->getId();
$_SESSION['role'] = $user->getRole(); // admin, user
```

### 2. Upload d'Images
```php
// Image pour l'événement
$evenement->setImage($_FILES['image']);
```

### 3. Système de Notifications
```php
// Email aux participants
sendNotification($participant->getEmail(), $event);
```

### 4. Filtres et Recherche
```javascript
// Filtrer par catégorie, date, lieu
filterEvents(category, date, location);
```

### 5. Pagination
```php
// Limiter à 10 événements par page
$controller->lireTous($page, $limit);
```

### 6. Export de Données
```php
// Exporter liste participants en CSV
exportToCSV($participants);
```

### 7. Statistiques Avancées
```sql
-- Événements les plus populaires
SELECT e.titre, COUNT(p.id_participation) as participants
FROM evenement e
JOIN participation p ON e.id_evenement = p.id_evenement
GROUP BY e.id_evenement
ORDER BY participants DESC
LIMIT 10;
```

---

## 🐛 Résolution des Problèmes Courants

### Problème 1: Erreur de Connexion à la Base

**Symptôme:**
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```

**Solution:**
```php
// Vérifier les identifiants dans config/database.php
private static $username = 'root';
private static $password = 'votre_mot_de_passe';
```

### Problème 2: Événements Non Affichés

**Symptôme:**
Page blanche ou liste vide

**Solution:**
```php
// Vérifier que la base de données contient des données
SELECT * FROM evenement;

// Vérifier les erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Problème 3: Validation Ne Fonctionne Pas

**Symptôme:**
Formulaire se soumet sans validation

**Solution:**
```javascript
// Vérifier que novalidate est présent
<form novalidate>

// Vérifier que le JavaScript est chargé
console.log('Validator loaded:', typeof Validator);
```

### Problème 4: Caractères Spéciaux Mal Affichés

**Symptôme:**
Accents affichés incorrectement (Ã©, Ã , etc.)

**Solution:**
```php
// Vérifier le charset dans database.php
self::$connection->exec("SET NAMES utf8mb4");

// Vérifier le charset HTML
<meta charset="UTF-8">
```

---

## 📝 Conclusion

Ce projet démontre une **implémentation complète et professionnelle** d'une application web en PHP avec:

### ✅ Conformité aux Exigences
1. **Architecture MVC** strictement respectée
2. **POO** appliquée dans tous les composants
3. **PDO** utilisé exclusivement pour la base de données
4. **CRUD complet** pour événements et participations
5. **Validation JavaScript** sans HTML5
6. **Interfaces séparées** (FrontOffice/BackOffice)

### 🎯 Points Forts
- Code bien structuré et maintenable
- Sécurité (requêtes préparées, htmlspecialchars)
- Expérience utilisateur fluide
- Design moderne et responsive
- Validation robuste côté client
- Base de données normalisée

### 💡 Apprentissages
- Architecture MVC en pratique
- Gestion avancée de PDO
- Validation JavaScript personnalisée
- Intégration base de données temps réel
- Bonnes pratiques de développement web

---

## 👥 Auteur

**Projet développé par:** FerielFA  
**Framework:** PHP Vanilla (MVC Custom)  
**Repository:** https://github.com/ferielFA/FoxUnity  
**Branche:** events  
**Date:** Novembre 2025

---

## 📄 Licence

Projet académique - Tous droits réservés © 2025 FoxUnity
