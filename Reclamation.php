<?php
class Reclamation {
    private $id_reclamation;
    private $full_name;
    private $email;
    private $subject;
    private $message;
    private $date_creation;
    private $statut;

    public function __construct($full_name, $email, $subject, $message, $statut = 'nouveau') {
        $this->full_name = $full_name;
        $this->email = $email;
        $this->subject = $subject;
        $this->message = $message;
        $this->date_creation = date('Y-m-d H:i:s');
        $this->statut = $statut;
    }

    // Getters
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getSubject() { return $this->subject; }
    public function getMessage() { return $this->message; }
    public function getDateCreation() { return $this->date_creation; }
    public function getStatut() { return $this->statut; }

    // Setters
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setFullName($name) { $this->full_name = $name; }
    public function setEmail($email) { $this->email = $email; }
    public function setSubject($subject) { $this->subject = $subject; }
    public function setMessage($message) { $this->message = $message; }
    public function setStatut($statut) { $this->statut = $statut; }
}
?>