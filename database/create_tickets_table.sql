-- Table pour les tickets d'événements
CREATE TABLE IF NOT EXISTS tickets (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    id_participation INT NOT NULL,
    id_evenement INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    qr_code_path VARCHAR(500),
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_participation) REFERENCES participation(id_participation) ON DELETE CASCADE,
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
    UNIQUE KEY unique_ticket_per_participant (id_participation, id_evenement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index pour améliorer les performances
CREATE INDEX idx_token ON tickets(token);
CREATE INDEX idx_participation_email ON tickets(id_participation);
CREATE INDEX idx_evenement ON tickets(id_evenement);
CREATE INDEX idx_status ON tickets(status);
