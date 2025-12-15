-- Ajouter la colonne catégorie à la table reclamations
ALTER TABLE reclamations 
ADD COLUMN IF NOT EXISTS categorie VARCHAR(50) DEFAULT 'Other' AFTER description;

-- Créer un index pour améliorer les performances de filtrage
CREATE INDEX IF NOT EXISTS idx_categorie ON reclamations(categorie);

-- Mettre à jour les réclamations existantes avec une catégorie par défaut si NULL
UPDATE reclamations SET categorie = 'Other' WHERE categorie IS NULL;

