<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

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

// Initialize store_data array for all stores
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

// Fill empty months with 0 for all stores
foreach ($store_data as $store => $values) {
    foreach ($months as $month) {
        if (!isset($store_data[$store][$month])) {
            $store_data[$store][$month] = 0;
        }
    }
    ksort($store_data[$store]);
}

// Calculate monthly totals for the line overlay
$monthly_totals = [];
foreach ($months as $month) {
    $total = 0;
    foreach ($store_data as $store => $values) {
        $total += $values[$month];
    }
    $monthly_totals[] = $total;
}

// Generate vibrant colors for each store
$colors = [
    ['bg' => 'rgba(54, 162, 235, 0.8)', 'border' => 'rgba(54, 162, 235, 1)'],
    ['bg' => 'rgba(255, 206, 86, 0.8)', 'border' => 'rgba(255, 206, 86, 1)'],
    ['bg' => 'rgba(75, 192, 192, 0.8)', 'border' => 'rgba(75, 192, 192, 1)'],
    ['bg' => 'rgba(153, 102, 255, 0.8)', 'border' => 'rgba(153, 102, 255, 1)'],
    ['bg' => 'rgba(255, 99, 132, 0.8)', 'border' => 'rgba(255, 99, 132, 1)'],
    ['bg' => 'rgba(255, 159, 64, 0.8)', 'border' => 'rgba(255, 159, 64, 1)'],
    ['bg' => 'rgba(46, 204, 113, 0.8)', 'border' => 'rgba(46, 204, 113, 1)'],
    ['bg' => 'rgba(52, 152, 219, 0.8)', 'border' => 'rgba(52, 152, 219, 1)'],
    ['bg' => 'rgba(155, 89, 182, 0.8)', 'border' => 'rgba(155, 89, 182, 1)'],
    ['bg' => 'rgba(241, 196, 15, 0.8)', 'border' => 'rgba(241, 196, 15, 1)']
];

// Prepare datasets for Chart.js (bars for each store)
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

// Add total trend line
$datasets[] = [
    'label' => '合計トレンド',
    'type' => 'line',
    'backgroundColor' => 'rgba(231, 76, 60, 0.1)',
    'borderColor' => 'rgba(231, 76, 60, 1)',
    'borderWidth' => 3,
    'data' => $monthly_totals,
    'fill' => true,
    'tension' => 0.4,
    'pointRadius' => 5,
    'pointHoverRadius' => 7,
    'pointBackgroundColor' => 'rgba(231, 76, 60, 1)',
    'pointBorderColor' => '#fff',
    'pointBorderWidth' => 2,
    'order' => 0
];
?>

