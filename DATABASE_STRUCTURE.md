# FoxUnity Database - Complete Structure

## Database Information
- **Database Name**: `foxunity_db`
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci / utf8mb4_general_ci
- **Created**: December 3, 2025

---

## Tables Overview

### 1. **users** (Main user table)
Primary table for all user accounts and authentication.

**Columns:**
- `id` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `username` - VARCHAR(100), UNIQUE, NOT NULL
- `email` - VARCHAR(150), UNIQUE, NOT NULL (indexed)
- `google_id` - VARCHAR(255), UNIQUE, NULL
- `dob` - DATE, NOT NULL
- `password` - VARCHAR(255), NOT NULL
- `gender` - VARCHAR(20), NULL
- `role` - VARCHAR(50), DEFAULT 'user'
- `status` - VARCHAR(20), DEFAULT 'active'
- `image` - VARCHAR(255), NULL

**Purpose**: Store user authentication and profile information.

---

### 2. **evenement** (Events table)
Stores all gaming events/tournaments created in the system.

**Columns:**
- `id_evenement` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `titre` - VARCHAR(200), NOT NULL
- `description` - TEXT, NOT NULL
- `date_debut` - DATETIME, NOT NULL (indexed)
- `date_fin` - DATETIME, NOT NULL
- `lieu` - VARCHAR(255), NOT NULL
- `createur_email` - VARCHAR(150), NOT NULL (indexed)
- `statut` - ENUM('upcoming','ongoing','completed','cancelled'), DEFAULT 'upcoming' (indexed)
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Foreign Keys:**
- `createur_email` should reference `users.email` (not enforced due to collation mismatch)

**Purpose**: Store event details created by users.

---

### 3. **participation** (Event registrations)
Tracks which users have registered for which events.

**Columns:**
- `id_participation` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `id_evenement` - INT(11), NOT NULL (indexed)
- `nom_participant` - VARCHAR(100), NOT NULL
- `email_participant` - VARCHAR(150), NOT NULL (indexed)
- `date_participation` - DATETIME, DEFAULT CURRENT_TIMESTAMP

**Foreign Keys:**
- `id_evenement` → `evenement.id_evenement` (ON DELETE CASCADE) ✅
- `email_participant` should reference `users.email` (not enforced)

**Unique Constraint:**
- `unique_participation` on (`id_evenement`, `email_participant`)

**Purpose**: Register users for events and prevent duplicate registrations.

---

### 4. **tickets** (Event tickets with QR codes)
Generated tickets for event participants with QR codes.

**Columns:**
- `id_ticket` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `id_participation` - INT(11), NOT NULL (indexed)
- `id_evenement` - INT(11), NOT NULL (indexed)
- `token` - VARCHAR(255), UNIQUE, NOT NULL (indexed)
- `qr_code_path` - VARCHAR(500), NULL
- `status` - ENUM('active','used','cancelled'), DEFAULT 'active' (indexed)
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Foreign Keys:**
- `id_participation` → `participation.id_participation` (ON DELETE CASCADE) ✅
- `id_evenement` → `evenement.id_evenement` (ON DELETE CASCADE) ✅

**Unique Constraint:**
- `unique_ticket_per_participant` on (`id_participation`, `id_evenement`)

**Purpose**: Generate unique tickets with QR codes for event entry.

---

### 5. **comment** (Event reviews and ratings)
User comments and ratings for events.

