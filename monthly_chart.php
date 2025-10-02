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

// Generate colors for each store
$colors = [
    ['bg' => 'rgba(54, 162, 235, 0.2)', 'border' => 'rgba(54, 162, 235, 1)'],   // Blue
    ['bg' => 'rgba(255, 206, 86, 0.2)', 'border' => 'rgba(255, 206, 86, 1)'],   // Yellow
    ['bg' => 'rgba(75, 192, 192, 0.2)', 'border' => 'rgba(75, 192, 192, 1)'],   // Green
    ['bg' => 'rgba(153, 102, 255, 0.2)', 'border' => 'rgba(153, 102, 255, 1)'], // Purple
    ['bg' => 'rgba(255, 99, 132, 0.2)', 'border' => 'rgba(255, 99, 132, 1)'],   // Red
    ['bg' => 'rgba(255, 159, 64, 0.2)', 'border' => 'rgba(255, 159, 64, 1)'],   // Orange
    ['bg' => 'rgba(199, 199, 199, 0.2)', 'border' => 'rgba(199, 199, 199, 1)'], // Gray
    ['bg' => 'rgba(83, 102, 255, 0.2)', 'border' => 'rgba(83, 102, 255, 1)'],   // Indigo
    ['bg' => 'rgba(255, 99, 255, 0.2)', 'border' => 'rgba(255, 99, 255, 1)'],   // Pink
    ['bg' => 'rgba(50, 205, 50, 0.2)', 'border' => 'rgba(50, 205, 50, 1)']      // Lime
];

// Prepare datasets for Chart.js
$datasets = [];
$color_index = 0;
foreach ($store_data as $store => $values) {
    $color = $colors[$color_index % count($colors)];
    $datasets[] = [
        'label' => $store,
        'backgroundColor' => $color['bg'],
        'borderColor' => $color['border'],
        'data' => array_values($values),
        'fill' => true,
        'tension' => 0.4
    ];
    $color_index++;
}
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
        }

        body {
            background: linear-gradient(125deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientBG {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .container {
            max-width: 1200px;
            margin-top: 40px;
        }

        h1 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 40px;
            text-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInDown 1s ease-out;
        }

        h1::after {
            content: '📈';
            margin-left: 15px;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: cardEntrance 1s ease-out;
            margin-bottom: 30px;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-link:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            font-size: 18px;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            color: #adb5bd;
        }

        @media (max-width: 768px) {
            .chart-container { padding: 20px; }
            h1 { font-size: 28px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>月別給与チャート</h1>
        
        <div class="chart-container">
            <?php if (count($data) > 0): ?>
                <canvas id="monthlyChart"></canvas>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-chart-line"></i>
                    <p>まだ給与データがありません。<br>給与を追加してグラフを表示しましょう！</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
        </div>
    </div>

    <?php if (count($data) > 0): ?>
    <script>
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: <?= json_encode($datasets) ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    title: {
                        display: true,
                        text: '店舗別月別給与トレンド',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 15
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '金額（¥）',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return '¥' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '月',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>