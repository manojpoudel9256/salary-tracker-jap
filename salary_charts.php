<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Salary Analysis',
        'subtitle' => 'Visualize your salary data with multiple chart types',
        'stores' => 'Stores',
        'months_recorded' => 'Months',
        'total_income' => 'Total Income',
        'avg_monthly' => 'Monthly Avg',
        'select_chart' => 'Chart Type',
        'bar' => 'Bar',
        'line' => 'Line',
        'radar' => 'Radar',
        'pie' => 'Pie',
        'bar_desc' => 'Compare salary by store across months',
        'line_desc' => 'Track salary trends over time',
        'radar_desc' => 'Multi-dimensional store comparison (Total, Avg, Active Months)',
        'pie_desc' => 'See income distribution by store',
        'bar_title' => 'Monthly Salary by Store',
        'line_title' => 'Salary Trend Over Time',
        'radar_title' => 'Store Performance Comparison',
        'pie_title' => 'Income Share by Store',
        'amount_label' => 'Amount (¥)',
        'month_label' => 'Month',
        'total_label' => 'Total Income',
        'avg_label' => 'Avg Monthly',
        'active_months' => 'Active Months',
        'no_data' => 'No salary data yet',
        'no_data_sub' => 'Add salary records to see charts!',
        'back' => 'Dashboard'
    ],
    'jp' => [
        'title' => '給与分析',
        'subtitle' => '複数のチャートで給与データを視覚化',
        'stores' => '店舗数',
        'months_recorded' => '記録月数',
        'total_income' => '総収入',
        'avg_monthly' => '月平均',
        'select_chart' => 'チャートタイプ',
        'bar' => '棒グラフ',
        'line' => '折れ線',
        'radar' => 'レーダー',
        'pie' => '円グラフ',
        'bar_desc' => '各月における店舗ごとの給与を比較',
        'line_desc' => '時系列での給与トレンドを確認',
        'radar_desc' => '店舗パフォーマンスを多角的に比較（総収入・平均月収・稼働月数）',
        'pie_desc' => '店舗別の総収入の割合を表示',
        'bar_title' => '店舗別の月別給与比較',
        'line_title' => '月別給与トレンド',
        'radar_title' => '店舗パフォーマンス比較',
        'pie_title' => '店舗別総収入の割合',
        'amount_label' => '金額（¥）',
        'month_label' => '月',
        'total_label' => '総収入',
        'avg_label' => '平均月収',
        'active_months' => '稼働月数',
        'no_data' => 'まだ給与データがありません',
        'no_data_sub' => '給与を追加してグラフを表示しましょう！',
        'back' => 'ダッシュボード'
    ]
];
$t = $trans[$lang];

$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(working_month, '%Y-%m') AS month,
        store_name,
        SUM(amount) AS total
    FROM salaries
    WHERE user_id = ?
    GROUP BY month, store_name
    ORDER BY month ASC
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll();

// Get all unique stores
$all_stores = array_unique(array_column($data, 'store_name'));

// Organize data
$months = [];
$store_data = [];

foreach ($all_stores as $store) {
    $store_data[$store] = [];
}

foreach ($data as $row) {
    $month = $row['month'];
    $store = $row['store_name'];
    $amount = $row['total'];

    if (!in_array($month, $months)) {
        $months[] = $month;
    }

    $store_data[$store][$month] = $amount;
}

foreach ($store_data as $store => $values) {
    foreach ($months as $month) {
        if (!isset($store_data[$store][$month])) {
            $store_data[$store][$month] = 0;
        }
    }
    ksort($store_data[$store]);
}

$store_totals = [];
foreach ($store_data as $store => $values) {
    $store_totals[$store] = array_sum($values);
}

$store_averages = [];
foreach ($store_data as $store => $values) {
    $non_zero_values = array_filter($values);
    $store_averages[$store] = count($non_zero_values) > 0 ? array_sum($non_zero_values) / count($non_zero_values) : 0;
}

