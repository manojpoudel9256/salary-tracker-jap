<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$store_filter = $_GET['store'] ?? 'All';
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Salary History',
        'filter_label' => 'Filter by Store',
        'all_stores' => 'All Stores',
        'total_earned' => 'Total Earned',
        'empty_state' => 'No salary records found.',
        'received' => 'Received',
        'month' => 'For Month',
        'notes' => 'Notes',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'back' => 'Dashboard',
        'export' => 'Export CSV'
    ],
    'jp' => [
        'title' => '給与履歴',
        'filter_label' => '店舗で絞り込み',
        'all_stores' => 'すべての店舗',
        'total_earned' => '総支給額',
        'empty_state' => '給与記録が見つかりません。',
        'received' => '受領日',
        'month' => '対象月',
        'notes' => 'メモ',
        'edit' => '編集',
        'delete' => '削除',
        'confirm_delete' => '本当にこの記録を削除しますか？',
        'back' => 'ダッシュボード',
        'export' => 'CSV出力'
    ]
];
$t = $trans[$lang];

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
function getStoreColor($store)
{
    // Premium Palette matching the theme
    $colors = ['#4facfe', '#00f2fe', '#43e97b', '#fa709a', '#fee140', '#a18cd1', '#fbc2eb', '#8fd3f4'];
    return $colors[crc32($store) % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['title'] ?> | Salary Tracker</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- App Icons -->
    <link rel="icon" href="icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="icon/apple-touch-icon.png">

    <style>
        :root {
            /* Premium Color Palette */
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;

            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-blur: blur(20px);

            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);

            --accent-primary: #4facfe;
            --btn-radius: 16px;

            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-mid), var(--bg-gradient-end));
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: var(--text-primary);
            padding-top: calc(var(--safe-top) + 20px);
            padding-bottom: calc(var(--safe-bottom) + 80px);
            /* Extra padding for total footer */
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            text-decoration: none;
            backdrop-filter: var(--glass-blur);
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 20px;
            margin: 0;
        }

        .export-btn {
            font-size: 18px;
            color: var(--accent-primary);
            text-decoration: none;
        }

        /* Filter */
        .filter-container {
            margin-bottom: 24px;
        }

        .filter-select {
            width: 100%;
            padding: 16px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--btn-radius);
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: var(--glass-blur);
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
        }

        .filter-select option {
            background: #24243e;
            color: white;
        }

        /* Salary Cards */
        .salary-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .salary-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s;
            animation: fadeInUp 0.5s ease-out backwards;
        }

        .salary-card:active {
            transform: scale(0.98);
        }

        .card-left-border {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
        }

        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .store-name {
            font-weight: 600;
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 2px;
        }

        .record-date {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .salary-amount {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 24px;
            color: var(--text-primary);
        }

        .card-details {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-row {
            display: flex;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .detail-label {
            min-width: 80px;
        }

        .card-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .action-chip {
            flex: 1;
            padding: 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: background 0.2s;
        }

        .chip-edit {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chip-delete {
            background: rgba(220, 53, 69, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Sticky Total Footer */
        .footer-total {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 40px);
            max-width: 460px;
            background: rgba(20, 20, 30, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 100;
        }

        .total-label {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .total-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #4facfe;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Staggered Delay */
        <?php for ($i = 1; $i <= 10; $i++): ?>
            .salary-card:nth-child(<?= $i ?>) {
                animation-delay:
                    <?= $i * 0.05 ?>
                    s;
            }

        <?php endfor; ?>
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="page-title"><?= $t['title'] ?></h2>
            <a href="export_csv.php" class="export-btn">
                <i class="fas fa-file-export"></i>
            </a>
        </div>

        <!-- Filter -->
        <div class="filter-container">
            <form method="get" id="filterForm">
                <select name="store" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                    <option value="All"><?= $t['all_stores'] ?></option>
                    <?php foreach ($available_stores as $store): ?>
                        <option value="<?= htmlspecialchars($store) ?>" <?= $store_filter == $store ? 'selected' : '' ?>>
                            <?= htmlspecialchars($store) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- List -->
        <div class="salary-list">
            <?php if (count($salaries) > 0): ?>
                <?php foreach ($salaries as $salary): ?>
                    <?php $color = getStoreColor($salary['store_name']); ?>

                    <div class="salary-card">
                        <div class="card-left-border" style="background: <?= $color ?>;"></div>

                        <div class="card-header-row">
                            <div class="store-info">
                                <div class="store-name" style="color: <?= $color ?>;">
                                    <?= htmlspecialchars($salary['store_name']) ?></div>
                                <div class="record-date">
                                    <i class="far fa-calendar-check me-1"></i> <?= htmlspecialchars($salary['received_date']) ?>
                                </div>
                            </div>
                            <div class="salary-amount">
                                ¥<?= number_format($salary['amount']) ?>
                            </div>
                        </div>

                        <div class="card-details">
                            <div class="detail-row">
                                <div class="detail-label"><?= $t['month'] ?>:</div>
                                <div><?= date('Y-m', strtotime($salary['working_month'])) ?></div>
                            </div>
                            <?php if (!empty($salary['notes'])): ?>
                                <div class="detail-row">
                                    <div class="detail-label"><?= $t['notes'] ?>:</div>
                                    <div><?= mb_strimwidth(htmlspecialchars($salary['notes']), 0, 30, "...") ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <a href="edit_salary.php?id=<?= $salary['id'] ?>" class="action-chip chip-edit">
                                <i class="fas fa-pen me-1"></i> <?= $t['edit'] ?>
                            </a>
                            <a href="delete_salary.php?id=<?= $salary['id'] ?>" class="action-chip chip-delete"
                                onclick="return confirm('<?= $t['confirm_delete'] ?>');">
                                <i class="fas fa-trash me-1"></i> <?= $t['delete'] ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open empty-icon"></i>
                    <p><?= $t['empty_state'] ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Spacing for Fixed Footer -->
        <div style="height: 60px;"></div>

        <!-- Sticky Total Footer -->
        <div class="footer-total">
            <span class="total-label"><?= $t['total_earned'] ?></span>
            <span class="total-value">¥<?= number_format($total) ?></span>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>