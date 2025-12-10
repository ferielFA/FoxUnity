# Integration Database Setup Instructions

## Overview
This integration combines:
1. **foxunity0** database - All existing modules (Users, Shop, Trade, News, Support)
2. **Events module** - Event management with tickets and QR codes

## Key Changes Made

### 1. Database Name
- Changed from \oxunity0\ to \integration\

### 2. Events Module Integration
The events module uses **email-based references** instead of user IDs:
- \evenement.createur_email\ - references \users.email\
- \participation.email_participant\ - references \users.email\
- \comment.user_email\ - references \users.email\

### 3. Setup Steps

#### Step 1: Import Base Database
Import the foxunity0 structure (all tables except evenement/participation from old structure):
\\\sql
-- The foxunity0.sql contains all tables:
-- users, article, categorie, produit, purchase, skins, trade, 
-- trade_conversations, trade_history, reclamation, reponse,
-- email_verifications, password_resets
\\\

#### Step 2: Add Events Tables
Run the events module SQL to add:
- \evenement\ (with email-based creator reference)
- \participation\ (with email-based participant reference)
- \	ickets\ (QR code tickets)
- \comment\ (event ratings with email reference)
- \comment_interaction\ (likes/dislikes)
- \event_rating_stats\ (VIEW for statistics)

## Complete Setup Command

\\\ash
# 1. Create integration database
mysql -u root -e "DROP DATABASE IF EXISTS integration; CREATE DATABASE integration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import complete structure
mysql -u root integration < database/integration_complete.sql
\\\

## Verification

\\\sql
USE integration;
SHOW TABLES;

-- Should show all tables:
-- article, categorie, comment, comment_interaction, email_verifications,
-- evenement, event_rating_stats, participation, password_resets, produit,
-- purchase, reclamation, reponse, skins, tickets, trade, trade_conversations,
-- trade_history, trade_history_trading_view, users
\\\

## Configuration Update

Update \config/database.php\:
\\\php
private static \ = 'integration';
\\\

