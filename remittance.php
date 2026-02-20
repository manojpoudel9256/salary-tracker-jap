<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Remittance',
        'subtitle' => 'Manage transfers to your home country',
        'total_salary' => 'Total Salary',
        'total_sent' => 'Total Sent',
        'remaining' => 'Remaining',
        'records' => 'Records',
        'add' => 'Add Remittance',
        'add_sub' => 'New transfer',
        'history' => 'History',
        'history_sub' => 'View records',
        'summary' => 'Summary',
        'summary_sub' => 'Detailed analysis',
        'chart' => 'Charts',
        'chart_sub' => 'Visual data',
        'back' => 'Dashboard',
        'back_sub' => 'Go back',
        'sent_pct' => 'sent'
    ],
    'jp' => [
        'title' => '送金管理',
        'subtitle' => '母国への送金を管理',
        'total_salary' => '総給与',
        'total_sent' => '総送金額',
        'remaining' => '残高',
        'records' => '記録数',
        'add' => '送金を追加',
        'add_sub' => '新規送金',
        'history' => '送金履歴',
        'history_sub' => '記録を見る',
        'summary' => '詳細分析',
        'summary_sub' => 'サマリー',
        'chart' => 'チャート',
        'chart_sub' => 'グラフ分析',
        'back' => 'ダッシュボード',
        'back_sub' => '戻る',
        'sent_pct' => '送金済'
    ]
];
$t = $trans[$lang];

// Get quick stats
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_records = $stmt->fetch()['count'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 1) : 0;
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
            --accent-primary: #4facfe;
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
            padding-bottom: calc(var(--safe-bottom) + 20px);
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
            margin-bottom: 28px;
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

        /* Progress Ring */
        .progress-hero {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            margin-bottom: 20px;
            animation: fadeInUp 0.4s ease-out 0.1s backwards;
        }

        .progress-ring-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 12px;
        }

        .progress-ring {
            transform: rotate(-90deg);
        }

        .progress-ring-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 8;
        }

        .progress-ring-fill {
            fill: none;
            stroke: url(#ringGradient);
            stroke-width: 8;
            stroke-linecap: round;
            stroke-dasharray: 314;
            stroke-dashoffset:
                <?= 314 - (314 * $percentage_sent / 100) ?>
            ;
            transition: stroke-dashoffset 1s ease-out;
        }

        .progress-pct {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 24px;
        }

        .progress-label {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 24px;
            animation: fadeInUp 0.4s ease-out 0.2s backwards;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 14px;
            text-align: center;
        }

        .stat-icon {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 10px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 17px;
        }

        .stat-salary .stat-icon {
            color: #4facfe;
        }

        .stat-salary .stat-value {
            color: #4facfe;
        }

        .stat-sent .stat-icon {
            color: #fa709a;
        }

        .stat-sent .stat-value {
            color: #fa709a;
        }

        .stat-remaining .stat-icon {
            color: #43e97b;
        }

        .stat-remaining .stat-value {
            color: #43e97b;
        }

        .stat-records .stat-icon {
            color: #ffd700;
        }

        .stat-records .stat-value {
            color: #ffd700;
        }

        /* Action Cards */
        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
            animation: fadeInUp 0.4s ease-out 0.3s backwards;
        }

        .action-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 20px 14px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .action-card:active {
            transform: scale(0.96);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .action-title {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 2px;
            color: var(--text-primary);
        }

        .action-sub {
            font-size: 10px;
            color: var(--text-secondary);
        }

        /* Icon backgrounds */
        .icon-green {
            background: rgba(67, 233, 123, 0.2);
            color: #43e97b;
        }

        .icon-blue {
            background: rgba(79, 172, 254, 0.2);
            color: #4facfe;
        }

        .icon-pink {
            background: rgba(250, 112, 154, 0.2);
            color: #fa709a;
        }

        .icon-purple {
            background: rgba(161, 140, 209, 0.2);
            color: #a18cd1;
        }

        /* Full-width back card */
        .action-back {
            grid-column: 1 / -1;
            flex-direction: row;
            padding: 14px 20px;
            gap: 12px;
        }

        .action-back .action-icon {
            margin-bottom: 0;
        }

        .action-back .action-text {
            text-align: left;
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
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><i class="fas fa-paper-plane me-2"></i><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <!-- Progress Ring -->
        <div class="progress-hero">
            <div class="progress-ring-container">
                <svg class="progress-ring" width="120" height="120" viewBox="0 0 120 120">
                    <defs>
                        <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#fa709a" />
                            <stop offset="100%" style="stop-color:#fee140" />
                        </linearGradient>
                    </defs>
                    <circle class="progress-ring-bg" cx="60" cy="60" r="50" />
                    <circle class="progress-ring-fill" cx="60" cy="60" r="50" />
                </svg>
                <div class="progress-pct"><?= $percentage_sent ?>%</div>
            </div>
            <div class="progress-label"><?= $t['sent_pct'] ?></div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-salary">
                <i class="fas fa-wallet stat-icon"></i>
                <div class="stat-label"><?= $t['total_salary'] ?></div>
                <div class="stat-value">¥<?= number_format($total_salary) ?></div>
            </div>
            <div class="stat-card stat-sent">
                <i class="fas fa-paper-plane stat-icon"></i>
                <div class="stat-label"><?= $t['total_sent'] ?></div>
                <div class="stat-value">¥<?= number_format($total_sent) ?></div>
            </div>
            <div class="stat-card stat-remaining">
                <i class="fas fa-piggy-bank stat-icon"></i>
                <div class="stat-label"><?= $t['remaining'] ?></div>
                <div class="stat-value">¥<?= number_format($remaining) ?></div>
            </div>
            <div class="stat-card stat-records">
                <i class="fas fa-file-invoice-dollar stat-icon"></i>
                <div class="stat-label"><?= $t['records'] ?></div>
                <div class="stat-value"><?= $total_records ?></div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="actions-grid">
            <a href="add_remittance.php" class="action-card">
                <div class="action-icon icon-green"><i class="fas fa-plus"></i></div>
                <div class="action-title"><?= $t['add'] ?></div>
                <div class="action-sub"><?= $t['add_sub'] ?></div>
            </a>

            <a href="view_remittance.php" class="action-card">
                <div class="action-icon icon-blue"><i class="fas fa-list"></i></div>
                <div class="action-title"><?= $t['history'] ?></div>
                <div class="action-sub"><?= $t['history_sub'] ?></div>
            </a>

            <a href="remittance_summary.php" class="action-card">
                <div class="action-icon icon-pink"><i class="fas fa-chart-line"></i></div>
                <div class="action-title"><?= $t['summary'] ?></div>
                <div class="action-sub"><?= $t['summary_sub'] ?></div>
            </a>

            <a href="remittance_chart.php" class="action-card">
                <div class="action-icon icon-purple"><i class="fas fa-chart-pie"></i></div>
                <div class="action-title"><?= $t['chart'] ?></div>
                <div class="action-sub"><?= $t['chart_sub'] ?></div>
            </a>

            <a href="dashboard.php" class="action-card action-back">
                <div class="action-icon" style="background:rgba(255,215,0,0.2);color:#ffd700;"><i
                        class="fas fa-home"></i></div>
                <div class="action-text">
                    <div class="action-title"><?= $t['back'] ?></div>
                    <div class="action-sub"><?= $t['back_sub'] ?></div>
                </div>
            </a>
        </div>

    </div>
</body>

</html>