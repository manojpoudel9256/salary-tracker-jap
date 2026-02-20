<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Monthly Trends',
        'subtitle' => 'Track salary trends by store with total overlay',
        'filter' => 'Period Filter',
        '3m' => '3 Months',
        '6m' => '6 Months',
        '1y' => '1 Year',
        'all' => 'All Time',
        'stores' => 'Stores',
        'months_recorded' => 'Months',
        'peak_income' => 'Peak Month',
        'chart_title' => 'Monthly Salary by Store + Total Trend',
        'amount' => 'Amount (¥)',
        'total_trend' => 'Total Trend',
        'no_data' => 'No salary data yet',
        'no_data_sub' => 'Add salary records to see trends!'
    ],
    'jp' => [
        'title' => '月別トレンド',
        'subtitle' => '店舗別の給与推移と合計トレンド',
        'filter' => '期間フィルター',
        '3m' => '3ヶ月',
        '6m' => '6ヶ月',
        '1y' => '1年',
        'all' => '全期間',
        'stores' => '店舗数',
        'months_recorded' => '記録月数',
        'peak_income' => '最高月収',
        'chart_title' => '店舗別月別給与 + 合計トレンド',
        'amount' => '金額（¥）',
        'total_trend' => '合計トレンド',
        'no_data' => 'まだ給与データがありません',
        'no_data_sub' => '給与を追加してグラフを表示しましょう！'
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

$all_stores = array_unique(array_column($data, 'store_name'));
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

$monthly_totals = [];
foreach ($months as $month) {
    $total = 0;
    foreach ($store_data as $store => $values) {
        $total += $values[$month];
    }
    $monthly_totals[] = $total;
}

// Premium colors
$colors = [
    ['bg' => 'rgba(79, 172, 254, 0.7)', 'border' => 'rgba(79, 172, 254, 1)'],
    ['bg' => 'rgba(0, 242, 254, 0.7)', 'border' => 'rgba(0, 242, 254, 1)'],
    ['bg' => 'rgba(67, 233, 123, 0.7)', 'border' => 'rgba(67, 233, 123, 1)'],
    ['bg' => 'rgba(250, 112, 154, 0.7)', 'border' => 'rgba(250, 112, 154, 1)'],
    ['bg' => 'rgba(254, 225, 64, 0.7)', 'border' => 'rgba(254, 225, 64, 1)'],
    ['bg' => 'rgba(161, 140, 209, 0.7)', 'border' => 'rgba(161, 140, 209, 1)'],
    ['bg' => 'rgba(251, 194, 235, 0.7)', 'border' => 'rgba(251, 194, 235, 1)'],
    ['bg' => 'rgba(143, 211, 244, 0.7)', 'border' => 'rgba(143, 211, 244, 1)']
];

$datasets = [];
$color_index = 0;
foreach ($store_data as $store => $values) {
    $color = $colors[$color_index % count($colors)];
    $datasets[] = [
        'label' => $store,
        'type' => 'bar',
        'backgroundColor' => $color['bg'],
        'borderColor' => $color['border'],
        'borderWidth' => 2,
        'data' => array_values($values),
        'borderRadius' => 6,
        'borderSkipped' => false
    ];
    $color_index++;
}

// Total trend line
$datasets[] = [
    'label' => $t['total_trend'],
    'type' => 'line',
    'backgroundColor' => 'rgba(250, 112, 154, 0.1)',
    'borderColor' => 'rgba(250, 112, 154, 1)',
    'borderWidth' => 3,
    'data' => $monthly_totals,
    'fill' => true,
    'tension' => 0.4,
    'pointRadius' => 4,
    'pointHoverRadius' => 6,
    'pointBackgroundColor' => 'rgba(250, 112, 154, 1)',
    'pointBorderColor' => '#fff',
    'pointBorderWidth' => 2,
    'order' => 0
];
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

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-out 0.1s backwards;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 14px;
            padding: 12px;
            text-align: center;
        }

        .stat-label {
            font-size: 10px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 17px;
        }

        .stat-accent-1 .stat-value {
            color: #4facfe;
        }

        .stat-accent-2 .stat-value {
            color: #00f2fe;
        }

        .stat-accent-3 .stat-value {
            color: #fa709a;
        }

        /* Period Filter */
        .filter-row {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            animation: fadeInUp 0.5s ease-out 0.2s backwards;
        }

        .filter-row::-webkit-scrollbar {
            display: none;
        }

        .filter-pill {
            flex-shrink: 0;
            padding: 8px 16px;
            border-radius: 10px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .filter-pill.active {
            background: rgba(79, 172, 254, 0.2);
            border-color: rgba(79, 172, 254, 0.4);
            color: #4facfe;
        }

        .filter-pill:active {
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

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 320px;
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
                font-size: 15px;
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
                <h1><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <?php if (count($data) > 0): ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card stat-accent-1">
                    <div class="stat-label"><i class="fas fa-store"></i> <?= $t['stores'] ?></div>
                    <div class="stat-value" id="storeCount"><?= count($all_stores) ?></div>
                </div>
                <div class="stat-card stat-accent-2">
                    <div class="stat-label"><i class="fas fa-calendar"></i> <?= $t['months_recorded'] ?></div>
                    <div class="stat-value" id="monthCount"><?= count($months) ?></div>
                </div>
                <div class="stat-card stat-accent-3">
                    <div class="stat-label"><i class="fas fa-arrow-up"></i> <?= $t['peak_income'] ?></div>
                    <div class="stat-value" id="maxIncome">¥<?= number_format(max($monthly_totals)) ?></div>
                </div>
            </div>

            <!-- Period Filter -->
            <div class="filter-row">
                <div class="filter-pill" data-period="3"><?= $t['3m'] ?></div>
                <div class="filter-pill" data-period="6"><?= $t['6m'] ?></div>
                <div class="filter-pill" data-period="12"><?= $t['1y'] ?></div>
                <div class="filter-pill active" data-period="all"><?= $t['all'] ?></div>
            </div>

            <!-- Chart -->
            <div class="chart-card">
                <div class="chart-wrapper">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

        <?php else: ?>
            <div class="chart-card">
                <div class="empty-state">
                    <i class="fas fa-chart-line empty-icon"></i>
                    <p style="font-weight:600;"><?= $t['no_data'] ?></p>
                    <p style="font-size:13px;opacity:0.7;"><?= $t['no_data_sub'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php if (count($data) > 0): ?>
        <script>
            const fullMonths = <?= json_encode($months) ?>;
            const fullDatasets = <?= json_encode($datasets) ?>;
            const fullMonthlyTotals = <?= json_encode($monthly_totals) ?>;
            const allStoresCount = <?= count($all_stores) ?>;

            Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            const ctx = document.getElementById('monthlyChart').getContext('2d');

            function getChartOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '<?= addslashes($t['chart_title']) ?>',
                            font: { size: 13, weight: 'bold' },
                            color: 'rgba(255,255,255,0.9)',
                            padding: { bottom: 10 }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 10 }, padding: 8, usePointStyle: true, color: 'rgba(255,255,255,0.7)' }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 10,
                            cornerRadius: 8,
                            mode: 'index',
                            intersect: false,
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
                            grid: { display: false }
                        }
                    },
                    interaction: { mode: 'index', intersect: false }
                };
            }

            let chart = new Chart(ctx, {
                type: 'bar',
                data: { labels: fullMonths, datasets: fullDatasets },
                options: getChartOptions()
            });

            // Filter functionality
            document.querySelectorAll('.filter-pill').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const period = this.getAttribute('data-period');
                    filterData(period);
                });
            });

            function filterData(period) {
                let filteredMonths, filteredDatasets;

                if (period === 'all') {
                    filteredMonths = fullMonths;
                    filteredDatasets = fullDatasets;
                    updateStats(fullMonths.length, Math.max(...fullMonthlyTotals));
                } else {
                    const periodCount = parseInt(period);
                    const startIndex = Math.max(0, fullMonths.length - periodCount);
                    filteredMonths = fullMonths.slice(startIndex);
                    filteredDatasets = fullDatasets.map(dataset => ({
                        ...dataset,
                        data: dataset.data.slice(startIndex)
                    }));
                    const filteredTotals = fullMonthlyTotals.slice(startIndex);
                    updateStats(filteredMonths.length, Math.max(...filteredTotals));
                }

                chart.data.labels = filteredMonths;
                chart.data.datasets = filteredDatasets;
                chart.update('active');
            }

            function updateStats(monthCount, maxIncome) {
                document.getElementById('monthCount').textContent = monthCount;
                document.getElementById('maxIncome').textContent = '¥' + maxIncome.toLocaleString();
            }
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>