// Premium colors
$colors = [
    'rgba(79, 172, 254, 0.6)',
    'rgba(0, 242, 254, 0.6)',
    'rgba(67, 233, 123, 0.6)',
    'rgba(250, 112, 154, 0.6)',
    'rgba(254, 225, 64, 0.6)',
    'rgba(161, 140, 209, 0.6)',
    'rgba(251, 194, 235, 0.6)',
    'rgba(143, 211, 244, 0.6)'
];

$borderColors = [
    'rgba(79, 172, 254, 1)',
    'rgba(0, 242, 254, 1)',
    'rgba(67, 233, 123, 1)',
    'rgba(250, 112, 154, 1)',
    'rgba(254, 225, 64, 1)',
    'rgba(161, 140, 209, 1)',
    'rgba(251, 194, 235, 1)',
    'rgba(143, 211, 244, 1)'
];

$datasets = [];
$color_index = 0;
foreach ($store_data as $store => $values) {
    $datasets[] = [
        'label' => $store,
        'backgroundColor' => $colors[$color_index % count($colors)],
        'borderColor' => $borderColors[$color_index % count($borderColors)],
        'borderWidth' => 2,
        'data' => array_values($values)
    ];
    $color_index++;
}

$radarDatasets = [];
$color_index = 0;
foreach ($store_averages as $store => $avg) {
    $radarDatasets[] = [
        'label' => $store,
        'data' => [$store_totals[$store], $avg, count(array_filter($store_data[$store]))],
        'backgroundColor' => $colors[$color_index % count($colors)],
        'borderColor' => $borderColors[$color_index % count($borderColors)],
        'borderWidth' => 2,
        'pointBackgroundColor' => $borderColors[$color_index % count($borderColors)],
        'pointBorderColor' => '#fff',
        'pointHoverBackgroundColor' => '#fff',
        'pointHoverBorderColor' => $borderColors[$color_index % count($borderColors)]
    ];
    $color_index++;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- App Icons -->
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
            --accent-glow: rgba(79, 172, 254, 0.4);

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
            margin-bottom: 24px;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
            animation: fadeInUp 0.5s ease-out 0.1s backwards;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 16px;
            padding: 16px;
            text-align: center;
        }

        .stat-label {
            font-size: 11px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 20px;
        }

        .stat-accent-1 .stat-value {
            color: #4facfe;
        }

        .stat-accent-2 .stat-value {
            color: #00f2fe;
        }

        .stat-accent-3 .stat-value {
            color: #43e97b;
        }

        .stat-accent-4 .stat-value {
            color: #fa709a;
        }

        /* Chart Type Selector */
        .chart-selector {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            animation: fadeInUp 0.5s ease-out 0.2s backwards;
        }

        .chart-selector::-webkit-scrollbar {
            display: none;
        }

        .chart-tab {
            flex-shrink: 0;
            padding: 10px 18px;
            border-radius: 12px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .chart-tab.active {
            background: rgba(79, 172, 254, 0.2);
            border-color: rgba(79, 172, 254, 0.4);
            color: #4facfe;
        }

        .chart-tab:active {
            transform: scale(0.95);
        }

        /* Chart Card */
        .chart-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            animation: fadeInUp 0.5s ease-out 0.3s backwards;
        }

        .chart-desc {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-desc i {
            color: var(--accent-primary);
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
            display: none;
        }

        .chart-wrapper.active {
            display: block;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 100%;
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
            .chart-wrapper {
                height: 260px;
            }

            .stat-value {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="header-text">
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <?php if (count($data) > 0): ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card stat-accent-1">
                    <div class="stat-label"><i class="fas fa-store me-1"></i> <?= $t['stores'] ?></div>
                    <div class="stat-value"><?= count($all_stores) ?></div>
                </div>
                <div class="stat-card stat-accent-2">
                    <div class="stat-label"><i class="fas fa-calendar me-1"></i> <?= $t['months_recorded'] ?></div>
                    <div class="stat-value"><?= count($months) ?></div>
                </div>
                <div class="stat-card stat-accent-3">
                    <div class="stat-label"><i class="fas fa-yen-sign me-1"></i> <?= $t['total_income'] ?></div>
                    <div class="stat-value">¥<?= number_format(array_sum($store_totals)) ?></div>
                </div>
                <div class="stat-card stat-accent-4">
                    <div class="stat-label"><i class="fas fa-chart-line me-1"></i> <?= $t['avg_monthly'] ?></div>
                    <div class="stat-value">
                        ¥<?= number_format(count($months) > 0 ? array_sum($store_totals) / count($months) : 0) ?></div>
                </div>
            </div>

            <!-- Chart Type Selector -->
            <div class="chart-selector">
                <div class="chart-tab active" data-chart="bar">
                    <i class="fas fa-chart-bar"></i> <?= $t['bar'] ?>
                </div>
                <div class="chart-tab" data-chart="line">
                    <i class="fas fa-chart-line"></i> <?= $t['line'] ?>
                </div>
                <div class="chart-tab" data-chart="radar">
                    <i class="fas fa-spider"></i> <?= $t['radar'] ?>
                </div>
                <div class="chart-tab" data-chart="pie">
                    <i class="fas fa-chart-pie"></i> <?= $t['pie'] ?>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="chart-card">
                <div class="chart-wrapper active" id="barChart">
                    <div class="chart-desc">
                        <i class="fas fa-info-circle"></i> <?= $t['bar_desc'] ?>
                    </div>
                    <div class="chart-container">
                        <canvas id="barCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="lineChart">
                    <div class="chart-desc">
                        <i class="fas fa-info-circle"></i> <?= $t['line_desc'] ?>
                    </div>
                    <div class="chart-container">
                        <canvas id="lineCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="radarChart">
                    <div class="chart-desc">
                        <i class="fas fa-info-circle"></i> <?= $t['radar_desc'] ?>
                    </div>
                    <div class="chart-container">
                        <canvas id="radarCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="pieChart">
                    <div class="chart-desc">
                        <i class="fas fa-info-circle"></i> <?= $t['pie_desc'] ?>
                    </div>
                    <div class="chart-container">
                        <canvas id="pieCanvas"></canvas>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="chart-card">
                <div class="empty-state">
                    <i class="fas fa-chart-bar empty-icon"></i>
                    <p style="font-weight:600;"><?= $t['no_data'] ?></p>
                    <p style="font-size:13px;opacity:0.7;"><?= $t['no_data_sub'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php if (count($data) > 0): ?>
        <script>
            const isMobile = window.innerWidth <= 768;
            const months = <?= json_encode($months) ?>;
            const datasets = <?= json_encode($datasets) ?>;
            const radarDatasets = <?= json_encode($radarDatasets) ?>;
            const storeTotals = <?= json_encode($store_totals) ?>;
            const storeNames = <?= json_encode(array_keys($store_totals)) ?>;
            const colors = <?= json_encode($colors) ?>;
            const borderColors = <?= json_encode($borderColors) ?>;

            // Translation strings for JS
            const chartTrans = {
                barTitle: '<?= addslashes($t['bar_title']) ?>',
                lineTitle: '<?= addslashes($t['line_title']) ?>',
                radarTitle: '<?= addslashes($t['radar_title']) ?>',
                pieTitle: '<?= addslashes($t['pie_title']) ?>',
                amountLabel: '<?= addslashes($t['amount_label']) ?>',
                monthLabel: '<?= addslashes($t['month_label']) ?>',
                totalLabel: '<?= addslashes($t['total_label']) ?>',
                avgLabel: '<?= addslashes($t['avg_label']) ?>',
                activeMonths: '<?= addslashes($t['active_months']) ?>'
            };

            // Dark theme configuration for Chart.js
            Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            let barChartInstance, lineChartInstance, radarChartInstance, pieChartInstance;

            function getBarLineOptions(title) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title,
                            font: { size: 14, weight: 'bold' },
                            color: 'rgba(255,255,255,0.9)',
                            padding: { bottom: 10 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 10 }, padding: 10, usePointStyle: true, color: 'rgba(255,255,255,0.7)' }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ¥' + ctx.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: !isMobile, text: chartTrans.amountLabel, font: { size: 12 }, color: 'rgba(255,255,255,0.5)' },
                            ticks: {
                                font: { size: 10 },
                                color: 'rgba(255,255,255,0.5)',
                                callback: function (v) {
                                    if (v >= 10000) return '¥' + (v / 10000).toFixed(0) + '万';
                                    return '¥' + v.toLocaleString();
                                }
                            },
                            grid: { color: 'rgba(255,255,255,0.05)' }
                        },
                        x: {
                            ticks: { font: { size: 9 }, color: 'rgba(255,255,255,0.5)', maxRotation: 45, minRotation: 45 },
                            grid: { color: 'rgba(255,255,255,0.05)' }
                        }
                    }
                };
            }

            function getRadarOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartTrans.radarTitle,
                            font: { size: 14, weight: 'bold' },
                            color: 'rgba(255,255,255,0.9)',
                            padding: { bottom: 10 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 10 }, padding: 10, usePointStyle: true, color: 'rgba(255,255,255,0.7)' }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            angleLines: { color: 'rgba(255,255,255,0.1)' },
                            grid: { color: 'rgba(255,255,255,0.08)', circular: true },
                            ticks: { font: { size: 9 }, color: 'rgba(255,255,255,0.5)', backdropColor: 'rgba(0,0,0,0)' },
                            pointLabels: {
                                font: { size: 11, weight: '600' },
                                color: 'rgba(255,255,255,0.8)'
                            }
                        }
                    }
                };
            }

            function getPieOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartTrans.pieTitle,
                            font: { size: 14, weight: 'bold' },
                            color: 'rgba(255,255,255,0.9)',
                            padding: { bottom: 10 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 10 }, padding: 10, usePointStyle: true, boxWidth: 12, color: 'rgba(255,255,255,0.7)' }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            cornerRadius: 8,
                            callbacks: {
                                label: function (ctx) {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                    return ctx.label + ': ¥' + ctx.parsed.toLocaleString() + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                };
            }

            function initCharts() {
                barChartInstance = new Chart(document.getElementById('barCanvas').getContext('2d'), {
                    type: 'bar',
                    data: { labels: months, datasets: datasets },
                    options: getBarLineOptions(chartTrans.barTitle)
                });

                lineChartInstance = new Chart(document.getElementById('lineCanvas').getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: datasets.map(d => ({ ...d, tension: 0.4, fill: false, borderWidth: 3, pointRadius: 3, pointHoverRadius: 5 }))
                    },
                    options: getBarLineOptions(chartTrans.lineTitle)
                });

                radarChartInstance = new Chart(document.getElementById('radarCanvas').getContext('2d'), {
                    type: 'radar',
                    data: {
                        labels: [chartTrans.totalLabel, chartTrans.avgLabel, chartTrans.activeMonths],
                        datasets: radarDatasets
                    },
                    options: getRadarOptions()
                });

                pieChartInstance = new Chart(document.getElementById('pieCanvas').getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: storeNames,
                        datasets: [{
                            data: Object.values(storeTotals),
                            backgroundColor: colors,
                            borderColor: borderColors,
                            borderWidth: 2
                        }]
                    },
                    options: getPieOptions()
                });
            }

            initCharts();

            // Chart tab switching
            document.querySelectorAll('.chart-tab').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.chart-tab').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.chart-wrapper').forEach(c => c.classList.remove('active'));
                    const chartType = this.getAttribute('data-chart');
                    document.getElementById(chartType + 'Chart').classList.add('active');
                });
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>