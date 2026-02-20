<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Remittance Summary',
        'subtitle' => 'Salary vs remittance analytics',
        'overall' => 'Overall',
        'total_salary' => 'Total Salary',
        'total_sent' => 'Total Sent',
        'remaining' => 'Remaining',
        'sent_pct' => 'Sent %',
        'chart_title' => 'Salary vs Sent (12 Months)',
        'chart_section' => 'Monthly Trend',
        'monthly_section' => 'Monthly Cumulative',
        'yearly_section' => 'By Year',
        'salary_label' => 'Salary',
        'sent_label' => 'Sent',
        'remaining_label' => 'Remaining',
        'this_month' => 'This Month',
        'cumul_salary' => 'Total Salary',
        'cumul_sent' => 'Total Sent',
        'year_suffix' => ''
    ],
    'jp' => [
        'title' => '送金サマリー',
        'subtitle' => '給与と送金の分析',
        'overall' => '全期間の概要',
        'total_salary' => '総給与',
        'total_sent' => '総送金額',
        'remaining' => '残高',
        'sent_pct' => '送金率',
        'chart_title' => '給与 vs 送金 (12ヶ月)',
        'chart_section' => '月別推移',
        'monthly_section' => '月別累計',
        'yearly_section' => '年別詳細',
        'salary_label' => '給与',
        'sent_label' => '送金',
        'remaining_label' => '残高',
        'this_month' => '今月',
        'cumul_salary' => '累計給与',
        'cumul_sent' => '累計送金',
        'year_suffix' => '年'
    ]
];
$t = $trans[$lang];

// Overall totals
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 1) : 0;

// Monthly breakdown (last 12 months)
$monthly_data = [];
$cumulative_salary = 0;
$cumulative_sent = 0;

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));

    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ? AND DATE_FORMAT(working_month, '%Y-%m') = ?");
    $stmt->execute([$user_id, $month]);
    $monthly_salary = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ? AND DATE_FORMAT(date_sent, '%Y-%m') = ?");
    $stmt->execute([$user_id, $month]);
    $monthly_sent = $stmt->fetch()['total'] ?? 0;

    $cumulative_salary += $monthly_salary;
    $cumulative_sent += $monthly_sent;

    $monthly_data[] = [
        'month' => $month,
        'month_label' => $lang === 'jp' ? date('Y年m月', strtotime($month . '-01')) : date('M Y', strtotime($month . '-01')),
        'monthly_salary' => $monthly_salary,
        'monthly_sent' => $monthly_sent,
        'salary' => $cumulative_salary,
        'sent' => $cumulative_sent,
        'remaining' => $cumulative_salary - $cumulative_sent,
        'percentage' => $cumulative_salary > 0 ? round(($cumulative_sent / $cumulative_salary) * 100, 1) : 0
    ];
}

// Yearly breakdown
$stmt = $pdo->prepare("SELECT YEAR(working_month) as year, SUM(amount) as total FROM salaries WHERE user_id = ? GROUP BY year ORDER BY year DESC");
$stmt->execute([$user_id]);
$yearly_salary = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("SELECT YEAR(date_sent) as year, SUM(amount) as total FROM remittances WHERE user_id = ? GROUP BY year ORDER BY year DESC");
$stmt->execute([$user_id]);
$yearly_sent = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$years = array_unique(array_merge(array_keys($yearly_salary), array_keys($yearly_sent)));
rsort($years);

$yearly_data = [];
foreach ($years as $year) {
    $salary = $yearly_salary[$year] ?? 0;
    $sent = $yearly_sent[$year] ?? 0;
    $yearly_data[] = [
        'year' => $year,
        'salary' => $salary,
        'sent' => $sent,
        'remaining' => $salary - $sent,
        'percentage' => $salary > 0 ? round(($sent / $salary) * 100, 1) : 0
    ];
}

