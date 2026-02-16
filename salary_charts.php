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

// Calculate totals for each store (for radar chart)
$store_totals = [];
foreach ($store_data as $store => $values) {
    $store_totals[$store] = array_sum($values);
}

// Calculate average per month for each store (for radar chart)
$store_averages = [];
foreach ($store_data as $store => $values) {
    $non_zero_values = array_filter($values);
    $store_averages[$store] = count($non_zero_values) > 0 ? array_sum($non_zero_values) / count($non_zero_values) : 0;
}

// Generate vibrant colors for each store
$colors = [
    'rgba(54, 162, 235, 0.6)',
    'rgba(255, 206, 86, 0.6)',
    'rgba(75, 192, 192, 0.6)',
    'rgba(153, 102, 255, 0.6)',
    'rgba(255, 99, 132, 0.6)',
    'rgba(255, 159, 64, 0.6)',
    'rgba(46, 204, 113, 0.6)',
    'rgba(52, 152, 219, 0.6)',
    'rgba(155, 89, 182, 0.6)',
    'rgba(241, 196, 15, 0.6)'
];

$borderColors = [
    'rgba(54, 162, 235, 1)',
    'rgba(255, 206, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(255, 99, 132, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(46, 204, 113, 1)',
    'rgba(52, 152, 219, 1)',
    'rgba(155, 89, 182, 1)',
    'rgba(241, 196, 15, 1)'
];

// Prepare datasets for bar chart
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

