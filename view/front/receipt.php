<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/config.php';

// Ensure user is logged in
if (!UserController::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();
$currentUserId = $currentUser->getId();
$negotiationId = $_GET['id'] ?? null;

if (!$negotiationId) {
    die("Invalid Receipt ID.");
}

$db = getDB();

// Fetch transaction details
// We look for the 'bought' or 'sold' action associated with this negotiation
// We need to verify the current user is involved (either as buyer or seller)
$stmt = $db->prepare("
    SELECT th.*, u.username as actor_name, u.email as actor_email
    FROM trade_history th 
    JOIN users u ON th.user_id = u.id
    WHERE th.negotiation_id = ? AND th.action IN ('bought', 'sold')
");
$stmt->execute([$negotiationId]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$records) {
    die("Receipt not found.");
}

// Identify Buyer and Seller from records
$buyer = null;
$seller = null;
$item = null;
$date = null;
$price = 0;

foreach ($records as $record) {
    if ($record['action'] === 'bought') {
        $buyer = [
            'id' => $record['user_id'],
            'username' => $record['actor_name'],
            'email' => $record['actor_email']
        ];
        // Common details
        $item = $record['skin_name'];
        $price = $record['skin_price'];
        $date = $record['created_at'];
    } elseif ($record['action'] === 'sold') {
        $seller = [
            'id' => $record['user_id'],
            'username' => $record['actor_name'],
            'email' => $record['actor_email']
        ];
    }
}

// Security Check: Is current user authorized?
if ($currentUserId != ($buyer['id'] ?? 0) && $currentUserId != ($seller['id'] ?? 0)) {
    // Check if user is admin? For now, strict check.
    if ($currentUser->getRole() !== 'admin' && $currentUser->getRole() !== 'superadmin') {
         die("Unauthorized access to this receipt.");
    }
}

// Fallback if one record is missing (rare but possible in old data)
if (!$item && isset($records[0])) {
    $item = $records[0]['skin_name'];
    $price = $records[0]['skin_price'];
    $date = $records[0]['created_at'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trade Receipt #<?= htmlspecialchars($negotiationId) ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f5f5f5; color: #333; padding: 40px; }
        .receipt { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border: 1px solid #ddd; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; margin-bottom: 5px; color: #ff7a00; }
        .info { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: bold; color: #666; }
        .item-table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        .item-table th, .item-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .total { text-align: right; font-size: 20px; font-weight: bold; margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        .print-btn { display: block; margin: 20px auto; padding: 10px 20px; background: #ff7a00; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 5px; }
        @media print {
            .print-btn { display: none; }
            body { background: #fff; padding: 0; }
            .receipt { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

    <div class="receipt">
        <div class="header">
            <div class="logo">FOX UNITY MARKETPLACE</div>
            <div>Official Transaction Receipt</div>
        </div>

        <div class="info">
            <div>
                <span class="label">Date:</span> <?= date('M j, Y g:i A', strtotime($date)) ?><br>
                <span class="label">Receipt ID:</span> #<?= htmlspecialchars(substr($negotiationId, -8)) ?>
            </div>
            <div style="text-align: right;">
                <span class="label">Status:</span> COMPLETED
            </div>
        </div>

        <div class="info" style="margin-top:20px;">
            <div>
                <span class="label">Seller:</span><br>
                <?= htmlspecialchars($seller['username'] ?? 'Unknown User') ?><br>
                <small><?= htmlspecialchars($seller['email'] ?? '') ?></small>
            </div>
            <div style="text-align: right;">
                <span class="label">Buyer:</span><br>
                <?= htmlspecialchars($buyer['username'] ?? 'Unknown User') ?><br>
                <small><?= htmlspecialchars($buyer['email'] ?? '') ?></small>
            </div>
        </div>

        <table class="item-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($item) ?></strong><br>
                        <small>Virtual Item / Skin Trade</small>
                    </td>
                    <td style="text-align:right;">$<?= number_format((float)$price, 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total">
            TOTAL PAID: $<?= number_format((float)$price, 2) ?>
        </div>

        <div class="footer">
            Thank you for trading on Fox Unity.<br>
            This is an automated receipt for digital goods.<br>
            Transaction ID: <?= htmlspecialchars($negotiationId) ?>
        </div>
    </div>

    <button onclick="window.print()" class="print-btn">Download / Print Receipt</button>

</body>
</html>
