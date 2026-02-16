<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM remittances WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header('Location: view_remittance.php');
    exit;
}

// Get filter from URL
$filter_recipient = $_GET['filter'] ?? 'all';

// Fetch all unique recipients for filter buttons
$stmt = $pdo->prepare("SELECT DISTINCT recipient FROM remittances WHERE user_id = ? AND recipient IS NOT NULL AND recipient != '' ORDER BY recipient");
$stmt->execute([$user_id]);
$recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch remittances based on filter
if ($filter_recipient === 'all') {
    $stmt = $pdo->prepare("SELECT * FROM remittances WHERE user_id = ? ORDER BY date_sent DESC, created_at DESC");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM remittances WHERE user_id = ? AND recipient = ? ORDER BY date_sent DESC, created_at DESC");
    $stmt->execute([$user_id, $filter_recipient]);
}
$remittances = $stmt->fetchAll();

// Calculate totals based on filter
$total_sent = 0;
foreach ($remittances as $r) {
    $total_sent += $r['amount'];
}

// Get total salary (always show full amount)
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$salary_data = $stmt->fetch();
$total_salary = $salary_data['total'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>送金記録の閲覧</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(125deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 15px 10px;
        }

        @keyframes gradientBG {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }

        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: 24px;
            padding: 0 10px;
        }

        h2 i {
            font-size: 22px;
            margin-right: 8px;
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title i {
            font-size: 16px;
            color: #f72585;
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-btn {
            padding: 10px 18px;
            border: 2px solid #dee2e6;
            background: white;
            color: #495057;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 44px;
        }

        .filter-btn:hover {
            border-color: #f72585;
            color: #f72585;
            background: #fff5f8;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: white;
            border-color: #f72585;
        }

        .filter-btn:active {
            transform: scale(0.95);
        }

        .filter-count {
            background: rgba(255, 255, 255, 0.3);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .active .filter-count {
            background: rgba(255, 255, 255, 0.3);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            text-align: center;
            transition: transform 0.2s;
        }

        .summary-card:active {
            transform: scale(0.98);
        }

        .summary-card.salary {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }

        .summary-card.sent {
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: white;
        }

        .summary-card.remaining {
            background: linear-gradient(135deg, #06d6a0, #02c39a);
            color: white;
        }

        .summary-card.percentage {
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
            color: white;
        }

        .summary-icon {
            font-size: 32px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-label {
            font-size: 11px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
            line-height: 1.3;
        }

        .summary-value {
            font-size: 22px;
            font-weight: 700;
            word-break: break-all;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
            overflow: hidden;
        }

        .card-body {
            padding: 0;
        }

        /* Mobile Card Style for Remittances */
        .remittance-item {
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .remittance-item:last-child {
            border-bottom: none;
        }

        .remittance-item:active {
            background: rgba(255, 190, 11, 0.1);
        }

        .remittance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .remittance-date {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }

        .remittance-date i {
            margin-right: 5px;
            font-size: 12px;
        }

        .remittance-amount {
            font-size: 22px;
            font-weight: 700;
            color: #f72585;
        }

        .remittance-details {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .detail-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: #e3f2fd;
            color: #1976d2;
        }

        .detail-badge i {
            font-size: 11px;
        }

        .detail-badge.purpose {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-action {
            padding: 10px 18px;
            font-size: 13px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            min-height: 44px;
            min-width: 44px;
            justify-content: center;
        }

        .btn-action:active {
            transform: scale(0.95);
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffa500, #ff8c00);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-add {
            background: linear-gradient(135deg, #06d6a0, #02c39a);
            color: white;
        }

        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            min-height: 48px;
            margin: 5px;
        }

        .back-link:active {
            transform: scale(0.98);
            background: rgba(255, 255, 255, 0.3);
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #adb5bd;
        }

        .no-data p {
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .bottom-nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 20px;
            gap: 10px;
        }

        /* Desktop Table View (576px and up) */
        @media (min-width: 576px) {
            h2 {
                font-size: 28px;
            }

            .filter-section {
                padding: 24px 20px;
            }

            .filter-title {
                font-size: 15px;
            }

            .filter-btn {
                font-size: 14px;
                padding: 11px 20px;
            }

            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }

            .summary-card {
                padding: 24px 20px;
            }

            .summary-icon {
                font-size: 36px;
            }

            .summary-label {
                font-size: 12px;
            }

            .summary-value {
                font-size: 26px;
            }

            /* Switch to table view on desktop */
            .remittance-item {
                display: none;
            }

            .table-responsive {
                display: block !important;
                border-radius: 10px;
                overflow: hidden;
            }

            .table {
                margin-bottom: 0;
                font-size: 14px;
            }

            .table thead {
                background: linear-gradient(135deg, #ffbe0b, #ffa500);
                color: white;
            }

            .table thead th {
                border: none;
                padding: 16px 12px;
                font-weight: 600;
                font-size: 13px;
            }

            .table tbody tr {
                transition: all 0.3s;
            }

            .table tbody tr:hover {
                background-color: rgba(255, 190, 11, 0.1);
            }

            .table tbody td {
                padding: 16px 12px;
                vertical-align: middle;
                border-bottom: 1px solid #f0f0f0;
            }

            .badge {
                padding: 6px 12px;
                border-radius: 15px;
                font-size: 12px;
                font-weight: 600;
            }

            .btn-sm {
                padding: 8px 16px;
                font-size: 13px;
                border-radius: 20px;
                min-height: 38px;
            }

            .btn-warning {
                background: linear-gradient(135deg, #ffa500, #ff8c00);
                border: none;
                color: white;
            }

            .btn-danger {
                background: linear-gradient(135deg, #dc3545, #c82333);
                border: none;
            }

            .action-buttons-desktop {
                display: flex;
                gap: 8px;
                justify-content: center;
            }
        }

        /* Hide table on mobile */
        @media (max-width: 575px) {
            .table-responsive {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            body {
                padding: 30px 20px;
            }

            h2 {
                font-size: 32px;
                margin-bottom: 30px;
            }

            .summary-grid {
                margin-bottom: 30px;
                gap: 20px;
            }

            .card-body {
                padding: 0;
            }

            .back-link {
                font-size: 15px;
                padding: 15px 28px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-history"></i> 送金記録の閲覧</h2>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                カテゴリーで絞り込み / Filter by Category
            </div>
            <div class="filter-buttons">
                <a href="view_remittance.php?filter=all"
                    class="filter-btn <?= $filter_recipient === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i>
                    すべて / All
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM remittances WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $all_count = $stmt->fetchColumn();
                    ?>
                    <span class="filter-count">
                        <?= $all_count ?>
                    </span>
                </a>

                <?php foreach ($recipients as $recipient): ?>
                    <a href="view_remittance.php?filter=<?= urlencode($recipient) ?>"
                        class="filter-btn <?= $filter_recipient === $recipient ? 'active' : '' ?>">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($recipient) ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM remittances WHERE user_id = ? AND recipient = ?");
                        $stmt->execute([$user_id, $recipient]);
                        $count = $stmt->fetchColumn();
                        ?>
                        <span class="filter-count">
                            <?= $count ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card salary">
                <i class="fas fa-wallet summary-icon"></i>
                <div class="summary-label">総給与<br>Total Salary</div>
                <div class="summary-value">¥
                    <?= number_format($total_salary) ?>
                </div>
            </div>

            <div class="summary-card sent">
                <i class="fas fa-paper-plane summary-icon"></i>
                <div class="summary-label">
                    <?= $filter_recipient === 'all' ? '総送金額<br>Total Sent' : '送金額<br>Sent (' . htmlspecialchars($filter_recipient) . ')' ?>
                </div>
                <div class="summary-value">¥
                    <?= number_format($total_sent) ?>
                </div>
            </div>

            <div class="summary-card remaining">
                <i class="fas fa-piggy-bank summary-icon"></i>
                <div class="summary-label">残高<br>Remaining</div>
                <div class="summary-value">¥
                    <?= number_format($remaining) ?>
                </div>
            </div>

            <div class="summary-card percentage">
                <i class="fas fa-chart-pie summary-icon"></i>
                <div class="summary-label">送金率<br>Sent %</div>
                <div class="summary-value">
                    <?= $percentage_sent ?>%
                </div>
            </div>
        </div>

        <!-- Remittance List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($remittances) > 0): ?>

                    <!-- Mobile Card View -->
                    <?php foreach ($remittances as $rem): ?>
                        <div class="remittance-item">
                            <div class="remittance-header">
                                <span class="remittance-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('Y/m/d', strtotime($rem['date_sent'])) ?>
                                </span>
                                <span class="remittance-amount">¥
                                    <?= number_format($rem['amount']) ?>
                                </span>
                            </div>

                            <?php if ($rem['recipient'] || $rem['purpose']): ?>
                                <div class="remittance-details">
                                    <?php if ($rem['recipient']): ?>
                                        <span class="detail-badge">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($rem['recipient']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($rem['purpose']): ?>
                                        <span class="detail-badge purpose">
                                            <i class="fas fa-tag"></i>
                                            <?= htmlspecialchars(substr($rem['purpose'], 0, 20)) . (strlen($rem['purpose']) > 20 ? '...' : '') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="action-buttons">
                                <a href="edit_remittance.php?id=<?= $rem['id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i> 編集
                                </a>
                                <a href="?delete=<?= $rem['id'] ?>&filter=<?= urlencode($filter_recipient) ?>"
                                    class="btn-action btn-delete" onclick="return confirm('この送金記録を削除してもよろしいですか？')">
                                    <i class="fas fa-trash"></i> 削除
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Desktop Table View -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>日付<br><small>Date</small></th>
                                    <th>金額<br><small>Amount</small></th>
                                    <th>受取人<br><small>Recipient</small></th>
                                    <th>目的<br><small>Purpose</small></th>
                                    <th>操作<br><small>Action</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($remittances as $rem): ?>
                                    <tr>
                                        <td>
                                            <?= date('Y/m/d', strtotime($rem['date_sent'])) ?>
                                        </td>
                                        <td><strong>¥
                                                <?= number_format($rem['amount']) ?>
                                            </strong></td>
                                        <td>
                                            <?= $rem['recipient'] ? '<span class="badge bg-info">' . htmlspecialchars($rem['recipient']) . '</span>' : '-' ?>
                                        </td>
                                        <td>
                                            <?= $rem['purpose'] ? htmlspecialchars(substr($rem['purpose'], 0, 30)) . (strlen($rem['purpose']) > 30 ? '...' : '') : '-' ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons-desktop">
                                                <a href="edit_remittance.php?id=<?= $rem['id'] ?>"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?= $rem['id'] ?>&filter=<?= urlencode($filter_recipient) ?>"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('この送金記録を削除してもよろしいですか？')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <p>
                            <?php if ($filter_recipient === 'all'): ?>
                                まだ送金記録がありません。<br>送金を追加しましょう！
                            <?php else: ?>
                                「
                                <?= htmlspecialchars($filter_recipient) ?>」の送金記録がありません。
                            <?php endif; ?>
                        </p>
                        <a href="add_remittance.php" class="btn-action btn-add">
                            <i class="fas fa-plus-circle"></i> 送金を追加
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bottom-nav">
            <a href="add_remittance.php" class="back-link">
                <i class="fas fa-plus-circle"></i> 送金を追加
            </a>
            <a href="remittance.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボード
            </a>
        </div>
    </div>
</body>

</html>