// Prepare data for radar chart (store performance comparison)
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
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>給与チャート分析</title>
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
            animation: rotate 3s ease-in-out infinite;
        }

        @keyframes rotate {

            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(180deg);
            }
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 400;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chart-selector {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .selector-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .selector-label i {
            color: #667eea;
        }

        .chart-type-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        .chart-type-btn {
            padding: 14px 28px;
            border: 2px solid #e0e0e0;
            background: white;
            color: #555;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 140px;
            justify-content: center;
        }

        .chart-type-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
            color: #667eea;
        }

        .chart-type-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .chart-type-btn i {
            font-size: 1.2rem;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: cardEntrance 0.8s ease-out;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 500px;
            display: none;
        }

        .chart-wrapper.active {
            display: block;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: calc(100% - 60px);
            margin-top: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 22px;
            border-radius: 18px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            0% {
                left: -150%;
            }

            100% {
                left: 150%;
            }
        }

        .stat-box:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.95;
            margin-bottom: 10px;
            font-weight: 500;
            position: relative;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
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

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
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

        .chart-description {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.3);
        }

        .chart-description i {
            margin-right: 8px;
        }

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

            .chart-selector {
                padding: 18px;
                border-radius: 18px;
                margin-bottom: 20px;
            }

            .selector-label {
                font-size: 0.95rem;
                margin-bottom: 12px;
                justify-content: center;
            }

            .chart-type-buttons {
                gap: 10px;
            }

            .chart-type-btn {
                padding: 11px 20px;
                font-size: 0.85rem;
                min-width: 110px;
                flex: 1 1 calc(50% - 5px);
                max-width: calc(50% - 5px);
            }

            .chart-type-btn i {
                font-size: 1rem;
            }

            .chart-card {
                padding: 18px;
                border-radius: 18px;
                margin-bottom: 20px;
            }

            .chart-wrapper {
                height: 350px;
            }

            .chart-container {
                height: calc(100% - 50px);
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
                margin-bottom: 8px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .chart-description {
                font-size: 0.85rem;
                padding: 12px 16px;
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

            .chart-selector {
                padding: 15px;
            }

            .chart-type-btn {
                padding: 10px 16px;
                font-size: 0.8rem;
                min-width: 100px;
            }

            .chart-card {
                padding: 50px;
                border-radius: 18px;
                margin-bottom: 20px;
            }

            .chart-wrapper {
                height: 320px;
            }

            .chart-container {
                height: calc(100% - 50px);
            }

            .stat-box {
                padding: 14px;
            }

            .stat-value {
                font-size: 1.4rem;
            }

            .back-link {
                padding: 10px 18px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) and (orientation: landscape) {
            .chart-wrapper {
                height: 300px;
            }

            .page-title {
                font-size: 1.3rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-type-btn {
                flex: 1 1 auto;
                max-width: none;
            }
        }

        @media (hover: none) and (pointer: coarse) {

            .back-link,
            .chart-type-btn {
                min-height: 44px;
            }

            .back-link:active {
                transform: translateX(-5px) scale(0.97);
            }

            .chart-type-btn:active {
                transform: scale(0.95);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-pie"></i>
                給与チャート分析
            </h1>
            <p class="page-subtitle">複数のチャートタイプで給与データを視覚化</p>
        </div>

        <?php if (count($data) > 0): ?>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label"><i class="fas fa-store"></i> 登録店舗数</div>
                    <div class="stat-value"><?= count($all_stores) ?></div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stat-label"><i class="fas fa-calendar-check"></i> 総記録月数</div>
                    <div class="stat-value"><?= count($months) ?></div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="stat-label"><i class="fas fa-yen-sign"></i> 総収入</div>
                    <div class="stat-value">¥<?= number_format(array_sum($store_totals)) ?></div>
                </div>
                <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="stat-label"><i class="fas fa-chart-line"></i> 月平均</div>
                    <div class="stat-value">
                        ¥<?= number_format(count($months) > 0 ? array_sum($store_totals) / count($months) : 0) ?></div>
                </div>
            </div>

            <div class="chart-selector">
                <div class="selector-label">
                    <i class="fas fa-chart-bar"></i>
                    チャートタイプを選択
                </div>
                <div class="chart-type-buttons">
                    <button class="chart-type-btn active" data-chart="bar">
                        <i class="fas fa-chart-bar"></i>
                        棒グラフ
                    </button>
                    <button class="chart-type-btn" data-chart="line">
                        <i class="fas fa-chart-line"></i>
                        折れ線グラフ
                    </button>
                    <button class="chart-type-btn" data-chart="radar">
                        <i class="fas fa-spider"></i>
                        レーダーチャート
                    </button>
                    <button class="chart-type-btn" data-chart="pie">
                        <i class="fas fa-chart-pie"></i>
                        円グラフ
                    </button>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-wrapper active" id="barChart">
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        各月における店舗ごとの給与を比較できます
                    </div>
                    <div class="chart-container">
                        <canvas id="barCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="lineChart">
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        時系列での給与トレンドを確認できます
                    </div>
                    <div class="chart-container">
                        <canvas id="lineCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="radarChart">
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        店舗のパフォーマンスを多角的に比較します（総収入・平均月収・稼働月数）
                    </div>
                    <div class="chart-container">
                        <canvas id="radarCanvas"></canvas>
                    </div>
                </div>

                <div class="chart-wrapper" id="pieChart">
                    <div class="chart-description">
                        <i class="fas fa-info-circle"></i>
                        店舗別の総収入の割合を表示します
                    </div>
                    <div class="chart-container">
                        <canvas id="pieCanvas"></canvas>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="chart-card">
                <div class="no-data">
                    <i class="fas fa-chart-bar"></i>
                    <div class="no-data-text">まだ給与データがありません</div>
                    <div class="no-data-subtext">給与を追加してグラフを表示しましょう！</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i>
                ダッシュボードに戻る
            </a>
        </div>
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

            let barChartInstance, lineChartInstance, radarChartInstance, pieChartInstance;

            function getBarLineOptions(title, isBar) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title,
                            font: {
                                size: isMobile ? 14 : 18,
                                weight: 'bold',
                                family: 'Poppins'
                            },
                            padding: {
                                top: isMobile ? 5 : 10,
                                bottom: isMobile ? 10 : 15
                            },
                            color: '#2c3e50'
                        },
                        legend: {
                            display: true,
                            position: isMobile ? 'bottom' : 'top',
                            labels: {
                                font: {
                                    size: isMobile ? 10 : 12,
                                    family: 'Poppins'
                                },
                                padding: isMobile ? 8 : 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { size: isMobile ? 11 : 13, weight: 'bold' },
                            bodyFont: { size: isMobile ? 10 : 12 },
                            padding: isMobile ? 10 : 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ¥' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: '金額（¥）',
                                font: { size: isMobile ? 11 : 14, weight: 'bold' }
                            },
                            ticks: {
                                font: { size: isMobile ? 9 : 11 },
                                callback: function (value) {
                                    if (isMobile && value >= 10000) {
                                        return '¥' + (value / 10000).toFixed(0) + '万';
                                    }
                                    return '¥' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: !isMobile,
                                text: '月',
                                font: { size: 14, weight: 'bold' }
                            },
                            ticks: {
                                font: { size: isMobile ? 8 : 11 },
                                maxRotation: isMobile ? 45 : 0,
                                minRotation: isMobile ? 45 : 0
                            }
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
                            text: '店舗パフォーマンス比較',
                            font: {
                                size: isMobile ? 14 : 18,
                                weight: 'bold',
                                family: 'Poppins'
                            },
                            padding: {
                                top: isMobile ? 5 : 10,
                                bottom: isMobile ? 10 : 15
                            },
                            color: '#2c3e50'
                        },
                        legend: {
                            display: true,
                            position: isMobile ? 'bottom' : 'top',
                            labels: {
                                font: {
                                    size: isMobile ? 10 : 12,
                                    family: 'Poppins'
                                },
                                padding: isMobile ? 8 : 12,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { size: isMobile ? 11 : 13, weight: 'bold' },
                            bodyFont: { size: isMobile ? 10 : 12 },
                            padding: isMobile ? 10 : 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            angleLines: {
                                color: 'rgba(102, 126, 234, 0.2)',
                                lineWidth: 1
                            },
                            grid: {
                                color: 'rgba(102, 126, 234, 0.15)',
                                circular: true
                            },
                            ticks: {
                                font: { size: isMobile ? 9 : 11 },
                                backdropColor: 'rgba(255, 255, 255, 0.8)',
                                color: '#667eea',
                                showLabelBackdrop: true
                            },
                            pointLabels: {
                                font: {
                                    size: isMobile ? 11 : 14,
                                    weight: '600',
                                    family: 'Poppins'
                                },
                                color: '#2c3e50'
                            }
                        }
                    }
                };
            }

            function getPieOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: isMobile ? 1.2 : 1.5,
                    plugins: {
                        title: {
                            display: true,
                            text: '店舗別総収入の割合',
                            font: {
                                size: isMobile ? 14 : 18,
                                weight: 'bold',
                                family: 'Poppins'
                            },
                            padding: {
                                top: isMobile ? 5 : 10,
                                bottom: isMobile ? 10 : 15
                            },
                            color: '#2c3e50'
                        },
                        legend: {
                            display: true,
                            position: isMobile ? 'bottom' : 'right',
                            align: 'center',
                            labels: {
                                font: {
                                    size: isMobile ? 10 : 12,
                                    family: 'Poppins'
                                },
                                padding: isMobile ? 8 : 15,
                                usePointStyle: true,
                                boxWidth: 15,
                                boxHeight: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleFont: { size: isMobile ? 11 : 13, weight: 'bold' },
                            bodyFont: { size: isMobile ? 10 : 12 },
                            padding: isMobile ? 10 : 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function (context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ¥' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                };
            }

            function initCharts() {
                const barCtx = document.getElementById('barCanvas').getContext('2d');
                barChartInstance = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: datasets
                    },
                    options: getBarLineOptions('店舗別の月別給与比較', true)
                });

                const lineCtx = document.getElementById('lineCanvas').getContext('2d');
                lineChartInstance = new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: datasets.map(d => ({ ...d, tension: 0.4, fill: false, borderWidth: 3, pointRadius: 4, pointHoverRadius: 6 }))
                    },
                    options: getBarLineOptions('月別給与トレンド', false)
                });

                const radarCtx = document.getElementById('radarCanvas').getContext('2d');
                radarChartInstance = new Chart(radarCtx, {
                    type: 'radar',
                    data: {
                        labels: ['総収入', '平均月収', '稼働月数'],
                        datasets: radarDatasets
                    },
                    options: getRadarOptions()
                });

                const pieCtx = document.getElementById('pieCanvas').getContext('2d');
                pieChartInstance = new Chart(pieCtx, {
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

            document.querySelectorAll('.chart-type-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.chart-type-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    document.querySelectorAll('.chart-wrapper').forEach(chart => chart.classList.remove('active'));
                    const chartType = this.getAttribute('data-chart');
                    document.getElementById(chartType + 'Chart').classList.add('active');
                });
            });

            let resizeTimer;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function () {
                    const newIsMobile = window.innerWidth <= 768;

                    [barChartInstance, lineChartInstance, radarChartInstance, pieChartInstance].forEach(chart => {
                        if (chart) {
                            if (chart === pieChartInstance) {
                                chart.options.plugins.legend.position = newIsMobile ? 'bottom' : 'right';
                            } else {
                                chart.options.plugins.legend.position = newIsMobile ? 'bottom' : 'top';
                            }
                            chart.options.plugins.title.font.size = newIsMobile ? 14 : 18;
                            chart.options.plugins.legend.labels.font.size = newIsMobile ? 10 : 12;
                            chart.update();
                        }
                    });
                }, 250);
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>