**Columns:**
- `id_comment` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `id_evenement` - INT(11), NOT NULL (indexed)
- `user_name` - VARCHAR(100), NOT NULL
- `user_email` - VARCHAR(150), NOT NULL (indexed)
- `content` - TEXT, NOT NULL
- `rating` - TINYINT(1), NOT NULL (1-5, indexed)
- `likes` - INT(11), DEFAULT 0
- `dislikes` - INT(11), DEFAULT 0
- `is_reported` - TINYINT(1), DEFAULT 0 (indexed)
- `report_reason` - VARCHAR(255), NULL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP (indexed)
- `updated_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

**Foreign Keys:**
- `id_evenement` → `evenement.id_evenement` (ON DELETE CASCADE) ✅
- `user_email` should reference `users.email` (not enforced)

**Purpose**: Allow users to rate and comment on events.

---

### 6. **comment_interaction** (Likes/Dislikes tracking)
Tracks user interactions (likes/dislikes) with comments.

**Columns:**
- `id_interaction` - INT(11), PRIMARY KEY, AUTO_INCREMENT
- `id_comment` - INT(11), NOT NULL (indexed)
- `user_email` - VARCHAR(150), NOT NULL (indexed)
- `interaction_type` - ENUM('like','dislike'), NOT NULL
- `created_at` - TIMESTAMP, DEFAULT CURRENT_TIMESTAMP

**Foreign Keys:**
- `id_comment` → `comment.id_comment` (ON DELETE CASCADE) ✅
- `user_email` should reference `users.email` (not enforced)

**Unique Constraint:**
- `unique_user_comment` on (`id_comment`, `user_email`)

**Purpose**: Prevent duplicate likes/dislikes from the same user.

---

## Views

### **event_rating_stats** (Event statistics view)
Aggregated statistics for event ratings.

**Columns:**
- `id_evenement` - INT(11)
- `total_comments` - BIGINT(21)
- `average_rating` - DECIMAL(7,4)
- `five_stars` - DECIMAL(22,0)
- `four_stars` - DECIMAL(22,0)
- `three_stars` - DECIMAL(22,0)
- `two_stars` - DECIMAL(22,0)
- `one_star` - DECIMAL(22,0)

**Purpose**: Quick access to rating statistics for each event.

---

## Database Relationships

```
users (1) ──< evenement (createur_email) [not enforced]
       (1) ──< participation (email_participant) [not enforced]
       (1) ──< comment (user_email) [not enforced]
       (1) ──< comment_interaction (user_email) [not enforced]

evenement (1) ──< participation (id_evenement) ✅
          (1) ──< tickets (id_evenement) ✅
          (1) ──< comment (id_evenement) ✅

participation (1) ──< tickets (id_participation) ✅

comment (1) ──< comment_interaction (id_comment) ✅
```

---

## Key Features

1. **User Management**: Complete user authentication system with email-based identification
2. **Event Management**: Create and manage gaming events with status tracking
3. **Participation System**: Register users for events with duplicate prevention
4. **Ticket Generation**: Automatic ticket creation with unique tokens and QR codes
5. **Rating System**: 5-star rating system with comments
6. **Social Features**: Like/dislike system for comments
7. **Moderation**: Report inappropriate comments

---

## File Locations

- **Database Creation**: `/database/create_foxunity_db.sql`
- **Table Creation Script**: `/database/create_tables.php`
- **Structure Verification**: `/database/verify_structure.php`
- **Foreign Key Addition**: `/database/add_foreign_keys.php`
- **Database Config**: `/config/database.php`

---

## Models Mapping

- `User` class → `users` table
- `Evenement` class → `evenement` table
- `Participation` class → `participation` table
- `Ticket` class → `tickets` table
- `Comment` class → `comment` table
- (Comment interactions handled in CommentController)

---

## Usage Instructions

### To Create the Database:
```bash
php database/create_tables.php
```

### To Verify Structure:
```bash
php database/verify_structure.php
```

### To Connect from PHP:
```php
require_once 'config/database.php';
$conn = Database::getConnection();
```

---

## Notes

⚠️ **Foreign Keys to users.email**: Due to character set/collation differences between tables, foreign keys referencing `users.email` are not enforced. The application logic handles referential integrity.

✅ **All Core Tables Created**: All 6 tables + 1 view are successfully created and ready for use.

🎯 **Ready for Production**: The database structure matches your PHP model classes perfectly.

---

## Last Updated
December 3, 2025
