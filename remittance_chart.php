<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Remittance Charts',
        'subtitle' => 'Visual breakdown of transfers',
        'total_salary' => 'Total Salary',
        'total_sent' => 'Total Sent',
        'remaining' => 'Remaining',
        'donut_title' => 'Salary Allocation',
        'donut_section' => 'Salary vs Sent',
        'donut_hint' => 'How much of your salary was sent',
        'remaining_label' => 'Remaining',
        'sent_label' => 'Sent',
        'pie_section' => 'By Recipient',
        'pie_title' => 'Sent by Recipient',
        'pie_hint' => 'Breakdown by who received the money',
        'other' => 'Other'
    ],
    'jp' => [
        'title' => '送金チャート分析',
        'subtitle' => '送金の視覚的な分析',
        'total_salary' => '総給与',
        'total_sent' => '総送金額',
        'remaining' => '残高',
        'donut_title' => '給与の使い道',
        'donut_section' => '給与 vs 送金',
        'donut_hint' => '給与のうちどれくらい送金したか',
        'remaining_label' => '残高',
        'sent_label' => '送金済',
        'pie_section' => '受取人別',
        'pie_title' => '受取人別送金額',
        'pie_hint' => '誰にどれだけ送金したか',
        'other' => 'その他'
    ]
];
$t = $trans[$lang];

// Get totals
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$remaining = $total_salary - $total_sent;
$pct_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 1) : 0;

// Recipient breakdown
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(NULLIF(recipient, ''), ?) as recipient,
        SUM(amount) as total
    FROM remittances 
    WHERE user_id = ?
    GROUP BY recipient
    ORDER BY total DESC
");
$stmt->execute([$t['other'], $user_id]);
$recipients_data = $stmt->fetchAll();

$recipient_labels = [];
$recipient_amounts = [];
$neon_colors = ['#fa709a', '#4facfe', '#43e97b', '#ffd700', '#a18cd1', '#ff6b6b', '#38f9d7', '#f093fb', '#00c9ff', '#fbc2eb'];

foreach ($recipients_data as $data) {
    $recipient_labels[] = $data['recipient'];
    $recipient_amounts[] = $data['total'];
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
            animation: fadeInUp 0.4s ease-out 0.1s backwards;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 12px;
            padding: 12px 8px;
            text-align: center;
        }

        .stat-label {
            font-size: 9px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 15px;
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

        /* Section Title */
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 15px;
            color: var(--text-secondary);
            margin: 16px 0 10px 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--accent);
            font-size: 14px;
        }

        /* Chart Card */
        .chart-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 20px 16px;
            margin-bottom: 16px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
        }

        .chart-hint {
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.35);
            margin-top: 12px;
            font-style: italic;
        }

        .chart-hint i {
            margin-right: 4px;
        }

        /* Legend */
        .legend-list {
            list-style: none;
            padding: 0;
            margin-top: 16px;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            margin-bottom: 6px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            transition: all 0.2s;
        }

        .legend-item:active {
            transform: scale(0.98);
        }

        .legend-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 5px;
            flex-shrink: 0;
        }

        .legend-name {
            font-size: 13px;
            font-weight: 600;
        }

        .legend-right {
            text-align: right;
        }

        .legend-amount {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px;
        }

        .legend-pct {
            font-size: 10px;
            color: var(--text-secondary);
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
            <a href="remittance.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><i class="fas fa-chart-pie me-2"></i><?= $t['title'] ?></h1>
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
        </div>

        <!-- Donut: Salary vs Sent -->
        <div class="section-title"><i class="fas fa-chart-pie"></i> <?= $t['donut_section'] ?></div>
        <div class="chart-card" style="animation: fadeInUp 0.4s ease-out 0.2s backwards;">
            <div class="chart-wrapper">
                <canvas id="donutChart"></canvas>
            </div>
            <div class="chart-hint">
                <i class="fas fa-info-circle"></i> <?= $t['donut_hint'] ?>
            </div>
        </div>

        <!-- Pie: By Recipient -->
        <div class="section-title"><i class="fas fa-users"></i> <?= $t['pie_section'] ?></div>
        <div class="chart-card" style="animation: fadeInUp 0.4s ease-out 0.3s backwards;">
            <div class="chart-wrapper">
                <canvas id="pieChart"></canvas>
            </div>

            <!-- Legend -->
            <ul class="legend-list">
                <?php foreach ($recipients_data as $index => $data): ?>
                    <?php $pct = $total_sent > 0 ? round(($data['total'] / $total_sent) * 100, 1) : 0; ?>
                    <li class="legend-item">
                        <div class="legend-info">
                            <div class="legend-color" style="background:<?= $neon_colors[$index % count($neon_colors)] ?>;">
                            </div>
                            <span class="legend-name"><?= htmlspecialchars($data['recipient']) ?></span>
                        </div>
                        <div class="legend-right">
                            <div class="legend-amount">¥<?= number_format($data['total']) ?></div>
                            <div class="legend-pct"><?= $pct ?>%</div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="chart-hint">
                <i class="fas fa-info-circle"></i> <?= $t['pie_hint'] ?>
            </div>
        </div>

    </div>

    <script>
        const mobile = window.innerWidth < 768;

        // Donut Chart
        new Chart(document.getElementById('donutChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['<?= $t['remaining_label'] ?>', '<?= $t['sent_label'] ?>'],
                datasets: [{
                    data: [<?= max(0, $remaining) ?>, <?= $total_sent ?>],
                    backgroundColor: ['#43e97b', '#fa709a'],
                    borderWidth: 2,
                    borderColor: 'rgba(15, 12, 41, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?= $t['donut_title'] ?>',
                        color: '#ffffff',
                        font: { size: mobile ? 14 : 16, weight: 'bold', family: 'Outfit' },
                        padding: { top: 0, bottom: 10 }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255,255,255,0.7)',
                            font: { size: 12 },
                            padding: 14,
                            boxWidth: 14,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        padding: 10,
                        callbacks: {
                            label: ctx => {
                                const total = <?= $total_salary > 0 ? $total_salary : 1 ?>;
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': ¥' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Pie Chart
        new Chart(document.getElementById('pieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($recipient_labels) ?>,
                datasets: [{
                    data: <?= json_encode($recipient_amounts) ?>,
                    backgroundColor: <?= json_encode(array_slice($neon_colors, 0, count($recipient_labels))) ?>,
                    borderWidth: 2,
                    borderColor: 'rgba(15, 12, 41, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?= $t['pie_title'] ?>',
                        color: '#ffffff',
                        font: { size: mobile ? 14 : 16, weight: 'bold', family: 'Outfit' },
                        padding: { top: 0, bottom: 10 }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        padding: 10,
                        callbacks: {
                            label: ctx => {
                                const total = <?= $total_sent > 0 ? $total_sent : 1 ?>;
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': ¥' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>