<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>月別給与チャート</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(125deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientShift 20s ease infinite;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            max-width: 1400px;
            margin-top: 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out;
        }

        .page-title {
            color: white;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 0px 3px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .page-title i {
            font-size: 2rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 400;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Filter Controls */
        .filter-controls {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .filter-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label i {
            color: #667eea;
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .filter-btn {
            padding: 12px 24px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #555;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }

        .filter-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .filter-btn i {
            font-size: 1rem;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: cardEntrance 0.8s ease-out;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 500px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 16px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-label {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 28px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.4);
            font-size: 1rem;
        }

        .back-link:hover {
            transform: translateX(-8px) scale(1.05);
            background: rgba(255, 255, 255, 0.35);
            color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .back-link i {
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-4px);
        }

        .no-data {
            text-align: center;
            padding: 80px 20px;
            color: #6c757d;
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #adb5bd;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .no-data-text {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .no-data-subtext {
            font-size: 1rem;
            color: #868e96;
        }

        /* ============================================ */
        /* MOBILE RESPONSIVE STYLES */
        /* ============================================ */
        
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }

            .container {
                margin-top: 10px;
                padding: 0 5px;
            }

            .page-header {
                margin-bottom: 20px;
            }

            .page-title {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 8px;
            }

            .page-title i {
                font-size: 1.5rem;
            }

            .page-subtitle {
                font-size: 0.85rem;
            }

            .filter-controls {
                padding: 18px;
                border-radius: 18px;
                margin-bottom: 20px;
            }

            .filter-label {
                font-size: 0.95rem;
                margin-bottom: 12px;
                justify-content: center;
            }

            .filter-buttons {
                gap: 8px;
            }

            .filter-btn {
                padding: 10px 18px;
                font-size: 0.85rem;
                min-width: 100px;
                flex: 1 1 calc(50% - 8px);
                max-width: calc(50% - 4px);
            }

            .filter-btn i {
                font-size: 0.9rem;
            }

            .chart-card {
                padding: 18px;
                border-radius: 18px;
                margin-bottom: 20px;
            }

            .chart-wrapper {
                height: 350px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 20px;
            }

            .stat-box {
                padding: 16px;
                border-radius: 14px;
            }

            .stat-label {
                font-size: 0.8rem;
                margin-bottom: 6px;
            }

            .stat-value {
                font-size: 1.4rem;
            }

            .back-link {
                padding: 12px 22px;
                font-size: 0.9rem;
                gap: 8px;
            }

            .no-data {
                padding: 50px 15px;
            }

            .no-data i {
                font-size: 48px;
            }

            .no-data-text {
                font-size: 1rem;
            }

            .no-data-subtext {
                font-size: 0.9rem;
            }
        }

        /* Extra small devices */
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .page-title {
                font-size: 1.3rem;
            }

            .page-subtitle {
                font-size: 0.8rem;
            }

            .filter-controls {
                padding: 15px;
            }

            .filter-btn {
                padding: 9px 16px;
                font-size: 0.8rem;
                min-width: 90px;
            }

            .chart-card {
                padding: 15px;
                border-radius: 16px;
            }

            .chart-wrapper {
                height: 320px;
            }

            .stat-box {
                padding: 14px;
            }

            .stat-value {
                font-size: 1.3rem;
            }

            .back-link {
                padding: 10px 18px;
                font-size: 0.85rem;
            }
        }

        /* Landscape orientation */
        @media (max-width: 768px) and (orientation: landscape) {
            .chart-wrapper {
                height: 280px;
            }

            .page-title {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .filter-btn {
                flex: 1 1 auto;
                max-width: none;
            }
        }

        /* Touch-friendly */
        @media (hover: none) and (pointer: coarse) {
            .back-link, .filter-btn {
                min-height: 44px;
            }

            .back-link:active {
                transform: translateX(-5px) scale(0.97);
            }

            .filter-btn:active {
                transform: scale(0.95);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i>
                月別給与チャート
            </h1>
            <p class="page-subtitle">店舗別の給与推移と合計トレンドを確認</p>
        </div>
        
        <?php if (count($data) > 0): ?>
                <!-- Filter Controls -->
                <div class="filter-controls">
                    <div class="filter-label">
                        <i class="fas fa-filter"></i>
                        期間フィルター
                    </div>
                    <div class="filter-buttons">
                        <button class="filter-btn" data-period="3">
                            <i class="fas fa-calendar-day"></i>
                            3ヶ月
                        </button>
                        <button class="filter-btn" data-period="6">
                            <i class="fas fa-calendar-week"></i>
                            6ヶ月
                        </button>
                        <button class="filter-btn" data-period="12">
                            <i class="fas fa-calendar-alt"></i>
                            1年
                        </button>
                        <button class="filter-btn active" data-period="all">
                            <i class="fas fa-infinity"></i>
                            全期間
                        </button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label"><i class="fas fa-store"></i> 店舗数</div>
                        <div class="stat-value" id="storeCount"><?= count($all_stores) ?></div>
                    </div>
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="stat-label"><i class="fas fa-calendar-check"></i> 記録月数</div>
                        <div class="stat-value" id="monthCount"><?= count($months) ?></div>
                    </div>
                    <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <div class="stat-label"><i class="fas fa-yen-sign"></i> 最高月収</div>
                        <div class="stat-value" id="maxIncome">¥<?= number_format(max($monthly_totals)) ?></div>
                    </div>
                </div>
        <?php endif; ?>

        <div class="chart-card">
            <?php if (count($data) > 0): ?>
                    <div class="chart-wrapper">
                        <canvas id="monthlyChart"></canvas>
                    </div>
            <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar"></i>
                        <div class="no-data-text">まだ給与データがありません</div>
                        <div class="no-data-subtext">給与を追加してグラフを表示しましょう！</div>
                    </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                ダッシュボードに戻る
            </a>
        </div>
    </div>

    <?php if (count($data) > 0): ?>
        <script>
            const fullMonths = <?= json_encode($months) ?>;
            const fullDatasets = <?= json_encode($datasets) ?>;
            const fullMonthlyTotals = <?= json_encode($monthly_totals) ?>;
            const allStoresCount = <?= count($all_stores) ?>;
        
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            const isMobile = window.innerWidth <= 768;
        
            let chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: fullMonths,
                    datasets: fullDatasets
                },
                options: getChartOptions(isMobile)
            });

            function getChartOptions(mobile) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '店舗別月別給与 + 合計トレンド',
                            font: {
                                size: mobile ? 15 : 20,
                                weight: 'bold',
                                family: 'Poppins'
                            },
                            padding: {
                                top: mobile ? 8 : 15,
                                bottom: mobile ? 12 : 20
                            },
                            color: '#2c3e50'
                        },
                        legend: {
                            display: true,
                            position: mobile ? 'bottom' : 'top',
                            labels: {
                                font: {
                                    size: mobile ? 11 : 13,
                                    family: 'Poppins',
                                    weight: '500'
                                },
                                padding: mobile ? 10 : 15,
                                boxWidth: mobile ? 15 : 20,
                                boxHeight: mobile ? 15 : 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: {
                                size: mobile ? 12 : 14,
                                weight: 'bold',
                                family: 'Poppins'
                            },
                            bodyFont: {
                                size: mobile ? 11 : 13,
                                family: 'Poppins'
                            },
                            padding: mobile ? 10 : 15,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += '¥' + context.parsed.y.toLocaleString();
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            stacked: false,
                            title: {
                                display: true,
                                text: '金額（¥）',
                                font: {
                                    size: mobile ? 12 : 15,
                                    weight: 'bold',
                                    family: 'Poppins'
                                },
                                color: '#34495e'
                            },
                            ticks: {
                                font: {
                                    size: mobile ? 10 : 12,
                                    family: 'Poppins'
                                },
                                color: '#7f8c8d',
                                callback: function(value) {
                                    if (mobile && value >= 10000) {
                                        return '¥' + (value / 10000).toFixed(0) + '万';
                                    } else if (mobile && value >= 1000) {
                                        return '¥' + (value / 1000).toFixed(0) + 'k';
                                    }
                                    return '¥' + value.toLocaleString();
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.06)',
                                drawBorder: false
                            }
                        },
                        x: {
                            stacked: false,
                            title: {
                                display: !mobile,
                                text: '月',
                                font: {
                                    size: 15,
                                    weight: 'bold',
                                    family: 'Poppins'
                                },
                                color: '#34495e'
                            },
                            ticks: {
                                font: {
                                    size: mobile ? 9 : 12,
                                    family: 'Poppins'
                                },
                                color: '#7f8c8d',
                                maxRotation: mobile ? 45 : 0,
                                minRotation: mobile ? 45 : 0
                            },
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    }
                };
            }

            // Filter functionality
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                
                    // Add active class to clicked button
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
                    filteredDatasets = fullDatasets.map(dataset => {
                        return {
                            ...dataset,
                            data: dataset.data.slice(startIndex)
                        };
                    });
                
                    // Calculate max for filtered period
                    const filteredTotals = fullMonthlyTotals.slice(startIndex);
                    updateStats(filteredMonths.length, Math.max(...filteredTotals));
                }
            
                // Update chart
                chart.data.labels = filteredMonths;
                chart.data.datasets = filteredDatasets;
                chart.update('active');
            }

            function updateStats(monthCount, maxIncome) {
                document.getElementById('monthCount').textContent = monthCount;
                document.getElementById('maxIncome').textContent = '¥' + maxIncome.toLocaleString();
            }

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    const newIsMobile = window.innerWidth <= 768;
                    chart.options = getChartOptions(newIsMobile);
                    chart.update();
                }, 250);
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>