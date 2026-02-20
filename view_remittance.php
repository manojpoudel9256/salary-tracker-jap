<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Remittance History',
        'subtitle' => 'View and manage transfer records',
        'filter_by' => 'Filter by Recipient',
        'all' => 'All',
        'total_salary' => 'Total Salary',
        'total_sent' => 'Total Sent',
        'remaining' => 'Remaining',
        'sent_pct' => 'Sent %',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm_delete' => 'Delete this remittance record?',
        'no_data' => 'No remittance records yet',
        'no_data_sub' => 'Add a remittance to get started!',
        'no_filter_data' => 'No records for this recipient',
        'add_new' => 'Add Remittance',
        'total_footer' => 'Total Sent',
        'show_more' => 'Show more',
        'show_less' => 'Show less'
    ],
    'jp' => [
        'title' => '送金履歴',
        'subtitle' => '送金記録の閲覧・管理',
        'filter_by' => '受取人で絞り込み',
        'all' => 'すべて',
        'total_salary' => '総給与',
        'total_sent' => '総送金額',
        'remaining' => '残高',
        'sent_pct' => '送金率',
        'edit' => '編集',
        'delete' => '削除',
        'confirm_delete' => 'この送金記録を削除してもよろしいですか？',
        'no_data' => 'まだ送金記録がありません',
        'no_data_sub' => '送金を追加しましょう！',
        'no_filter_data' => 'この受取人の記録がありません',
        'add_new' => '送金を追加',
        'total_footer' => '合計送金額',
        'show_more' => 'もっと見る',
        'show_less' => '閉じる'
    ]
];
$t = $trans[$lang];

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

// Fetch all unique recipients for filter
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

// Calculate totals
$total_sent = 0;
foreach ($remittances as $r) {
    $total_sent += $r['amount'];
}

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 1) : 0;