// Chart data
$chart_labels = array_column($monthly_data, 'month_label');
$chart_salary = array_column($monthly_data, 'salary');
$chart_sent = array_column($monthly_data, 'sent');
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

        /* Section Title */
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 15px;
            color: var(--text-secondary);
            margin: 20px 0 10px 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--accent);
            font-size: 14px;
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

        /* Chart Card */
        .chart-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            animation: fadeInUp 0.4s ease-out 0.2s backwards;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 260px;
        }

        /* Data Cards */
        .data-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            margin-bottom: 16px;
            overflow: hidden;
            animation: fadeInUp 0.4s ease-out 0.3s backwards;
        }

        .data-row {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .data-row:last-child {
            border-bottom: none;
        }

        .data-row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .data-month {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 14px;
        }

        .data-this-month {
            font-size: 10px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .pct-badge {
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .pct-high {
            background: rgba(250, 112, 154, 0.2);
            color: #fa709a;
        }

        .pct-mid {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
        }

        .pct-low {
            background: rgba(67, 233, 123, 0.2);
            color: #43e97b;
        }

        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        .data-cell {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }

        .data-cell-label {
            font-size: 9px;
            color: var(--text-secondary);
            margin-bottom: 2px;
        }

        .data-cell-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 13px;
        }

        .c-salary {
            color: #4facfe;
        }

        .c-sent {
            color: #fa709a;
        }

        .c-remain {
            color: #43e97b;
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
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="remittance.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><i class="fas fa-chart-line me-2"></i><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="section-title"><i class="fas fa-globe"></i> <?= $t['overall'] ?></div>
        <div class="stats-row">
            <div class="stat-card stat-1">
                <div class="stat-label"><?= $t['total_salary'] ?></div>
                <div class="stat-value">¥<?= number_format($total_salary) ?></div>
            </div>
            <div class="stat-card stat-2">
                <div class="stat-label"><?= $t['total_sent'] ?></div>
                <div class="stat-value">¥<?= number_format($total_sent) ?></div>
            </div>
            <div class="stat-card stat-3">
                <div class="stat-label"><?= $t['remaining'] ?></div>
                <div class="stat-value">¥<?= number_format($remaining) ?></div>
            </div>
            <div class="stat-card stat-4">
                <div class="stat-label"><?= $t['sent_pct'] ?></div>
                <div class="stat-value"><?= $percentage_sent ?>%</div>
            </div>
        </div>

        <!-- Chart -->
        <div class="section-title"><i class="fas fa-chart-area"></i> <?= $t['chart_section'] ?></div>
        <div class="chart-card">
            <div class="chart-wrapper">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- Monthly Cumulative -->
        <div class="section-title"><i class="fas fa-calendar-alt"></i> <?= $t['monthly_section'] ?></div>
        <div class="data-card">
            <?php foreach ($monthly_data as $data): ?>
                <?php if ($data['salary'] > 0 || $data['sent'] > 0): ?>
                    <div class="data-row">
                        <div class="data-row-header">
                            <div>
                                <div class="data-month"><?= $data['month_label'] ?></div>
                                <?php if ($data['monthly_salary'] > 0 || $data['monthly_sent'] > 0): ?>
                                    <div class="data-this-month">
                                        <?= $t['this_month'] ?>: <?= $t['salary_label'] ?>
                                        ¥<?= number_format($data['monthly_salary']) ?> / <?= $t['sent_label'] ?>
                                        ¥<?= number_format($data['monthly_sent']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span
                                class="pct-badge <?= $data['percentage'] > 50 ? 'pct-high' : ($data['percentage'] > 30 ? 'pct-mid' : 'pct-low') ?>">
                                <?= $data['percentage'] ?>%
                            </span>
                        </div>
                        <div class="data-grid">
                            <div class="data-cell">
                                <div class="data-cell-label"><?= $t['cumul_salary'] ?></div>
                                <div class="data-cell-value c-salary">¥<?= number_format($data['salary']) ?></div>
                            </div>
                            <div class="data-cell">
                                <div class="data-cell-label"><?= $t['cumul_sent'] ?></div>
                                <div class="data-cell-value c-sent">¥<?= number_format($data['sent']) ?></div>
                            </div>
                            <div class="data-cell">
                                <div class="data-cell-label"><?= $t['remaining_label'] ?></div>
                                <div class="data-cell-value c-remain">¥<?= number_format($data['remaining']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- Yearly Breakdown -->
        <div class="section-title"><i class="fas fa-calendar-check"></i> <?= $t['yearly_section'] ?></div>
        <div class="data-card">
            <?php foreach ($yearly_data as $data): ?>
                <div class="data-row">
                    <div class="data-row-header">
                        <div class="data-month"><?= $data['year'] ?><?= $t['year_suffix'] ?></div>
                        <span
                            class="pct-badge <?= $data['percentage'] > 50 ? 'pct-high' : ($data['percentage'] > 30 ? 'pct-mid' : 'pct-low') ?>">
                            <?= $data['percentage'] ?>%
                        </span>
                    </div>
                    <div class="data-grid">
                        <div class="data-cell">
                            <div class="data-cell-label"><?= $t['salary_label'] ?></div>
                            <div class="data-cell-value c-salary">¥<?= number_format($data['salary']) ?></div>
                        </div>
                        <div class="data-cell">
                            <div class="data-cell-label"><?= $t['sent_label'] ?></div>
                            <div class="data-cell-value c-sent">¥<?= number_format($data['sent']) ?></div>
                        </div>
                        <div class="data-cell">
                            <div class="data-cell-label"><?= $t['remaining_label'] ?></div>
                            <div class="data-cell-value c-remain">¥<?= number_format($data['remaining']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <script>
        const mobile = window.innerWidth < 768;
        const ctx = document.getElementById('monthlyChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: '<?= $t['salary_label'] ?>',
                        data: <?= json_encode($chart_salary) ?>,
                        borderColor: '#4facfe',
                        backgroundColor: 'rgba(79, 172, 254, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: mobile ? 2 : 4,
                        pointHoverRadius: mobile ? 4 : 6,
                        pointBackgroundColor: '#4facfe'
                    },
                    {
                        label: '<?= $t['sent_label'] ?>',
                        data: <?= json_encode($chart_sent) ?>,
                        borderColor: '#fa709a',
                        backgroundColor: 'rgba(250, 112, 154, 0.1)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: mobile ? 2 : 4,
                        pointHoverRadius: mobile ? 4 : 6,
                        pointBackgroundColor: '#fa709a'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?= $t['chart_title'] ?>',
                        color: '#ffffff',
                        font: { size: mobile ? 13 : 16, weight: 'bold', family: 'Outfit' },
                        padding: { top: 0, bottom: 12 }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'rgba(255,255,255,0.7)',
                            font: { size: 11 },
                            padding: 12,
                            boxWidth: 14,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.85)',
                        padding: 10,
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ¥' + ctx.parsed.y.toLocaleString()
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgba(255,255,255,0.5)',
                            font: { size: mobile ? 9 : 11 },
                            callback: v => v >= 10000 ? '¥' + (v / 10000).toFixed(0) + '万' : '¥' + v.toLocaleString()
                        },
                        grid: { color: 'rgba(255,255,255,0.06)', drawBorder: false }
                    },
                    x: {
                        ticks: {
                            color: 'rgba(255,255,255,0.5)',
                            font: { size: mobile ? 8 : 10 },
                            maxRotation: mobile ? 45 : 0,
                            minRotation: mobile ? 45 : 0
                        },
                        grid: { display: false }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    </script>
</body>

</html>