-- Table pour stocker les réponses des admins aux réclamations
CREATE TABLE IF NOT EXISTS `responses` (
  `id_response` int(11) NOT NULL AUTO_INCREMENT,
  `id_reclamation` int(11) NOT NULL,
  `response_text` text NOT NULL,
  `admin_name` varchar(255) NOT NULL DEFAULT 'Admin',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_response`),
  KEY `id_reclamation` (`id_reclamation`),
  CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamations` (`id_reclamation`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

