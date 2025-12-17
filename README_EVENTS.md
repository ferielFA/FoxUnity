# 📅 FoxUnity - Event Management System

## 📋 Table des Matières
1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Structure de la Base de Données](#structure-de-la-base-de-données)
4. [Démarche du Code](#démarche-du-code)
5. [Flux de Fonctionnement](#flux-de-fonctionnement)
6. [API & Endpoints](#api--endpoints)
7. [Guide d'Utilisation](#guide-dutilisation)
8. [Dépannage](#dépannage)

---

## 🎯 Vue d'ensemble

Le système de gestion d'événements **FoxUnity** permet aux utilisateurs de :
- ✅ Créer et gérer des événements
- ✅ S'inscrire aux événements
- ✅ Recevoir des tickets avec QR codes automatiques
- ✅ Scanner les tickets via mobile
- ✅ Évaluer et commenter les événements

**Architecture :** MVC (Model-View-Controller)  
**Technologies :** PHP 7.4+, MySQL, JavaScript, HTML5-QRCode, PHPQRCode

---

## 🏗️ Architecture

```
projet_web/
├── model/
│   ├── Evenement.php          # Entité événement
│   ├── Participation.php      # Entité inscription
│   ├── Ticket.php             # Entité ticket
│   └── Comments.php           # Entité commentaire
│
├── controller/
│   ├── EvenementController.php       # Logique événements
│   ├── ParticipationController.php   # Logique inscriptions
│   ├── TicketController.php          # Logique tickets + QR
│   └── CommentController.php         # Logique évaluations
│
├── view/front/
│   ├── events.php             # Liste des événements
│   ├── event_details.php      # Détails + inscription
│   ├── my_tickets.php         # Mes tickets + scanner
│   ├── ticket_view.php        # Affichage ticket scanné
│   ├── scan_ticket.php        # Page scanner dédiée
│   └── qrcodes/               # Dossier QR codes générés
│
├── config/
│   ├── database.php           # Configuration BDD
│   └── site_config.php        # Configuration IP/URL
│
└── libs/
    └── phpqrcode/             # Bibliothèque génération QR
```

---

## 🗄️ Structure de la Base de Données

### Table : `evenement`
```sql
CREATE TABLE evenement (
    id_evenement INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255),
    createur_id INT,
    createur_email VARCHAR(255),
    statut ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    FOREIGN KEY (createur_id) REFERENCES user(id_user)
);
```

### Table : `participation`
```sql
CREATE TABLE participation (
    id_participation INT PRIMARY KEY AUTO_INCREMENT,
    id_evenement INT NOT NULL,
    user_id INT,
    nom_participant VARCHAR(255) NOT NULL,
    email_participant VARCHAR(255) NOT NULL,
    date_participation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id_user),
    UNIQUE KEY unique_participation (email_participant, id_evenement)
);
```

### Table : `tickets`
```sql
CREATE TABLE tickets (
    id_ticket INT PRIMARY KEY AUTO_INCREMENT,
    id_participation INT NOT NULL,
    id_evenement INT NOT NULL,
    token VARCHAR(50) UNIQUE NOT NULL,
    qr_code_path VARCHAR(255),
    status ENUM('active', 'used', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_participation) REFERENCES participation(id_participation) ON DELETE CASCADE,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE
);
```

### Table : `comment`
```sql
CREATE TABLE comment (
    id_comment INT PRIMARY KEY AUTO_INCREMENT,
    id_evenement INT NOT NULL,
    user_id INT,
    user_name VARCHAR(255),
    user_email VARCHAR(255),
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
    UNIQUE KEY unique_comment (user_email, id_evenement)
);
```

---

## 💻 Démarche du Code

### 1️⃣ **Model (Entités)**

#### `Evenement.php`
```php
class Evenement {
    private ?int $id_evenement;
    private string $titre;
    private string $description;
    private DateTime $date_debut;
    private DateTime $date_fin;
    private string $lieu;
    private ?int $createur_id;
    private ?string $createur_email;
    private string $statut;
    
    // Getters et Setters
    // Méthodes métier : calculerTempsRestant(), changerStatut()
}
```

**Responsabilité :** Représenter un événement avec ses propriétés et méthodes métier basiques.

---

#### `Participation.php`
```php
class Participation {
    private ?int $id_participation;
    private int $id_evenement;
    private ?int $user_id;
    private string $nom_participant;
    private string $email_participant;
    private DateTime $date_participation;
    
    // Getters et Setters
}
```

**Responsabilité :** Représenter l'inscription d'un utilisateur à un événement.

---

#### `Ticket.php`
```php
class Ticket {
    private ?int $id_ticket;
    private int $id_participation;
    private int $id_evenement;
    private string $token;
    private ?string $qr_code_path;
    private string $status;
    
    // Getters et Setters
}
```

**Responsabilité :** Représenter un ticket électronique avec QR code.

---

### 2️⃣ **Controller (Logique Métier)**

#### `EvenementController.php`

**Méthode : `creer(Evenement $evenement): bool`**
```php
public function creer(Evenement $evenement): bool {
    try {
        // 1. Vérifier les doublons (même titre + créateur + date)
        $checkSql = "SELECT COUNT(*) FROM evenement 
                    WHERE titre = :titre 
                    AND createur_id = :createur_id 
                    AND DATE(date_debut) = DATE(:date_debut)";
        
        // 2. Si doublon détecté, retourner false
        if ($count > 0) {
            return false;
        }
        
        // 3. Insérer le nouvel événement
        $sql = "INSERT INTO evenement (...) VALUES (...)";
        $stmt->execute([...]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
        return false;
    }
}
```

**Démarche :**
1. **Validation anti-doublon** : Évite les créations multiples accidentelles
2. **Préparation requête** : Utilise PDO prepared statements (sécurité SQL injection)
3. **Exécution** : Insert en base
4. **Gestion erreurs** : Try-catch avec logs
5. **Retour booléen** : Succès ou échec

---

**Méthode : `lireTous(): array`**
```php
public function lireTous(): array {
    $sql = "SELECT e.*, COUNT(p.id_participation) as nb_participants 
            FROM evenement e 
            LEFT JOIN participation p ON e.id_evenement = p.id_evenement 
            GROUP BY e.id_evenement 
            ORDER BY e.date_debut ASC";
    
    // Transformation des résultats en objets Evenement
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evenement = new Evenement(...);
        $results[] = [
            'evenement' => $evenement,
            'nb_participants' => (int)$row['nb_participants']
        ];
    }
    
    return $results;
}
```

**Démarche :**
1. **Jointure LEFT JOIN** : Récupère événements + nombre participants
2. **GROUP BY** : Agrégation par événement
3. **ORDER BY** : Tri chronologique
4. **Hydratation objets** : Transformation données brutes → objets PHP
5. **Retour tableau** : Événements avec statistiques

---

#### `ParticipationController.php`

**Méthode : `inscrire(Participation $participation): bool`**
```php
public function inscrire(Participation $participation): bool {
    try {
        // 1. Vérifier si déjà inscrit
        $alreadyRegistered = $this->verifierInscription(
            $participation->getEmailParticipant(), 
            $participation->getIdEvenement()
        );
        
        if ($alreadyRegistered) {
            return false; // Déjà inscrit
        }

        // 2. Insérer la participation
        $sql = "INSERT INTO participation (...) VALUES (...)";
        $stmt->execute([...]);
        
        // 3. Récupérer l'ID de la participation créée
        $idParticipation = (int)$this->db->lastInsertId();
        
        // 4. Générer automatiquement le ticket avec QR code
        if ($idParticipation > 0) {
            $this->ticketController->generateTicket(
                $idParticipation, 
                $participation->getIdEvenement()
            );
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erreur: " . $e->getMessage());
        return false;
    }
}
```

**Démarche :**
1. **Anti-doublon** : Empêche les inscriptions multiples
2. **Insert participation** : Enregistre l'inscription
3. **Récupération ID** : Via `lastInsertId()`
4. **Génération ticket automatique** : Appel asynchrone au TicketController
5. **Logging** : Traces pour debug

---

#### `TicketController.php` ⭐ (Cœur du système)

**Méthode : `generateTicket(int $idParticipation, int $idEvenement)`**
```php
public function generateTicket(int $idParticipation, int $idEvenement) {
    try {
        // 1. Vérifier si ticket existe déjà
        $existingTicket = $this->getTicketByParticipationAndEvent($idParticipation, $idEvenement);
        if ($existingTicket) {
            return $existingTicket; // Retourne le ticket existant
        }

        // 2. Générer token unique (MD5 + timestamp)
        $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
        
        // 3. Générer le QR code
        $qrCodePath = $this->generateQRCode($ticketNumber, $idParticipation, $idEvenement);
        
        // 4. Sauvegarder en base
        $sql = "INSERT INTO tickets (id_participation, id_evenement, token, qr_code_path, status) 
                VALUES (:id_participation, :id_evenement, :token, :qr_code_path, :status)";
        
        $stmt->execute([
            ':id_participation' => $idParticipation,
            ':id_evenement' => $idEvenement,
            ':token' => $ticketNumber,
            ':qr_code_path' => $qrCodePath,
            ':status' => 'active'
        ]);
        
        // 5. Créer et retourner l'objet Ticket
        $lastId = (int)$this->pdo->lastInsertId();
        $ticket = new Ticket($idParticipation, $idEvenement, $ticketNumber);
        $ticket->setIdTicket($lastId);
        $ticket->setQrCodePath($qrCodePath);
        
        return $ticket;
    } catch (Exception $e) {
        error_log("Erreur: " . $e->getMessage());
        return false;
    }
}
```

**Démarche :**
1. **Vérification existence** : Évite doublons
2. **Génération token** : MD5(uniqid) = unique et sécurisé
3. **Création QR code** : Appel à `generateQRCode()`
4. **Sauvegarde BDD** : Lien participation-événement-ticket
5. **Retour objet** : Ticket complet prêt à l'emploi

---

**Méthode : `generateQRCode(string $ticketNumber, int $idParticipation, int $idEvenement): string`**
```php
private function generateQRCode(string $ticketNumber, int $idParticipation, int $idEvenement): string {
    // 1. Définir le dossier de stockage
    $qrCodeDir = __DIR__ . '/../view/front/qrcodes/';
    
    // 2. Créer le dossier s'il n'existe pas
    if (!file_exists($qrCodeDir)) {
        mkdir($qrCodeDir, 0777, true);
    }
    
    // 3. Générer nom de fichier unique
    $filename = 'ticket_' . $idParticipation . '_' . $idEvenement . '_' . time() . '.png';
    $filepath = $qrCodeDir . $filename;
    
    // 4. Créer l'URL contenue dans le QR code
    $qrContent = BASE_URL . '/view/front/ticket_view.php?p=' . $idParticipation . '&e=' . $idEvenement;
    
    // 5. Générer l'image QR code (bibliothèque phpqrcode)
    QRcode::png($qrContent, $filepath, QR_ECLEVEL_L, 4, 2);
    
    // 6. Retourner le chemin relatif
    return 'qrcodes/' . $filename;
}
```

**Démarche :**
1. **Définition chemin** : Dossier `view/front/qrcodes/`
2. **Création dossier** : Si inexistant (permissions 0777)
3. **Nom fichier** : Format `ticket_{id_p}_{id_e}_{timestamp}.png`
4. **URL dans QR** : Pointe vers `ticket_view.php` avec params GET
5. **Génération image** : Via PHPQRCode (niveau correction L, taille 4, marge 2)
6. **Retour chemin** : Relatif pour stockage BDD

**Paramètres QRcode::png() :**
- `$qrContent` : Données à encoder
- `$filepath` : Chemin complet fichier de sortie
- `QR_ECLEVEL_L` : Niveau correction erreur (Low = 7%)
- `4` : Taille des modules (pixels)
- `2` : Marge blanche autour (modules)

---

### 3️⃣ **View (Interface Utilisateur)**

#### `events.php` - Liste des événements

**Flux :**
```php
// 1. Récupération des événements
$evenementController = new EvenementController();
$events = $evenementController->lireTous();

// 2. Affichage en grille
foreach ($events as $eventData) {
    $evenement = $eventData['evenement'];
    $nbParticipants = $eventData['nb_participants'];
    
    // 3. Carte événement avec bouton "Détails"
    echo "<div class='event-card'>
            <h3>{$evenement->getTitre()}</h3>
            <p>{$evenement->getDescription()}</p>
            <span>{$nbParticipants} participants</span>
            <a href='event_details.php?id={$evenement->getIdEvenement()}'>Voir détails</a>
          </div>";
}
```

---

#### `event_details.php` - Détails + Inscription

**Flux :**
```php
// 1. Récupération de l'événement par ID
$id = $_GET['id'];
$evenement = $evenementController->lireParId($id);

// 2. Vérification si utilisateur déjà inscrit
$isRegistered = $participationController->verifierInscription($userEmail, $id);

// 3. Affichage des détails
echo "<h1>{$evenement->getTitre()}</h1>";
echo "<p>{$evenement->getDescription()}</p>";

// 4. Bouton d'inscription conditionnel
if (!$isRegistered) {
    echo '<button onclick="registerToEvent()">S\'inscrire</button>';
} else {
    echo '<span>Déjà inscrit ✅</span>';
}

// 5. Section commentaires et notes
$comments = $commentController->getCommentsByEvent($id);
foreach ($comments as $comment) {
    // Affichage étoiles + contenu
}
```

**JavaScript AJAX (Inscription) :**
```javascript
function registerToEvent() {
    fetch('process_registration.php', {
        method: 'POST',
        body: JSON.stringify({
            event_id: eventId,
            user_name: userName,
            user_email: userEmail
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Inscription réussie ! Consultez vos tickets.');
            window.location.href = 'my_tickets.php';
        } else {
            alert('❌ ' + data.message);
        }
    });
}
```

---

#### `my_tickets.php` - Mes tickets + Scanner QR

**Flux :**
```php
// 1. Récupération des tickets de l'utilisateur
$ticketController = new TicketController();
$tickets = $ticketController->getTicketsByEmail($userEmail);

// 2. Affichage de chaque ticket
foreach ($tickets as $ticketData) {
    $ticket = $ticketData['ticket'];
    $eventTitle = $ticketData['event_title'];
    
    echo "<div class='ticket'>
            <h3>{$eventTitle}</h3>
            <p>Token : {$ticket->getToken()}</p>
            <img src='{$ticket->getQrCodePath()}' alt='QR Code' />
            <span>Statut : {$ticket->getStatus()}</span>
          </div>";
}
```

**Scanner QR (HTML5-QRCode) :**
```javascript
// 1. Initialisation du scanner
const html5QrCode = new Html5Qrcode("qr-reader");

// 2. Démarrage de la caméra
html5QrCode.start(
    { facingMode: "environment" }, // Caméra arrière
    {
        fps: 10,
        qrbox: { width: 250, height: 250 }
    },
    (decodedText) => {
        // 3. QR code scanné avec succès
        
        // 4. Détection du format
        if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
            // URL complète → Redirection directe
            window.location.href = decodedText;
        } else {
            // Token simple → Redirection avec paramètre
            window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
        }
    },
    (errorMessage) => {
        // Erreurs de scan (normales pendant balayage)
    }
);
```

**Démarche Scanner :**
1. **Initialisation** : Div container pour vidéo caméra
2. **Demande permissions** : Accès caméra via navigateur
3. **Scan continu** : Analyse image 10 fois/seconde
4. **Détection QR** : Décodage automatique
5. **Détection format** : URL vs Token
6. **Redirection** : Vers page appropriée

---

#### `ticket_view.php` - Affichage ticket scanné

**Flux :**
```php
// 1. Récupération des paramètres GET
$idParticipation = $_GET['p'];
$idEvenement = $_GET['e'];

// 2. Récupération des données liées
$ticket = $ticketController->getTicketByParticipationAndEvent($idParticipation, $idEvenement);
$participation = $participationController->lireParId($idParticipation);
$evenement = $evenementController->lireParId($idEvenement);

// 3. Affichage ticket stylisé
echo "<div class='ticket-card'>
        <h1>🎫 {$evenement->getTitre()}</h1>
        <p>Token : {$ticket->getToken()}</p>
        <p>Participant : {$participation->getNomParticipant()}</p>
        <p>Date : {$evenement->getDateDebut()->format('d/m/Y H:i')}</p>
        <p>Lieu : {$evenement->getLieu()}</p>
        <span class='status-{$ticket->getStatus()}'>{$ticket->getStatus()}</span>
        <img src='{$ticket->getQrCodePath()}' />
      </div>";
```

---

## 🔄 Flux de Fonctionnement

### **Scénario 1 : Créer un événement**

```mermaid
User → events.php
       ↓
Click "Créer événement"
       ↓
Formulaire de création
       ↓
Submit → EvenementController::creer()
       ↓
Vérification doublons
       ↓
INSERT INTO evenement
       ↓
Redirection → events.php
       ↓
Événement visible dans la liste
```

**Code simplifié :**
```php
// view/back/create_event.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evenement = new Evenement(
        null,
        $_POST['titre'],
        $_POST['description'],
        new DateTime($_POST['date_debut']),
        new DateTime($_POST['date_fin']),
        $_POST['lieu'],
        $userId,
        $userEmail,
        'upcoming'
    );
    
    if ($evenementController->creer($evenement)) {
        header('Location: events.php?success=1');
    } else {
        echo "Erreur : Événement déjà existant ou problème de création";
    }
}
```

---

### **Scénario 2 : S'inscrire à un événement**

```mermaid
User → event_details.php?id=5
       ↓
Click "S'inscrire"
       ↓
AJAX → ParticipationController::inscrire()
       ↓
Vérification anti-doublon
       ↓
INSERT INTO participation → lastInsertId()
       ↓
TicketController::generateTicket(participationId, eventId)
       ↓
generateQRCode() → QRcode::png()
       ↓
INSERT INTO tickets
       ↓
Response JSON {success: true, ticket_id: X}
       ↓
Redirection → my_tickets.php
```

**Code simplifié :**
```php
// process_registration.php
$data = json_decode(file_get_contents('php://input'), true);

$participation = new Participation(
    null,
    $data['event_id'],
    $userId,
    $data['user_name'],
    $data['user_email'],
    new DateTime()
);

if ($participationController->inscrire($participation)) {
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie ! Ticket généré.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Déjà inscrit ou erreur'
    ]);
}
```

---

### **Scénario 3 : Scanner un QR code**

```mermaid
User mobile → my_tickets.php
       ↓
Click bouton "Scanner QR"
       ↓
Activation caméra (HTML5-QRCode)
       ↓
Pointage vers QR code
       ↓
Décodage : http://192.168.100.83/projet_web/view/front/ticket_view.php?p=8&e=5
       ↓
Détection format URL → Redirection directe
       ↓
ticket_view.php récupère p=8, e=5
       ↓
SELECT ticket + participation + evenement
       ↓
Affichage ticket complet avec statut
```

**Code JavaScript :**
```javascript
// my_tickets.php (Scanner)
html5QrCode.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    (decodedText) => {
        // Arrêt du scanner
        html5QrCode.stop();
        
        // Détection format
        if (decodedText.startsWith('http://') || decodedText.startsWith('https://')) {
            // URL complète détectée
            window.location.href = decodedText;
        } else {
            // Token simple
            window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
        }
    }
);
```

---

### **Scénario 4 : Évaluer un événement**

```mermaid
User → event_details.php?id=5
       ↓
Événement terminé → Section évaluation visible
       ↓
Sélection étoiles (1-5) + Commentaire
       ↓
Click "Soumettre"
       ↓
AJAX → CommentController::addComment()
       ↓
Vérification : 1 commentaire par user
       ↓
INSERT INTO comment (content, rating)
       ↓
Calcul statistiques (moyenne, total)
       ↓
Response JSON {success: true, avg_rating: 4.5}
       ↓
Mise à jour affichage en temps réel
```

**Code JavaScript AJAX :**
```javascript
function submitRating(rating) {
    fetch('save_rating_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            event_id: eventId,
            user_email: userEmail,
            rating: rating,
            content: document.getElementById('comment').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mise à jour affichage
            document.getElementById('avg-rating').textContent = data.stats.average;
            document.getElementById('total-ratings').textContent = data.stats.total;
        }
    });
}
```

---

## 🌐 API & Endpoints

### Endpoints Publics

| Endpoint | Méthode | Description | Paramètres |
|----------|---------|-------------|------------|
| `/view/front/events.php` | GET | Liste tous les événements | - |
| `/view/front/event_details.php` | GET | Détails d'un événement | `id` (int) |
| `/view/front/my_tickets.php` | GET | Mes tickets | Session user |
| `/view/front/ticket_view.php` | GET | Afficher un ticket | `p` (id_participation), `e` (id_evenement) |
| `/view/front/scan_ticket.php` | GET | Scanner/Vérifier ticket | `token` (string) |

### Endpoints AJAX

| Endpoint | Méthode | Description | Payload JSON |
|----------|---------|-------------|--------------|
| `/process_registration.php` | POST | Inscription à événement | `{event_id, user_name, user_email}` |
| `/save_rating_ajax.php` | POST | Soumettre évaluation | `{event_id, rating, content, user_email}` |
| `/check_registration.php` | POST | Vérifier inscription | `{event_id, user_email}` |

---

## 📱 Guide d'Utilisation

### Configuration Initiale

1. **Configurer l'IP réseau :**
```php
// config/site_config.php
define('SERVER_IP', '192.168.100.83'); // Votre IP WiFi
define('BASE_URL', 'http://' . SERVER_IP . '/projet_web');
```

2. **Vérifier Apache :**
```powershell
netstat -an | Select-String ":80"
# Doit afficher : TCP 0.0.0.0:80 LISTENING
```

3. **Configurer le pare-feu :**
```powershell
# Exécuter en tant qu'administrateur
netsh advfirewall firewall add rule name="Apache HTTP" dir=in action=allow protocol=TCP localport=80
```

4. **Régénérer les QR codes :**
```
http://localhost/projet_web/regenerate_qr.php
```

---

### Utilisation Standard

#### **1. Créer un événement (Admin/Créateur)**
```
1. Se connecter au système
2. Aller dans "Gestion des événements"
3. Cliquer "Créer un événement"
4. Remplir le formulaire :
   - Titre
   - Description
   - Date/heure début et fin
   - Lieu
5. Cliquer "Créer"
6. Événement visible sur /view/front/events.php
```

#### **2. S'inscrire à un événement (Utilisateur)**
```
1. Aller sur /view/front/events.php
2. Parcourir les événements disponibles
3. Cliquer "Voir détails" sur un événement
4. Cliquer "S'inscrire"
5. Confirmation → Redirection vers "Mes tickets"
6. Ticket généré automatiquement avec QR code
```

#### **3. Accéder aux tickets**
```
1. Aller sur /view/front/my_tickets.php
2. Voir la liste de tous vos tickets
3. Chaque ticket affiche :
   - Nom événement
   - Date/heure
   - Lieu
   - QR code scannable
   - Statut (actif, utilisé, expiré)
```

#### **4. Scanner un QR code (Mobile)**
```
1. Connecter mobile au même WiFi que le serveur
2. Ouvrir : http://192.168.100.83/projet_web/view/front/my_tickets.php
3. Cliquer sur l'icône QR (bouton flottant en bas à droite)
4. Autoriser accès caméra
5. Pointer vers un QR code
6. Ticket s'affiche automatiquement avec tous les détails
```

#### **5. Évaluer un événement**
```
1. Événement doit être terminé (date_fin < maintenant)
2. Aller sur event_details.php?id=X
3. Voir section "Évaluation"
4. Cliquer sur les étoiles (1 à 5)
5. Ajouter commentaire (optionnel)
6. Cliquer "Soumettre"
7. Note enregistrée et statistiques mises à jour
```

---

## 🛠️ Dépannage

### Problème : QR code ne scanne pas

**Symptômes :**
- Caméra s'ouvre mais ne détecte rien
- Message "Invalid ticket parameters"
- Chargement infini

**Solutions :**
1. **Régénérer les QR codes :**
   ```
   http://localhost/projet_web/regenerate_qr.php
   ```

2. **Vérifier l'IP :**
   ```powershell
   ipconfig | Select-String "IPv4"
   ```
   Mettre à jour dans `config/site_config.php`

3. **Vérifier connectivité réseau :**
   - PC et mobile sur même WiFi
   - Tester accès : `http://192.168.100.83/projet_web/`

4. **Permissions caméra :**
   - Chrome : Paramètres → Confidentialité → Caméra
   - Autoriser pour le site

---

### Problème : "Already registered"

**Cause :** Email déjà inscrit à cet événement

**Solution :**
```sql
-- Vérifier en BDD
SELECT * FROM participation WHERE email_participant = 'user@email.com' AND id_evenement = 5;

-- Supprimer inscription si nécessaire
DELETE FROM participation WHERE id_participation = X;
```

Ou via interface : Se désinscrire puis réinscrire

---

### Problème : "Duplicate event detected"

**Cause :** Événement identique existe (même titre + créateur + date)

**Solution :**
- Modifier le titre légèrement
- Changer la date de début
- Ou supprimer l'événement existant

---

### Problème : Caméra ne s'active pas

**Cause :** Permissions navigateur refusées

**Solution :**
1. **Chrome Mobile :**
   ```
   Paramètres → Paramètres du site → [votre site] → Caméra → Autoriser
   ```

2. **Firefox Mobile :**
   ```
   Menu → Paramètres → Permissions du site → Caméra → Autoriser
   ```

3. **Safari iOS :**
   ```
   Réglages → Safari → Caméra → Autoriser
   ```

---

### Problème : Ticket statut incorrect

**Symptôme :** Ticket marqué "used" alors qu'il devrait être "active"

**Solution :**
```sql
-- Mise à jour manuelle du statut
UPDATE tickets SET status = 'active' WHERE id_ticket = X;
```

Ou via code :
```php
$ticketController->updateTicketStatus($ticketId, 'active');
```

---

## 📊 Monitoring & Logs

### Logs à surveiller

**PHP Error Log :**
```
C:\xampp\apache\logs\error.log
```

**Logs personnalisés :**
```php
// Les controllers logguent via error_log()
error_log("TicketController::generateTicket() START");
error_log("Participation ID: $idParticipation");
```

**Vérifier logs :**
```powershell
Get-Content C:\xampp\apache\logs\error.log -Tail 50
```

---

### Statistiques disponibles

**Événements :**
- Nombre total d'événements
- Événements à venir / en cours / terminés
- Nombre participants par événement
- Moyenne évaluations par événement

**Tickets :**
- Total tickets générés
- Tickets actifs / utilisés / expirés
- Taux de participation (inscrits vs tickets scannés)

**Utilisateurs :**
- Nombre inscriptions par utilisateur
- Événements créés par utilisateur
- Historique évaluations

---

## 🔐 Sécurité

### Mesures Implémentées

1. **SQL Injection :** PDO prepared statements
2. **XSS :** `htmlspecialchars()` sur tous les outputs
3. **CSRF :** Tokens de session (à implémenter)
4. **Authentification :** Email/Password, Google OAuth, Facial Recognition
5. **Validation inputs :** Côté serveur systématique

### Recommandations

```php
// Toujours valider les inputs
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    die('Invalid event ID');
}

// Échapper les outputs
echo htmlspecialchars($evenement->getTitre(), ENT_QUOTES, 'UTF-8');

// Vérifier permissions
if ($evenement->getCreateurId() !== $_SESSION['user_id']) {
    die('Unauthorized');
}
```

---

## 📚 Ressources

### Bibliothèques Utilisées

- **PHPQRCode** : Génération QR codes serveur
  - Repo : https://github.com/t0k4rt/phpqrcode
  - Doc : `/libs/phpqrcode/index.php`

- **HTML5-QRCode** : Scanner QR client
  - Repo : https://github.com/mebjas/html5-qrcode
  - CDN : `https://unpkg.com/html5-qrcode`

### Documentation PHP

- PDO : https://www.php.net/manual/fr/book.pdo.php
- DateTime : https://www.php.net/manual/fr/class.datetime.php
- Sessions : https://www.php.net/manual/fr/book.session.php

---

## 🚀 Évolutions Futures

### Fonctionnalités à Développer

1. **Notifications Push** : Rappels événements 24h avant
2. **Paiements** : Intégration Stripe/PayPal pour événements payants
3. **Capacité limitée** : Gestion jauge max participants
4. **Liste d'attente** : Si événement complet
5. **Exports** : CSV des participants
6. **Statistiques avancées** : Dashboard analytics
7. **API REST** : Pour applications mobiles natives
8. **Webhooks** : Notifications tierces (Slack, Discord)

---

## 📞 Support

Pour toute question ou problème :
- Documentation : Ce fichier README
- Logs : `C:\xampp\apache\logs\error.log`
- Tests : Scripts dans `/scripts/`

---

**Version :** 1.0  
**Dernière mise à jour :** 17 décembre 2025  
**Auteur :** FoxUnity Team
