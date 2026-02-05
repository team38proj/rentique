<?php
session_start();
require_once 'connectdb.php';

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    header("Location: login.php");
    exit;
}

// Single query to check admin role
$stmt = $db->prepare("SELECT role FROM users WHERE uid = ? LIMIT 1");
$stmt->execute([(int)$uid]);
if ($stmt->fetchColumn() !== 'admin') {
    die("Access denied.");
}

/* Aggregate queries optimized */
$aggregates = $db->query("
  SELECT 
    COALESCE(SUM(platform_fee), 0) AS platform_fees,
    COALESCE(SUM(line_total), 0) AS total_rent,
    COALESCE(SUM(line_total + platform_fee), 0) AS total_spent
  FROM order_items
")->fetch(PDO::FETCH_ASSOC);

$platformFees = (float)$aggregates['platform_fees'];
$totalRentSpent = (float)$aggregates['total_rent'];
$totalSpentAll = (float)$aggregates['total_spent'];

/* Top sellers by rental revenue */
$topSellers = $db->query("
  SELECT oi.seller_uid, u.username,
         SUM(oi.line_total) AS revenue,
         SUM(oi.platform_fee) AS fees_generated
  FROM order_items oi
  JOIN users u ON u.uid = oi.seller_uid
  GROUP BY oi.seller_uid, u.username
  ORDER BY revenue DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* Top buyers by total spent */
$topBuyers = $db->query("
  SELECT o.buyer_uid, u.username,
         SUM(oi.line_total + oi.platform_fee) AS spent
  FROM orders o
  JOIN users u ON u.uid = o.buyer_uid
  JOIN order_items oi ON oi.order_id_fk = o.id
  GROUP BY o.buyer_uid, u.username
  ORDER BY spent DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Rental Report</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 18px; color: #111; }
    h2 { margin: 0 0 10px; }
    h3 { margin: 18px 0 8px; }
    .meta { margin-bottom: 12px; }

    .kpi { border-collapse: collapse; width: 100%; max-width: 900px; }
    .kpi td { border: 1px solid #ddd; padding: 8px; }

    .tbl { border-collapse: collapse; width: 100%; max-width: 900px; margin-top: 10px; }
    .tbl td, .tbl th { border: 1px solid #ddd; padding: 8px; }
    .tbl th { background: #f3f3f3; text-align: left; }

    .right { text-align: right; }

    .actions { margin: 12px 0; }
    .actions button {
      padding: 10px 14px;
      border-radius: 10px;
      border: 1px solid #222;
      background: #222;
      color: #fff;
      cursor: pointer;
    }
    .actions button:hover { opacity: 0.9; }

    @media print { .actions { display:none; } }
  </style>
</head>
<body>

  <h2>Rental Report</h2>
  <div class="meta">Generated: <?php echo h(date('Y-m-d H:i:s')); ?></div>

  <div class="actions">
    <button type="button" onclick="window.print()">Save as PDF or Print</button>
  </div>

  <table class="kpi">
    <tr>
      <td>Money made from platform fees</td>
      <td class="right">£<?php echo number_format($platformFees, 2); ?></td>
    </tr>
    <tr>
      <td>Total money spent on renting items (excluding fees)</td>
      <td class="right">£<?php echo number_format($totalRentSpent, 2); ?></td>
    </tr>
    <tr>
      <td>Total spent including platform fees</td>
      <td class="right">£<?php echo number_format($totalSpentAll, 2); ?></td>
    </tr>
  </table>

  <h3>Top sellers (by rental revenue)</h3>
  <table class="tbl">
    <thead>
      <tr>
        <th>Seller</th>
        <th class="right">Rental revenue</th>
        <th class="right">Platform fees generated</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$topSellers): ?>
        <tr><td colspan="3">No data</td></tr>
      <?php else: ?>
        <?php foreach ($topSellers as $s): ?>
          <tr>
            <td><?php echo h($s['username']); ?></td>
            <td class="right">£<?php echo number_format((float)$s['revenue'], 2); ?></td>
            <td class="right">£<?php echo number_format((float)$s['fees_generated'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Top buyers (by total spent)</h3>
  <table class="tbl">
    <thead>
      <tr>
        <th>Buyer</th>
        <th class="right">Total spent</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$topBuyers): ?>
        <tr><td colspan="2">No data</td></tr>
      <?php else: ?>
        <?php foreach ($topBuyers as $b): ?>
          <tr>
            <td><?php echo h($b['username']); ?></td>
            <td class="right">£<?php echo number_format((float)$b['spent'], 2); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

</body>
</html>