// Get counts for filter badges
$stmt_all = $pdo->prepare("SELECT COUNT(*) FROM remittances WHERE user_id = ?");
$stmt_all->execute([$user_id]);
$all_count = $stmt_all->fetchColumn();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['title'] ?> | Salary Tracker</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link rel="icon" href="icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="icon/apple-touch-icon.png">

    <style>
        :root {
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-blur: blur(20px);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --accent: #fa709a;
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
            padding-bottom: calc(var(--safe-bottom) + 100px);
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
            margin-bottom: 20px;
            animation: fadeInDown 0.5s ease-out;
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
            margin-right: 16px;
            flex-shrink: 0;
        }

        .header-text h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 22px;
            margin: 0;
        }

        .header-text p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
            animation: fadeInUp 0.4s ease-out 0.1s backwards;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 12px;
            padding: 10px 6px;
            text-align: center;
        }

        .stat-label {
            font-size: 9px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px;
        }

        .stat-1 .stat-value {
            color: #4facfe;
        }

        .stat-2 .stat-value {
            color: #fa709a;
        }

        .stat-3 .stat-value {
            color: #43e97b;
        }

        .stat-4 .stat-value {
            color: #ffd700;
        }

        /* Filter Pills */
        .filter-row {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 4px;
            animation: fadeInUp 0.4s ease-out 0.2s backwards;
        }

        .filter-row::-webkit-scrollbar {
            display: none;
        }

        .filter-pill {
            flex-shrink: 0;
            padding: 8px 14px;
            border-radius: 10px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-pill.active {
            background: rgba(250, 112, 154, 0.2);
            border-color: rgba(250, 112, 154, 0.4);
            color: #fa709a;
        }

        .filter-pill:active {
            transform: scale(0.95);
        }

        .pill-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 10px;
        }

        .active .pill-count {
            background: rgba(250, 112, 154, 0.3);
        }

        /* Remittance Cards */
        .record-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 10px;
            animation: fadeInUp 0.4s ease-out backwards;
        }

        .record-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .record-date {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .record-date i {
            margin-right: 4px;
        }

        .record-amount {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 20px;
            color: #fa709a;
        }

        .record-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-recipient {
            background: rgba(79, 172, 254, 0.15);
            color: #4facfe;
        }

        .badge-purpose {
            background: rgba(161, 140, 209, 0.15);
            color: #a18cd1;
        }

        /* Expandable Note */
        .note-section {
            margin-top: 8px;
            margin-bottom: 12px;
            padding: 10px 12px;
            background: rgba(161, 140, 209, 0.08);
            border: 1px solid rgba(161, 140, 209, 0.15);
            border-radius: 10px;
        }

        .note-text {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.5;
            word-break: break-word;
        }

        .note-text.collapsed {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .note-toggle {
            display: inline-block;
            margin-top: 6px;
            font-size: 11px;
            font-weight: 600;
            color: #a18cd1;
            cursor: pointer;
            padding: 2px 0;
        }

        .note-toggle i {
            margin-left: 4px;
            font-size: 10px;
            transition: transform 0.2s;
        }

        .note-toggle.expanded i {
            transform: rotate(180deg);
        }

        .record-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-act {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
            border: none;
        }

        .btn-act:active {
            transform: scale(0.95);
        }

        .btn-edit-r {
            background: rgba(255, 165, 0, 0.2);
            color: #ffa500;
        }

        .btn-delete-r {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b7a;
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

        .add-btn-empty {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            padding: 12px 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
        }

        /* Sticky Footer */
        .sticky-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 12, 41, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            padding: 12px 20px;
            padding-bottom: calc(12px + var(--safe-bottom));
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .footer-total {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .footer-amount {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #fa709a;
        }

        .footer-add {
            padding: 10px 20px;
            border-radius: 12px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        @media (max-width: 400px) {
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .stat-value {
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="remittance.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card stat-1">
                <div class="stat-label"><i class="fas fa-wallet"></i> <?= $t['total_salary'] ?></div>
                <div class="stat-value">¥<?= number_format($total_salary) ?></div>
            </div>
            <div class="stat-card stat-2">
                <div class="stat-label"><i class="fas fa-paper-plane"></i> <?= $t['total_sent'] ?></div>
                <div class="stat-value">¥<?= number_format($total_sent) ?></div>
            </div>
            <div class="stat-card stat-3">
                <div class="stat-label"><i class="fas fa-piggy-bank"></i> <?= $t['remaining'] ?></div>
                <div class="stat-value">¥<?= number_format($remaining) ?></div>
            </div>
            <div class="stat-card stat-4">
                <div class="stat-label"><i class="fas fa-chart-pie"></i> <?= $t['sent_pct'] ?></div>
                <div class="stat-value"><?= $percentage_sent ?>%</div>
            </div>
        </div>

        <!-- Filter Pills -->
        <?php if (!empty($recipients)): ?>
            <div class="filter-row">
                <a href="view_remittance.php?filter=all"
                    class="filter-pill <?= $filter_recipient === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> <?= $t['all'] ?>
                    <span class="pill-count"><?= $all_count ?></span>
                </a>
                <?php foreach ($recipients as $recipient): ?>
                    <?php
                    $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM remittances WHERE user_id = ? AND recipient = ?");
                    $stmt_c->execute([$user_id, $recipient]);
                    $count = $stmt_c->fetchColumn();
                    ?>
                    <a href="view_remittance.php?filter=<?= urlencode($recipient) ?>"
                        class="filter-pill <?= $filter_recipient === $recipient ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($recipient) ?>
                        <span class="pill-count"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Records -->
        <?php if (count($remittances) > 0): ?>
            <?php foreach ($remittances as $i => $rem): ?>
                <div class="record-card" style="animation-delay: <?= 0.3 + ($i * 0.05) ?>s;">
                    <div class="record-top">
                        <span class="record-date">
                            <i class="fas fa-calendar"></i>
                            <?= date('Y/m/d', strtotime($rem['date_sent'])) ?>
                        </span>
                        <span class="record-amount">¥<?= number_format($rem['amount']) ?></span>
                    </div>

                    <?php if ($rem['recipient']): ?>
                        <div class="record-badges">
                            <span class="badge-tag badge-recipient">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($rem['recipient']) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($rem['purpose']): ?>
                        <?php $purpose_text = htmlspecialchars($rem['purpose']);
                        $is_long = mb_strlen($rem['purpose']) > 30; ?>
                        <div class="note-section">
                            <div class="note-text <?= $is_long ? 'collapsed' : '' ?>">
                                <i class="fas fa-tag" style="color:#a18cd1;margin-right:4px;"></i> <?= $purpose_text ?>
                            </div>
                            <?php if ($is_long): ?>
                                <span class="note-toggle" onclick="toggleNote(this)">
                                    <?= $t['show_more'] ?> <i class="fas fa-chevron-down"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="record-actions">
                        <a href="edit_remittance.php?id=<?= $rem['id'] ?>" class="btn-act btn-edit-r">
                            <i class="fas fa-edit"></i> <?= $t['edit'] ?>
                        </a>
                        <a href="?delete=<?= $rem['id'] ?>&filter=<?= urlencode($filter_recipient) ?>"
                            class="btn-act btn-delete-r" onclick="return confirm('<?= $t['confirm_delete'] ?>')">
                            <i class="fas fa-trash"></i> <?= $t['delete'] ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="record-card">
                <div class="empty-state">
                    <i class="fas fa-inbox empty-icon"></i>
                    <p style="font-weight:600;">
                        <?= $filter_recipient === 'all' ? $t['no_data'] : $t['no_filter_data'] ?>
                    </p>
                    <p style="font-size:13px;opacity:0.7;"><?= $t['no_data_sub'] ?></p>
                    <a href="add_remittance.php" class="add-btn-empty">
                        <i class="fas fa-plus-circle"></i> <?= $t['add_new'] ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sticky Footer -->
    <div class="sticky-footer">
        <div>
            <div class="footer-total"><?= $t['total_footer'] ?></div>
            <div class="footer-amount">¥<?= number_format($total_sent) ?></div>
        </div>
        <a href="add_remittance.php" class="footer-add">
            <i class="fas fa-plus"></i> <?= $t['add_new'] ?>
        </a>
    </div>

    <script>
        const SHOW_MORE = '<?= addslashes($t['show_more']) ?>';
        const SHOW_LESS = '<?= addslashes($t['show_less']) ?>';

        function toggleNote(el) {
            const noteText = el.previousElementSibling;
            const isCollapsed = noteText.classList.contains('collapsed');

            if (isCollapsed) {
                noteText.classList.remove('collapsed');
                el.innerHTML = SHOW_LESS + ' <i class="fas fa-chevron-down"></i>';
                el.classList.add('expanded');
            } else {
                noteText.classList.add('collapsed');
                el.innerHTML = SHOW_MORE + ' <i class="fas fa-chevron-down"></i>';
                el.classList.remove('expanded');
            }
        }
    </script>
</body>

</html>