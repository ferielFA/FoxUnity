<?php
require_once __DIR__ . '/../../controller/UserController.php';

$controller = new UserController();
$controller->logout();

header('Location: login.php');
exit();
?>