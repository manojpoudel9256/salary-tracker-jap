<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Get total salary
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

// Get total sent
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$remaining = $total_salary - $total_sent;

// Get breakdown by recipient
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(recipient, 'その他') as recipient,
        SUM(amount) as total
    FROM remittances 
    WHERE user_id = ?
    GROUP BY recipient
    ORDER BY total DESC
");
$stmt->execute([$user_id]);
$recipients_data = $stmt->fetchAll();

// Prepare data for charts
$recipient_labels = [];
$recipient_amounts = [];
$recipient_colors = [
    '#f72585',
    '#b5179e',
    '#7209b7',
    '#560bad',
    '#480ca8',
    '#3a0ca3',
    '#3f37c9',
    '#4361ee',
    '#4895ef',
    '#4cc9f0'
];

foreach ($recipients_data as $index => $data) {
    $recipient_labels[] = $data['recipient'];
    $recipient_amounts[] = $data['total'];
}
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>送金チャート分析</title>
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
            padding: 15px 10px;
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
            margin: 0 auto;
            padding: 0;
        }

        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: 24px;
            padding: 0 10px;
        }

        h2 i {
            font-size: 22px;
            margin-right: 8px;
        }

        .section-title {
            color: white;
            font-weight: 600;
            margin: 25px 0 15px 0;
            font-size: 18px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 0 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-body {
            padding: 25px 20px;
        }

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 350px;
            margin: 0 auto;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            text-align: center;
            transition: transform 0.2s;
        }

        .summary-card:active {
            transform: scale(0.98);
        }

        .summary-card.salary {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }

        .summary-card.sent {
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: white;
        }

        .summary-card.remaining {
            background: linear-gradient(135deg, #06d6a0, #02c39a);
            color: white;
        }

        .summary-icon {
            font-size: 32px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-label {
            font-size: 11px;
            opacity: 0.95;
            margin-bottom: 8px;
            font-weight: 500;
            line-height: 1.3;
        }

        .summary-value {
            font-size: 22px;
            font-weight: 700;
            word-break: break-all;
        }

        .legend-list {
            margin-top: 20px;
            padding: 0;
            list-style: none;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .legend-item:active {
            background: #e9ecef;
            transform: scale(0.98);
        }

        .legend-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .legend-name {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }

        .legend-amount {
            font-size: 14px;
            font-weight: 700;
            color: #212529;
        }

        .legend-percentage {
            font-size: 12px;
            color: #6c757d;
            margin-left: 5px;
        }

        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            min-height: 48px;
            margin: 5px;
        }

        .back-link:active {
            transform: scale(0.98);
            background: rgba(255, 255, 255, 0.3);
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .bottom-nav {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .info-text {
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            margin-top: 15px;
            font-style: italic;
        }

        @media (min-width: 576px) {
            h2 {
                font-size: 28px;
            }

            .section-title {
                font-size: 20px;
                margin: 30px 0 20px 0;
            }

            .summary-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }

            .summary-card {
                padding: 24px 20px;
            }

            .summary-icon {
                font-size: 36px;
            }

            .summary-label {
                font-size: 12px;
            }

            .summary-value {
                font-size: 26px;
            }

            .chart-wrapper {
                height: 400px;
            }

            .card-body {
                padding: 30px 25px;
            }
        }

        @media (min-width: 768px) {
            body {
                padding: 30px 20px;
            }

            h2 {
                font-size: 32px;
                margin-bottom: 30px;
            }

            .section-title {
                font-size: 22px;
            }

            .chart-wrapper {
                height: 450px;
            }

            .back-link {
                font-size: 15px;
                padding: 15px 28px;
            }

            .charts-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }

        @media (min-width: 992px) {
            .chart-wrapper {
                height: 500px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-chart-pie"></i> 送金チャート分析</h2>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card salary">
                <i class="fas fa-wallet summary-icon"></i>
                <div class="summary-label">総給与<br>Total Salary</div>
                <div class="summary-value">¥
                    <?= number_format($total_salary) ?>
                </div>
            </div>

            <div class="summary-card sent">
                <i class="fas fa-paper-plane summary-icon"></i>
                <div class="summary-label">総送金額<br>Total Sent</div>
                <div class="summary-value">¥
                    <?= number_format($total_sent) ?>
                </div>
            </div>

            <div class="summary-card remaining">
                <i class="fas fa-piggy-bank summary-icon"></i>
                <div class="summary-label">残高<br>Remaining</div>
                <div class="summary-value">¥
                    <?= number_format($remaining) ?>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <!-- Donut Chart: Salary vs Sent -->
            <div>
                <h4 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    給与 vs 送金 (Salary vs Sent)
                </h4>
                <div class="card">
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="donutChart"></canvas>
                        </div>
                        <p class="info-text">
                            <i class="fas fa-info-circle"></i>
                            給与のうち、どれくらい送金したか
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pie Chart: By Recipient -->
            <div>
                <h4 class="section-title">
                    <i class="fas fa-users"></i>
                    受取人別 (By Recipient)
                </h4>
                <div class="card">
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="pieChart"></canvas>
                        </div>

                        <!-- Legend List -->
                        <ul class="legend-list">
                            <?php foreach ($recipients_data as $index => $data): ?>
                                <?php
                                $percentage = $total_sent > 0 ? round(($data['total'] / $total_sent) * 100, 1) : 0;
                                ?>
                                <li class="legend-item">
                                    <div class="legend-info">
                                        <div class="legend-color"
                                            style="background-color: <?= $recipient_colors[$index % count($recipient_colors)] ?>;">
                                        </div>
                                        <span class="legend-name">
                                            <?= htmlspecialchars($data['recipient']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="legend-amount">¥
                                            <?= number_format($data['total']) ?>
                                        </span>
                                        <span class="legend-percentage">(
                                            <?= $percentage ?>%)
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <p class="info-text">
                            <i class="fas fa-info-circle"></i>
                            誰にどれだけ送金したか
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-nav">
            <a href="remittance.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
        </div>
    </div>

    <script>
        const isMobile = window.innerWidth < 768;

        // Donut Chart: Salary vs Sent
        const donutCtx = document.getElementById('donutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['残高 (Remaining)', '送金済 (Sent)'],
                datasets: [{
                    data: [<?= $remaining ?>, <?= $total_sent ?>],
                    backgroundColor: [
                        '#06d6a0',
                        '#f72585'
                    ],
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '給与の使い道',
                        font: {
                            size: isMobile ? 16 : 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: isMobile ? 10 : 15,
                            bottom: isMobile ? 15 : 20
                        }
                    },
                    legend: {
                        display: true,
                        position: isMobile ? 'bottom' : 'bottom',
                        labels: {
                            font: { size: isMobile ? 12 : 14 },
                            padding: isMobile ? 12 : 20,
                            boxWidth: isMobile ? 15 : 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: isMobile ? 12 : 15,
                        titleFont: { size: isMobile ? 13 : 15 },
                        bodyFont: { size: isMobile ? 12 : 14 },
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = <?= $total_salary ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ¥' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Pie Chart: By Recipient
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode($recipient_labels) ?>,
                datasets: [{
                    data: <?= json_encode($recipient_amounts) ?>,
                    backgroundColor: <?= json_encode(array_slice($recipient_colors, 0, count($recipient_labels))) ?>,
                    borderWidth: 3,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '受取人別送金額',
                        font: {
                            size: isMobile ? 16 : 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: isMobile ? 10 : 15,
                            bottom: isMobile ? 15 : 20
                        }
                    },
                    legend: {
                        display: isMobile ? false : true,
                        position: 'bottom',
                        labels: {
                            font: { size: 13 },
                            padding: 15,
                            boxWidth: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: isMobile ? 12 : 15,
                        titleFont: { size: isMobile ? 13 : 15 },
                        bodyFont: { size: isMobile ? 12 : 14 },
                        callbacks: {
                            label: function (context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = <?= $total_sent ?>;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ¥' + value.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>