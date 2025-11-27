-- Add creator_email field to evenement table
-- Run this in phpMyAdmin or MySQL client

ALTER TABLE evenement 
ADD COLUMN createur_email VARCHAR(255) DEFAULT NULL AFTER lieu,
ADD INDEX idx_createur_email (createur_email);

-- Update existing events with a default creator (optional)
-- UPDATE evenement SET createur_email = 'admin@foxunity.com' WHERE createur_email IS NULL;
