<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Ticket.php';
require_once __DIR__ . '/../libs/phpqrcode/qrlib.php';

class TicketController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Generate automatic ticket when participant joins an event
     * @param int $idParticipation
     * @param int $idEvenement
     * @return Ticket|false
     */
    public function generateTicket(int $idParticipation, int $idEvenement) {
        try {
            error_log("TicketController::generateTicket() START");
            error_log("Participation ID: $idParticipation, Event ID: $idEvenement");
            
            // Check if ticket already exists
            $existingTicket = $this->getTicketByParticipationAndEvent($idParticipation, $idEvenement);
            if ($existingTicket) {
                error_log("Ticket already exists, returning existing ticket");
                return $existingTicket;
            }

            error_log("Generating unique ticket number...");
            // Generate unique ticket number
            $ticketNumber = 'TKT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
            error_log("Ticket number: " . $ticketNumber);
            
            error_log("Generating QR code...");
            // Generate QR code
            $qrCodePath = $this->generateQRCode($ticketNumber, $idParticipation, $idEvenement);
            error_log("QR code path: " . $qrCodePath);
            
            error_log("Saving ticket to database...");
            // Save to database - using actual table columns (token, not ticket_number)
            $sql = "INSERT INTO tickets (id_participation, id_evenement, token, qr_code_path, status) 
                    VALUES (:id_participation, :id_evenement, :token, :qr_code_path, :status)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':id_participation' => $idParticipation,
                ':id_evenement' => $idEvenement,
                ':token' => $ticketNumber,
                ':qr_code_path' => $qrCodePath,
                ':status' => 'active'
            ]);
            
            error_log("INSERT execute result: " . ($result ? "TRUE" : "FALSE"));
            
            $lastId = (int)$this->pdo->lastInsertId();
            error_log("Last insert ID: " . $lastId);
            
            if ($lastId > 0) {
                error_log("✅ Ticket generated successfully with ID: " . $lastId);
                
                // Create and return ticket object
                $ticket = new Ticket($idParticipation, $idEvenement, $ticketNumber);
                $ticket->setIdTicket($lastId);
                $ticket->setQrCodePath($qrCodePath);
                
                return $ticket;
            } else {
                error_log("❌ Failed to get last insert ID");
                return false;
            }
        } catch (PDOException $e) {
            error_log("❌ PDOException in generateTicket: " . $e->getMessage());
            error_log("SQL Error Code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            error_log("❌ Exception in generateTicket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate QR code for ticket
     * @param string $token
     * @param int $idParticipation
     * @param int $idEvenement
     * @return string Path to QR code image
     */
    private function generateQRCode(string $ticketNumber, int $idParticipation, int $idEvenement): string {
        $qrCodeDir = __DIR__ . '/../view/front/qrcodes/';
        
        // Create directory if it doesn't exist
        if (!file_exists($qrCodeDir)) {
            mkdir($qrCodeDir, 0777, true);
        }
        
        // Generate unique filename
        $filename = 'ticket_' . $idParticipation . '_' . $idEvenement . '_' . time() . '.png';
        $filepath = $qrCodeDir . $filename;
        
        // QR code content (verification URL with ticket number)
        $qrContent = "TICKET:" . $ticketNumber . "|PARTICIPATION:" . $idParticipation . "|EVENT:" . $idEvenement;
        
        // Generate QR code (level L = Low error correction, size 4, margin 2)
        QRcode::png($qrContent, $filepath, QR_ECLEVEL_L, 4, 2);
        
        return 'qrcodes/' . $filename;
    }

    /**
     * Get ticket by participation and event
     * @param int $idParticipation
     * @param int $idEvenement
     * @return Ticket|null
     */
    public function getTicketByParticipationAndEvent(int $idParticipation, int $idEvenement): ?Ticket {
        try {
            $sql = "SELECT * FROM tickets WHERE id_participation = :id_participation AND id_evenement = :id_evenement";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id_participation' => $idParticipation,
                ':id_evenement' => $idEvenement
            ]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $this->hydrateTicket($row);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error fetching ticket: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all tickets for a participant by email
     * @param string $email
     * @return array
     */
    public function getTicketsByEmail(string $email): array {
        try {
            $sql = "SELECT t.*, p.nom_participant, p.email_participant, e.titre, e.date_debut, e.date_fin, e.lieu
                    FROM tickets t
                    INNER JOIN participation p ON t.id_participation = p.id_participation
                    INNER JOIN evenement e ON t.id_evenement = e.id_evenement
                    WHERE p.email_participant = :email
                    ORDER BY t.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':email' => $email]);
            
            $tickets = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tickets[] = [
                    'ticket' => $this->hydrateTicket($row),
                    'participant_name' => $row['nom_participant'],
                    'participant_email' => $row['email_participant'],
                    'event_title' => $row['titre'],
                    'event_start' => new DateTime($row['date_debut']),
                    'event_end' => new DateTime($row['date_fin']),
                    'event_location' => $row['lieu']
                ];
            }
            
            return $tickets;
        } catch (PDOException $e) {
            error_log("Error fetching tickets by email: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get ticket by token
     * @param string $token
     * @return Ticket|null
     */
    public function getTicketByToken(string $token): ?Ticket {
        try {
            $sql = "SELECT * FROM tickets WHERE token = :token";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':token' => $token]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $this->hydrateTicket($row);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error fetching ticket by token: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get ticket by ID with full details
     * @param int $idTicket
     * @return array|null
     */
    public function getTicketById(int $idTicket): ?array {
        try {
            $sql = "SELECT t.*, 
                           p.nom_participant as participant_name, 
                           p.email_participant as participant_email, 
                           e.titre as event_title, 
                           e.date_debut as event_start, 
                           e.date_fin as event_end, 
                           e.lieu as event_location
                    FROM tickets t
                    INNER JOIN participation p ON t.id_participation = p.id_participation
                    INNER JOIN evenement e ON t.id_evenement = e.id_evenement
                    WHERE t.id_ticket = :id_ticket";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_ticket' => $idTicket]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching ticket by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mark ticket as used
     * @param int $idTicket
     * @return bool
     */
    public function markAsUsed(int $idTicket): bool {
        try {
            $sql = "UPDATE tickets SET status = 'used', updated_at = NOW() WHERE id_ticket = :id_ticket";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_ticket' => $idTicket]);
        } catch (PDOException $e) {
            error_log("Error marking ticket as used: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel ticket
     * @param int $idTicket
     * @return bool
     */
    public function cancelTicket(int $idTicket): bool {
        try {
            $sql = "UPDATE tickets SET status = 'cancelled', updated_at = NOW() WHERE id_ticket = :id_ticket";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_ticket' => $idTicket]);
        } catch (PDOException $e) {
            error_log("Error cancelling ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hydrate Ticket object from database row
     * @param array $row
     * @return Ticket
     */
    private function hydrateTicket(array $row): Ticket {
        $ticket = new Ticket(
            (int)$row['id_participation'],
            (int)$row['id_evenement'],
            $row['token'],
            $row['qr_code_path']
        );
        
        $ticket->setIdTicket((int)$row['id_ticket']);
        $ticket->setStatus($row['status'] ?? 'active');
        
        if (isset($row['created_at'])) {
            $ticket->setCreatedAt(new DateTime($row['created_at']));
        }
        
        if (isset($row['updated_at'])) {
            $ticket->setUpdatedAt(new DateTime($row['updated_at']));
        }
        
        return $ticket;
    }

    /**
     * Delete ticket
     * @param int $idTicket
     * @return bool
     */
    public function deleteTicket(int $idTicket): bool {
        try {
            // Get ticket to delete QR code file
            $sql = "SELECT qr_code_path FROM tickets WHERE id_ticket = :id_ticket";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_ticket' => $idTicket]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && $row['qr_code_path']) {
                $qrPath = __DIR__ . '/../view/front/' . $row['qr_code_path'];
                if (file_exists($qrPath)) {
                    unlink($qrPath);
                }
            }
            
            // Delete from database
            $sql = "DELETE FROM tickets WHERE id_ticket = :id_ticket";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id_ticket' => $idTicket]);
        } catch (PDOException $e) {
            error_log("Error deleting ticket: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all tickets for a specific event
     * @param int $idEvenement
     * @return array
     */
    public function getTicketsByEvent(int $idEvenement): array {
        try {
            $sql = "SELECT t.*, p.nom_participant, p.email_participant
                    FROM tickets t
                    INNER JOIN participation p ON t.id_participation = p.id_participation
                    WHERE t.id_evenement = :id_evenement
                    ORDER BY t.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_evenement' => $idEvenement]);
            
            $tickets = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tickets[] = [
                    'ticket' => $this->hydrateTicket($row),
                    'participant_name' => $row['nom_participant'],
                    'participant_email' => $row['email_participant']
                ];
            }
            
            return $tickets;
        } catch (PDOException $e) {
            error_log("Error fetching tickets by event: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count tickets for a specific event
     * @param int $idEvenement
     * @return int
     */
    public function countTicketsByEvent(int $idEvenement): int {
        try {
            $sql = "SELECT COUNT(*) FROM tickets WHERE id_evenement = :id_evenement";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id_evenement' => $idEvenement]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting tickets: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count all tickets in the database
     * @return int
     */
    public function countAllTickets(): int {
        try {
            $sql = "SELECT COUNT(*) FROM tickets";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting all tickets: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all tickets with full details (for admin view)
     * @return array
     */
    public function getAllTickets(): array {
        try {
            $sql = "SELECT t.*, p.nom_participant, p.email_participant, e.titre as event_title, e.date_debut, e.date_fin, e.lieu
                    FROM tickets t
                    INNER JOIN participation p ON t.id_participation = p.id_participation
                    INNER JOIN evenement e ON t.id_evenement = e.id_evenement
                    ORDER BY t.created_at DESC";
            
            $stmt = $this->pdo->query($sql);
            
            $tickets = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tickets[] = [
                    'id_ticket' => $row['id_ticket'],
                    'token' => substr($row['token'], 0, 8) . '...',
                    'participant_name' => $row['nom_participant'],
                    'participant_email' => $row['email_participant'],
                    'event_title' => $row['event_title'],
                    'event_start' => $row['date_debut'],
                    'event_end' => $row['date_fin'],
                    'event_location' => $row['lieu'],
                    'status' => $row['status'],
                    'qr_code_path' => $row['qr_code_path'],
                    'created_at' => $row['created_at']
                ];
            }
            
            return $tickets;
        } catch (PDOException $e) {
            error_log("Error fetching all tickets: " . $e->getMessage());
            return [];
        }
    }
}