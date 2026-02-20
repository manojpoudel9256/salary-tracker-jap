<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Annual Report',
        'subtitle' => 'Yearly income summary by store',
        'total_income' => 'Total Lifetime Income',
        'growth_title' => 'Year-over-Year Growth',
        'chart_title' => 'Annual Income by Store',
        'no_data' => 'No salary data yet',
        'no_data_sub' => 'Add salary records to see your annual report!'
    ],
    'jp' => [
        'title' => '年間レポート',
        'subtitle' => '店舗別の年間収入概要',
        'total_income' => '累計総収入',
        'growth_title' => '年間成長率',
        'chart_title' => '各店舗の年間収入',
        'no_data' => 'まだ給与データがありません',
        'no_data_sub' => '給与を追加してレポートを表示しましょう！'
    ]
];
$t = $trans[$lang];

$stmt = $pdo->prepare("
    SELECT 
        YEAR(working_month) AS year,
        store_name,
        SUM(amount) AS total
    FROM salaries
    WHERE user_id = ?
    GROUP BY year, store_name
    ORDER BY year ASC
");
$stmt->execute([$user_id]);
$data = $stmt->fetchAll();

$years = [];
$summary = [];
$grand_total = 0;
$yearly_totals = [];

foreach ($data as $row) {
    $year = $row['year'];
    $store = $row['store_name'];
    $total = $row['total'];

    if (!in_array($year, $years)) {
        $years[] = $year;
        $yearly_totals[$year] = 0;
    }

    if (!isset($summary[$store])) {
        $summary[$store] = [];
    }

    $summary[$store][$year] = $total;
    $grand_total += $total;
    $yearly_totals[$year] += $total;
}

// Premium colors
$colors = [
    'rgba(79, 172, 254, 0.7)',
    'rgba(0, 242, 254, 0.7)',
    'rgba(67, 233, 123, 0.7)',
    'rgba(250, 112, 154, 0.7)',
    'rgba(254, 225, 64, 0.7)',
    'rgba(161, 140, 209, 0.7)',
    'rgba(251, 194, 235, 0.7)',
    'rgba(143, 211, 244, 0.7)'
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
foreach ($summary as $store => $values) {
    $data_points = [];
    foreach ($values as $year => $amount) {
        $data_points[] = ['x' => $year, 'y' => $amount];
    }

    $datasets[] = [
        'label' => $store,
        'data' => $data_points,
        'backgroundColor' => $colors[$color_index % count($colors)],
        'borderColor' => $borderColors[$color_index % count($borderColors)],
        'borderWidth' => 2,
        'borderRadius' => 6
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

        /* Total Income Hero */
        .total-hero {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.2), rgba(0, 242, 254, 0.2));
            border: 1px solid rgba(79, 172, 254, 0.3);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
            animation: fadeInUp 0.5s ease-out 0.1s backwards;
        }

        .total-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .total-amount {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 32px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Chart Card */
        .glass-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-out 0.2s backwards;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
        }

        /* Growth Section */
        .section-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeInUp 0.5s ease-out 0.3s backwards;
        }

        .section-title i {
            color: var(--accent-primary);
        }

        .growth-list {
            list-style: none;
            padding: 0;
            margin: 0 0 24px 0;
            animation: fadeInUp 0.5s ease-out 0.3s backwards;
        }

        .growth-item {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .growth-years {
            font-size: 14px;
            font-weight: 600;
        }

        .growth-badge {
            font-size: 13px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
        }

        .growth-up {
            color: #43e97b;
            background: rgba(67, 233, 123, 0.15);
        }

        .growth-down {
            color: #fa709a;
            background: rgba(250, 112, 154, 0.15);
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
                height: 240px;
            }

            .total-amount {
                font-size: 26px;
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

            <!-- Total Income Hero -->
            <div class="total-hero">
                <div class="total-label"><i class="fas fa-coins me-1"></i> <?= $t['total_income'] ?></div>
                <div class="total-amount">¥<?= number_format($grand_total) ?></div>
            </div>

            <!-- Chart -->
            <div class="glass-card">
                <div class="chart-wrapper">
                    <canvas id="annualChart"></canvas>
                </div>
            </div>

            <!-- Year-over-Year Growth -->
            <?php if (count($years) > 1): ?>
                <div class="section-title"><i class="fas fa-chart-line"></i> <?= $t['growth_title'] ?></div>
                <ul class="growth-list">
                    <?php
                    for ($i = 1; $i < count($years); $i++) {
                        $prev = $yearly_totals[$years[$i - 1]];
                        $curr = $yearly_totals[$years[$i]];
                        $diff = $curr - $prev;
                        $percent = $prev > 0 ? round(($diff / $prev) * 100, 1) : 100;
                        $is_up = $diff >= 0;
                        $badge_class = $is_up ? 'growth-up' : 'growth-down';
                        $icon = $is_up ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>';
                        $sign = $is_up ? '+' : '';
                        $formatted_diff = $sign . '¥' . number_format(abs($diff));
                        echo "<li class='growth-item'>
                            <span class='growth-years'>{$years[$i - 1]} → {$years[$i]}</span>
                            <div style='text-align:right;'>
                                <div class='growth-badge $badge_class'>$icon {$sign}{$percent}%</div>
                                <div style='font-size:12px;color:rgba(255,255,255,0.5);margin-top:4px;'>{$formatted_diff}</div>
                            </div>
                        </li>";
                    }
                    ?>
                </ul>
            <?php endif; ?>

        <?php else: ?>
            <div class="glass-card">
                <div class="empty-state">
                    <i class="fas fa-calendar-times empty-icon"></i>
                    <p style="font-weight:600;"><?= $t['no_data'] ?></p>
                    <p style="font-size:13px;opacity:0.7;"><?= $t['no_data_sub'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php if (count($data) > 0): ?>
        <script>
            Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";

            const ctx = document.getElementById('annualChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($years) ?>,
                    datasets: <?= json_encode($datasets) ?>
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    parsing: { xAxisKey: 'x', yAxisKey: 'y' },
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
                            type: 'category',
                            labels: <?= json_encode($years) ?>,
                            ticks: { font: { size: 11 }, color: 'rgba(255,255,255,0.6)' },
                            grid: { display: false }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>