<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$store_filter = $_GET['store'] ?? 'All';

// Get all unique store names for filter dropdown
$stmt_stores = $pdo->prepare("SELECT DISTINCT store_name FROM salaries WHERE user_id = ? ORDER BY store_name ASC");
$stmt_stores->execute([$user_id]);
$available_stores = $stmt_stores->fetchAll(PDO::FETCH_COLUMN);

// Build query based on filter
$sql = "SELECT * FROM salaries WHERE user_id = ?";
$params = [$user_id];

if ($store_filter !== 'All') {
    $sql .= " AND store_name = ?";
    $params[] = $store_filter;
}

$sql .= " ORDER BY received_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$salaries = $stmt->fetchAll();

// Calculate total
$total = array_sum(array_column($salaries, 'amount'));

// Generate consistent color for each store
function getStoreColor($store) {
    $colors = ['#4361ee', '#06d6a0', '#f72585', '#ffbe0b', '#7209b7', '#4cc9f0', '#ff6b35', '#06ffa5'];
    return $colors[crc32($store) % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>あなたの給与記録</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #06d6a0;
            --warning: #ffbe0b;
        }

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
            padding: 20px;
        }

        @keyframes gradientBG {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
            text-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInDown 1s ease-out;
        }

        .page-header h2 {
            font-weight: 700;
            display: inline-block;
        }

        .page-header h2::after {
            content: '💰';
            margin-left: 15px;
            animation: bounceMoney 2s infinite ease-in-out;
        }

        @keyframes bounceMoney {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(10deg); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-section {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .filter-label {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-select {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 8px 15px;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
        }

        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
        }

        .data-card {
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: cardEntrance 1s ease-out;
            border: none;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table thead {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            color: white;
        }

        .table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .table tbody tr {
            transition: all 0.3s;
        }

        .table tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: rgba(0, 0, 0, 0.05);
        }

        .store-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-action {
            border-radius: 50px;
            padding: 6px 15px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-warning {
            background-color: var(--warning);
            border: none;
            color: #212529;
            box-shadow: 0 5px 10px rgba(255, 190, 11, 0.3);
        }

        .btn-danger {
            background-color: var(--accent);
            border: none;
            color: white;
            box-shadow: 0 5px 10px rgba(247, 37, 133, 0.3);
        }

        .btn-warning:hover, .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-export {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            color: white;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            transform: translateY(-3px);
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4);
            color: white;
        }

        .total-section {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px;
            padding: 15px 25px;
            margin-top: 25px;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-weight: 600;
            font-size: 18px;
            margin: 0;
        }

        .total-amount {
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 0;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .back-link:hover {
            transform: translateX(-5px);
            color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .container { padding: 0 10px; }
            .page-header h2 { font-size: 24px; }
            .table thead th, .table td { padding: 10px; font-size: 13px; }
            .btn-action { padding: 4px 8px; font-size: 12px; }
            .total-section { flex-direction: column; align-items: flex-start; gap: 5px; }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h2>あなたの給与記録</h2>
        </div>

        <div class="filter-section">
            <form method="get" class="d-flex justify-content-start align-items-center gap-3">
                <label for="store" class="filter-label">
                    <i class="fas fa-filter"></i> 店舗で絞り込む:
                </label>
                <select name="store" id="store" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="All" <?= $store_filter == 'All' ? 'selected' : '' ?>>すべての店舗</option>
                    <?php foreach ($available_stores as $store): ?>
                        <option value="<?= htmlspecialchars($store) ?>" <?= $store_filter == $store ? 'selected' : '' ?>>
                            <?= htmlspecialchars($store) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="data-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>店舗</th>
                            <th>金額</th>
                            <th>受領日</th>
                            <th>勤務月</th>
                            <th>メモ</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($salaries) > 0): ?>
                            <?php foreach ($salaries as $salary): ?>
                                <tr>
                                    <td>
                                        <span class="store-badge" style="background-color: <?= getStoreColor($salary['store_name']) ?>">
                                            <i class="fas fa-store me-2"></i><?= htmlspecialchars($salary['store_name']) ?>
                                        </span>
                                    </td>
                                    <td><i class="fas fa-yen-sign me-2"></i><?= number_format($salary['amount']) ?></td>
                                    <td><i class="fas fa-calendar-check me-2"></i><?= htmlspecialchars($salary['received_date']) ?></td>
                                    <td><i class="fas fa-calendar-alt me-2"></i><?= date('F Y', strtotime($salary['working_month'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($salary['notes'])) ?></td>
                                    <td>
                                        <a href="edit_salary.php?id=<?= $salary['id'] ?>" class="btn btn-warning btn-action">
                                            <i class="fas fa-edit"></i> 編集
                                        </a>
                                        <a href="delete_salary.php?id=<?= $salary['id'] ?>" class="btn btn-danger btn-action" onclick="return confirm('この記録を削除しますか？');">
                                            <i class="fas fa-trash"></i> 削除
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">給与記録が見つかりませんでした。</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
            <a href="export_csv.php" class="btn-export">
                <i class="fas fa-file-export"></i> CSVとしてエクスポート
            </a>
        </div>

        <div class="total-section">
            <p class="total-label">総給与額:</p>
            <p class="total-amount">¥<?= number_format($total) ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>