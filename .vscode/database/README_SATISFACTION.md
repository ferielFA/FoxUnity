# Système de Satisfaction Client (CSAT) - Documentation

## 📋 Vue d'ensemble

Le système de satisfaction client (CSAT) permet aux utilisateurs d'évaluer la qualité du service après la résolution de leur réclamation. Il inclut :

- ✅ Enquête après résolution avec étoiles (1-5) et commentaire
- ✅ Statistiques de satisfaction dans le dashboard admin
- ✅ Badge "Résolu avec satisfaction" pour les évaluations positives (4-5 étoiles)
- ✅ Affichage des évaluations déjà soumises

## 🗄️ Base de données

### Installation

Exécutez le script SQL pour créer la table `satisfactions` :

```sql
-- Fichier: .vscode/database/create_satisfactions_table.sql
```

La table contient :
- `id_satisfaction` : Identifiant unique
- `id_reclamation` : Référence à la réclamation
- `email` : Email de l'utilisateur
- `rating` : Note de 1 à 5 étoiles
- `commentaire` : Commentaire optionnel
- `date_evaluation` : Date de l'évaluation

## 📁 Fichiers créés/modifiés

### Nouveaux fichiers

1. **models/Satisfaction.php**
   - Modèle pour gérer les évaluations
   - Méthodes : save(), findByReclamationId(), findAll(), getStats()

2. **controllers/SatisfactionController.php**
   - Contrôleur pour gérer les opérations de satisfaction
   - Méthodes : addSatisfaction(), getSatisfactionByReclamationId(), getAllSatisfactions(), getStats()

3. **.vscode/database/create_satisfactions_table.sql**
   - Script SQL pour créer la table satisfactions

### Fichiers modifiés

1. **view/back/reclamback.php**
   - Ajout du traitement AJAX pour soumettre les évaluations
   - Ajout des statistiques de satisfaction dans le dashboard
   - Ajout du badge "Résolu avec satisfaction"
   - Affichage des évaluations dans les réclamations

2. **view/front/reclamation.php**
   - Ajout de l'interface d'enquête pour les réclamations résolues
   - JavaScript pour gérer la sélection des étoiles
   - Soumission AJAX des évaluations
   - Affichage des évaluations déjà soumises

## 🎯 Fonctionnalités

### Pour les utilisateurs (Front-end)

1. **Enquête de satisfaction**
   - Apparaît automatiquement pour les réclamations résolues
   - Système d'étoiles interactif (1-5)
   - Champ de commentaire optionnel
   - Soumission via AJAX

2. **Affichage des évaluations**
   - Les évaluations déjà soumises sont affichées
   - Affichage de la note et du commentaire

### Pour les administrateurs (Back-end)

1. **Statistiques de satisfaction**
   - Carte CSAT dans le dashboard avec :
     - Note moyenne (/5)
     - Pourcentage de satisfaction (4-5 étoiles)

2. **Badge "Résolu avec satisfaction"**
   - Apparaît automatiquement sur les réclamations résolues avec 4-5 étoiles
   - Badge vert avec icône étoile

3. **Affichage des évaluations**
   - Note visible dans chaque réclamation résolue
   - Commentaire affiché si disponible

## 🔧 Utilisation

### Pour les utilisateurs

1. Une fois une réclamation résolue, l'enquête apparaît automatiquement
2. Cliquez sur les étoiles pour sélectionner une note (1-5)
3. (Optionnel) Ajoutez un commentaire
4. Cliquez sur "Envoyer l'évaluation"
5. L'évaluation est enregistrée et affichée

### Pour les administrateurs

1. Les statistiques sont visibles dans le dashboard
2. Le badge "Résolu avec satisfaction" apparaît automatiquement
3. Les évaluations sont visibles dans chaque réclamation résolue

## 📊 Statistiques disponibles

- **Total d'évaluations** : Nombre total d'évaluations reçues
- **Note moyenne** : Moyenne de toutes les notes (sur 5)
- **Pourcentage de satisfaction** : % d'évaluations avec 4-5 étoiles
- **Répartition par note** : Détail par nombre d'étoiles

## 🎨 Interface

- **Étoiles interactives** : Effet hover et sélection
- **Design moderne** : Intégré au style existant
- **Notifications** : Messages de succès/erreur
- **Responsive** : Compatible mobile et desktop

## 🔒 Sécurité

- Validation des données côté serveur
- Vérification de la note (1-5)
- Protection contre les doublons (une évaluation par réclamation)
- Échappement HTML pour les commentaires

## 🚀 Prochaines améliorations possibles

- Graphiques de tendances
- Export des statistiques (CSV/PDF)
- Notifications email après évaluation
- Analyse de sentiment des commentaires
- Filtres par période pour les statistiques








