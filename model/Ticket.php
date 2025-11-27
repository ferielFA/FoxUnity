<?php

class Ticket {
    private ?int $idTicket = null;
    private int $idParticipation;
    private int $idEvenement;
    private string $token;
    private ?string $qrCodePath = null;
    private string $status = 'active'; // active, used, cancelled
    private ?DateTime $createdAt = null;
    private ?DateTime $updatedAt = null;

    // Constructor
    public function __construct(int $idParticipation, int $idEvenement, string $token, ?string $qrCodePath = null) {
        $this->idParticipation = $idParticipation;
        $this->idEvenement = $idEvenement;
        $this->token = $token;
        $this->qrCodePath = $qrCodePath;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    // Getters
    public function getIdTicket(): ?int {
        return $this->idTicket;
    }

    public function getIdParticipation(): int {
        return $this->idParticipation;
    }

    public function getIdEvenement(): int {
        return $this->idEvenement;
    }

    public function getToken(): string {
        return $this->token;
    }

    public function getQrCodePath(): ?string {
        return $this->qrCodePath;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getCreatedAt(): ?DateTime {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime {
        return $this->updatedAt;
    }

    // Setters
    public function setIdTicket(?int $idTicket): void {
        $this->idTicket = $idTicket;
    }

    public function setIdParticipation(int $idParticipation): void {
        $this->idParticipation = $idParticipation;
    }

    public function setIdEvenement(int $idEvenement): void {
        $this->idEvenement = $idEvenement;
    }

    public function setToken(string $token): void {
        $this->token = $token;
    }

    public function setQrCodePath(?string $qrCodePath): void {
        $this->qrCodePath = $qrCodePath;
    }

    public function setStatus(string $status): void {
        if (in_array($status, ['active', 'used', 'cancelled'])) {
            $this->status = $status;
        }
    }

    public function setCreatedAt(?DateTime $createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    // Helper methods
    public function isActive(): bool {
        return $this->status === 'active';
    }

    public function isUsed(): bool {
        return $this->status === 'used';
    }

    public function isCancelled(): bool {
        return $this->status === 'cancelled';
    }

    public function markAsUsed(): void {
        $this->status = 'used';
        $this->updatedAt = new DateTime();
    }

    public function cancel(): void {
        $this->status = 'cancelled';
        $this->updatedAt = new DateTime();
    }

    // Generate unique token
    public static function generateUniqueToken(): string {
        return bin2hex(random_bytes(32)) . '-' . time();
    }
}
