<?php
require_once __DIR__ . '/config.php';
session_start();

$pdo = getDB();

echo "<h1>Cart Debugger</h1>";
echo "Session ID: " . session_id() . "<br>";
if (isset($_SESSION['cart_session_id'])) {
    echo "Cart Session ID: " . $_SESSION['cart_session_id'] . "<br>";
} else {
    echo "Cart Session ID: NOT SET<br>";
}

echo "<h2>Table: panier</h2>";
$stmt = $pdo->query("SELECT * FROM panier");
$paniers = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($paniers)) {
    echo "Table 'panier' is empty.<br>";
} else {
    echo "<table border='1'><tr>";
    foreach (array_keys($paniers[0]) as $key) echo "<th>$key</th>";
    echo "</tr>";
    foreach ($paniers as $row) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>$val</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Table: panier_items</h2>";
$stmt = $pdo->query("SELECT * FROM panier_items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($items)) {
    echo "Table 'panier_items' is empty.<br>";
} else {
    echo "<table border='1'><tr>";
    foreach (array_keys($items[0]) as $key) echo "<th>$key</th>";
    echo "</tr>";
    foreach ($items as $row) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>$val</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Table: produit (First 5)</h2>";
$stmt = $pdo->query("SELECT produit_id, name, price FROM produit LIMIT 5");
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($produits)) {
    echo "Table 'produit' is empty.<br>";
} else {
    echo "<table border='1'><tr>";
    foreach (array_keys($produits[0]) as $key) echo "<th>$key</th>";
    echo "</tr>";
    foreach ($produits as $row) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>$val</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
