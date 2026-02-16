<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

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

$colors = [
    'rgba(54, 162, 235, 0.6)',
    'rgba(255, 206, 86, 0.6)',
    'rgba(75, 192, 192, 0.6)',
    'rgba(153, 102, 255, 0.6)',
    'rgba(255, 99, 132, 0.6)',
    'rgba(255, 159, 64, 0.6)',
    'rgba(199, 199, 199, 0.6)',
    'rgba(83, 102, 255, 0.6)',
    'rgba(255, 99, 255, 0.6)',
    'rgba(50, 205, 50, 0.6)'
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
        'backgroundColor' => $colors[$color_index % count($colors)]
    ];
    $color_index++;
}
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>年間給与概要</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(125deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 10px;
        }

        @keyframes gradientBG {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 10px;
        }

        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: clamp(20px, 5vw, 32px);
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
        }

        .card-body {
            padding: 15px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 300px;
        }

        .total-income {
            background: linear-gradient(45deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 12px;
            padding: 20px 15px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }

        .total-income h3 {
            margin: 0;
            font-weight: 700;
            font-size: clamp(18px, 4vw, 26px);
            word-break: break-word;
        }

        h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 15px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            font-size: clamp(16px, 3.5vw, 22px);
            padding: 0 10px;
        }

        .list-group {
            gap: 8px;
        }

        .list-group-item {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 10px !important;
            padding: 12px 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            font-size: clamp(13px, 3vw, 15px);
        }

        .list-group-item:hover {
            transform: translateX(3px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }

        .list-group-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: clamp(14px, 3.2vw, 16px);
        }

        .growth-increase {
            color: #06d6a0;
            font-weight: 600;
        }

        .growth-decrease {
            color: #f72585;
            font-weight: 600;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: clamp(14px, 3vw, 16px);
            min-height: 44px;
        }

        .back-link:hover {
            transform: translateX(-3px);
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-data i {
            font-size: clamp(36px, 10vw, 48px);
            margin-bottom: 15px;
            display: block;
            color: #adb5bd;
        }

        .no-data p {
            font-size: clamp(14px, 3.5vw, 18px);
            line-height: 1.6;
        }

        @media (min-width: 768px) {
            .container {
                padding: 0 15px;
                margin: 40px auto;
            }

            .card-body {
                padding: 25px;
            }

            .chart-wrapper {
                height: 400px;
            }

            h2 {
                margin-bottom: 30px;
            }

            .total-income {
                padding: 25px 20px;
            }
        }

        @media (min-width: 1024px) {
            .chart-wrapper {
                height: 450px;
            }

            .card-body {
                padding: 30px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-calendar-alt"></i> 年間給与概要</h2>

        <?php if (count($data) > 0): ?>
            <div class="card">
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="annualChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="total-income">
                <h3><i class="fas fa-coins"></i> 総収入: ¥<?= number_format($grand_total) ?></h3>
            </div>

            <?php if (count($years) > 1): ?>
                <h4><i class="fas fa-chart-line"></i> 年間成長</h4>
                <ul class="list-group">
                    <?php
                    for ($i = 1; $i < count($years); $i++) {
                        $prev = $yearly_totals[$years[$i - 1]];
                        $curr = $yearly_totals[$years[$i]];
                        $diff = $curr - $prev;
                        $percent = $prev > 0 ? round(($diff / $prev) * 100, 2) : 100;
                        $trend_class = $diff >= 0 ? "growth-increase" : "growth-decrease";
                        $trend_icon = $diff >= 0 ? "<i class='fas fa-arrow-up'></i>" : "<i class='fas fa-arrow-down'></i>";
                        echo "<li class='list-group-item'>
                            <strong>{$years[$i - 1]} → {$years[$i]}</strong>
                            <span class='$trend_class'>¥" . number_format($diff) . " ($percent%) $trend_icon</span>
                          </li>";
                    }
                    ?>
                </ul>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="no-data">
                        <i class="fas fa-calendar-times"></i>
                        <p>まだ給与データがありません。<br>給与を追加してグラフを表示しましょう！</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
        </div>
    </div>

    <?php if (count($data) > 0): ?>
        <script>
            const isMobile = window.innerWidth < 768;
            const ctx = document.getElementById('annualChart').getContext('2d');
            const chart = new Chart(ctx, {
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
                            text: '各店舗の年間収入',
                            font: { size: isMobile ? 14 : 18, weight: 'bold' }
                        },
                        legend: {
                            display: true,
                            position: isMobile ? 'bottom' : 'top',
                            labels: {
                                font: { size: isMobile ? 10 : 12 },
                                padding: isMobile ? 8 : 15,
                                boxWidth: isMobile ? 12 : 40
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: !isMobile,
                                text: '金額（¥）',
                                font: { size: 14, weight: 'bold' }
                            },
                            ticks: {
                                font: { size: isMobile ? 10 : 12 },
                                callback: function (value) {
                                    return isMobile ? '¥' + (value / 10000).toFixed(0) + '万' : '¥' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            type: 'category',
                            labels: <?= json_encode($years) ?>,
                            title: {
                                display: !isMobile,
                                text: '年',
                                font: { size: 14, weight: 'bold' }
                            },
                            ticks: {
                                font: { size: isMobile ? 11 : 12 }
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