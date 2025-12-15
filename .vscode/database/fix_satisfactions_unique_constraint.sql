-- Script pour corriger la contrainte UNIQUE de la table satisfactions
-- Permet plusieurs évaluations par réclamation (une par email)

-- Supprimer l'ancienne contrainte UNIQUE sur id_reclamation seul
ALTER TABLE satisfactions DROP INDEX IF EXISTS unique_reclamation;

-- Ajouter une nouvelle contrainte UNIQUE sur (id_reclamation, email)
-- Cela permet plusieurs évaluations par réclamation, mais une seule par email
ALTER TABLE satisfactions ADD UNIQUE KEY unique_reclamation_email (id_reclamation, email);

