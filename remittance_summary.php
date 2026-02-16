<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Overall totals
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 2) : 0;

// Monthly breakdown (last 12 months) - with cumulative totals
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

    // Add to cumulative totals
    $cumulative_salary += $monthly_salary;
    $cumulative_sent += $monthly_sent;

    $monthly_data[] = [
        'month' => $month,
        'month_name' => date('Y年m月', strtotime($month . '-01')),
        'monthly_salary' => $monthly_salary,
        'monthly_sent' => $monthly_sent,
        'salary' => $cumulative_salary,
        'sent' => $cumulative_sent,
        'remaining' => $cumulative_salary - $cumulative_sent,
        'percentage' => $cumulative_salary > 0 ? round(($cumulative_sent / $cumulative_salary) * 100, 2) : 0
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
        'percentage' => $salary > 0 ? round(($sent / $salary) * 100, 2) : 0
    ];
}

// Chart data
$chart_months = array_column(array_reverse($monthly_data), 'month_name');
$chart_salary = array_column(array_reverse($monthly_data), 'salary');
$chart_sent = array_column(array_reverse($monthly_data), 'sent');
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>送金サマリー</title>
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
            padding: 20px 15px;
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

        .summary-card.percentage {
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
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

        .chart-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
            padding: 10px 0;
        }

        /* Mobile Card View for Tables */
        .data-item {
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .data-item:last-child {
            border-bottom: none;
        }

        .data-item:active {
            background: rgba(255, 190, 11, 0.1);
        }

        .data-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .data-month {
            font-size: 15px;
            font-weight: 700;
            color: #495057;
        }

        .data-percentage {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            color: white;
        }

        .percentage-high {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }

        .percentage-medium {
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
        }

        .percentage-low {
            background: linear-gradient(135deg, #06d6a0, #02c39a);
        }

        .data-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .data-detail-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 12px;
        }

        .data-detail-label {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .data-detail-value {
            font-size: 16px;
            font-weight: 700;
            color: #212529;
        }

        .data-detail-item.salary {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(58, 12, 163, 0.1));
        }

        .data-detail-item.salary .data-detail-value {
            color: #4361ee;
        }

        .data-detail-item.sent {
            background: linear-gradient(135deg, rgba(247, 37, 133, 0.1), rgba(181, 23, 158, 0.1));
        }

        .data-detail-item.sent .data-detail-value {
            color: #f72585;
        }

        .data-detail-item.remaining {
            background: linear-gradient(135deg, rgba(6, 214, 160, 0.1), rgba(2, 195, 154, 0.1));
        }

        .data-detail-item.remaining .data-detail-value {
            color: #06d6a0;
        }

        /* Desktop Table View */
        .table-responsive {
            border-radius: 10px;
            overflow-x: auto;
            display: none;
        }

        .table {
            margin-bottom: 0;
            font-size: 14px;
        }

        .table thead {
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
            color: white;
        }

        .table thead th {
            border: none;
            padding: 16px 12px;
            font-weight: 600;
            font-size: 13px;
        }

        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: rgba(255, 190, 11, 0.1);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg-success {
            background: linear-gradient(135deg, #06d6a0, #02c39a) !important;
        }

        .bg-warning {
            background: linear-gradient(135deg, #ffbe0b, #ffa500) !important;
        }

        .bg-danger {
            background: linear-gradient(135deg, #f72585, #b5179e) !important;
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

        /* Desktop Breakpoint */
        @media (min-width: 576px) {
            h2 {
                font-size: 28px;
            }

            .section-title {
                font-size: 20px;
                margin: 30px 0 20px 0;
            }

            .summary-grid {
                grid-template-columns: repeat(4, 1fr);
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
                height: 350px;
            }

            /* Show table, hide cards on desktop */
            .data-item {
                display: none;
            }

            .table-responsive {
                display: block !important;
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

            .card-body {
                padding: 25px;
            }

            .chart-wrapper {
                height: 400px;
            }

            .back-link {
                font-size: 15px;
                padding: 15px 28px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-chart-line"></i> 送金サマリー</h2>

        <!-- Overall Summary -->
        <h4 class="section-title">
            <i class="fas fa-globe"></i>
            全期間の概要 (Overall Summary)
        </h4>
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

            <div class="summary-card percentage">
                <i class="fas fa-chart-pie summary-icon"></i>
                <div class="summary-label">送金率<br>Sent %</div>
                <div class="summary-value">
                    <?= $percentage_sent ?>%
                </div>
            </div>
        </div>

        <!-- Chart -->
        <h4 class="section-title">
            <i class="fas fa-chart-area"></i>
            月別推移 (Monthly Trend)
        </h4>
        <div class="card">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <h4 class="section-title">
            <i class="fas fa-calendar-alt"></i>
            月別累計 (Monthly Cumulative Total)
        </h4>
        <div class="card">
            <div class="card-body">
                <!-- Mobile Card View -->
                <?php foreach ($monthly_data as $data): ?>
                    <?php if ($data['salary'] > 0 || $data['sent'] > 0): ?>
                        <div class="data-item">
                            <div class="data-header">
                                <div>
                                    <span class="data-month">
                                        <?= $data['month_name'] ?>
                                    </span>
                                    <?php if ($data['monthly_salary'] > 0 || $data['monthly_sent'] > 0): ?>
                                        <div style="font-size: 11px; color: #6c757d; margin-top: 2px;">
                                            今月: 給与 ¥
                                            <?= number_format($data['monthly_salary']) ?> / 送金 ¥
                                            <?= number_format($data['monthly_sent']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span
                                    class="data-percentage <?= $data['percentage'] > 50 ? 'percentage-high' : ($data['percentage'] > 30 ? 'percentage-medium' : 'percentage-low') ?>">
                                    <?= $data['percentage'] ?>%
                                </span>
                            </div>
                            <div class="data-details">
                                <div class="data-detail-item salary">
                                    <div class="data-detail-label">累計給与 (Total Salary)</div>
                                    <div class="data-detail-value">¥
                                        <?= number_format($data['salary']) ?>
                                    </div>
                                </div>
                                <div class="data-detail-item sent">
                                    <div class="data-detail-label">累計送金 (Total Sent)</div>
                                    <div class="data-detail-value">¥
                                        <?= number_format($data['sent']) ?>
                                    </div>
                                </div>
                                <div class="data-detail-item remaining">
                                    <div class="data-detail-label">残高 (Remaining)</div>
                                    <div class="data-detail-value">¥
                                        <?= number_format($data['remaining']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>月 (Month)</th>
                                <th>今月給与<br><small>This Month</small></th>
                                <th>今月送金<br><small>This Month</small></th>
                                <th>累計給与<br><small>Total Salary</small></th>
                                <th>累計送金<br><small>Total Sent</small></th>
                                <th>残高<br><small>Remaining</small></th>
                                <th>率 (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_data as $data): ?>
                                <?php if ($data['salary'] > 0 || $data['sent'] > 0): ?>
                                    <tr>
                                        <td><strong>
                                                <?= $data['month_name'] ?>
                                            </strong></td>
                                        <td>
                                            <?= $data['monthly_salary'] > 0 ? '¥' . number_format($data['monthly_salary']) : '-' ?>
                                        </td>
                                        <td>
                                            <?= $data['monthly_sent'] > 0 ? '<span class="badge bg-warning">¥' . number_format($data['monthly_sent']) . '</span>' : '-' ?>
                                        </td>
                                        <td><strong>¥
                                                <?= number_format($data['salary']) ?>
                                            </strong></td>
                                        <td><strong><span class="badge bg-danger">¥
                                                    <?= number_format($data['sent']) ?>
                                                </span></strong></td>
                                        <td>¥
                                            <?= number_format($data['remaining']) ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge <?= $data['percentage'] > 50 ? 'bg-danger' : ($data['percentage'] > 30 ? 'bg-warning' : 'bg-success') ?>">
                                                <?= $data['percentage'] ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Yearly Breakdown -->
        <h4 class="section-title">
            <i class="fas fa-calendar-check"></i>
            年別詳細 (Yearly Details)
        </h4>
        <div class="card">
            <div class="card-body">
                <!-- Mobile Card View -->
                <?php foreach ($yearly_data as $data): ?>
                    <div class="data-item">
                        <div class="data-header">
                            <span class="data-month">
                                <?= $data['year'] ?>年
                            </span>
                            <span
                                class="data-percentage <?= $data['percentage'] > 50 ? 'percentage-high' : ($data['percentage'] > 30 ? 'percentage-medium' : 'percentage-low') ?>">
                                <?= $data['percentage'] ?>%
                            </span>
                        </div>
                        <div class="data-details">
                            <div class="data-detail-item salary">
                                <div class="data-detail-label">給与 (Salary)</div>
                                <div class="data-detail-value">¥
                                    <?= number_format($data['salary']) ?>
                                </div>
                            </div>
                            <div class="data-detail-item sent">
                                <div class="data-detail-label">送金 (Sent)</div>
                                <div class="data-detail-value">¥
                                    <?= number_format($data['sent']) ?>
                                </div>
                            </div>
                            <div class="data-detail-item remaining">
                                <div class="data-detail-label">残高 (Remain)</div>
                                <div class="data-detail-value">¥
                                    <?= number_format($data['remaining']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>年 (Year)</th>
                                <th>給与 (Salary)</th>
                                <th>送金 (Sent)</th>
                                <th>残高 (Remain)</th>
                                <th>率 (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearly_data as $data): ?>
                                <tr>
                                    <td><strong>
                                            <?= $data['year'] ?>年
                                        </strong></td>
                                    <td>¥
                                        <?= number_format($data['salary']) ?>
                                    </td>
                                    <td><span class="badge bg-warning">¥
                                            <?= number_format($data['sent']) ?>
                                        </span></td>
                                    <td>¥
                                        <?= number_format($data['remaining']) ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?= $data['percentage'] > 50 ? 'bg-danger' : ($data['percentage'] > 30 ? 'bg-warning' : 'bg-success') ?>">
                                            <?= $data['percentage'] ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        const ctx = document.getElementById('monthlyChart').getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_months) ?>,
                datasets: [
                    {
                        label: '給与 (Salary)',
                        data: <?= json_encode($chart_salary) ?>,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: isMobile ? 3 : 4,
                        pointHoverRadius: isMobile ? 5 : 6
                    },
                    {
                        label: '送金 (Sent)',
                        data: <?= json_encode($chart_sent) ?>,
                        borderColor: '#f72585',
                        backgroundColor: 'rgba(247, 37, 133, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: isMobile ? 3 : 4,
                        pointHoverRadius: isMobile ? 5 : 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '給与 vs 送金 (12ヶ月)',
                        font: {
                            size: isMobile ? 15 : 18,
                            weight: 'bold'
                        },
                        padding: {
                            top: isMobile ? 5 : 10,
                            bottom: isMobile ? 15 : 20
                        }
                    },
                    legend: {
                        display: true,
                        position: isMobile ? 'bottom' : 'top',
                        labels: {
                            font: { size: isMobile ? 11 : 13 },
                            padding: isMobile ? 10 : 15,
                            boxWidth: isMobile ? 15 : 40,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: isMobile ? 10 : 12,
                        titleFont: { size: isMobile ? 12 : 14 },
                        bodyFont: { size: isMobile ? 11 : 13 },
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
                        ticks: {
                            font: { size: isMobile ? 10 : 12 },
                            callback: function (value) {
                                if (isMobile) {
                                    return value >= 10000 ? '¥' + (value / 10000).toFixed(0) + '万' : '¥' + value.toLocaleString();
                                }
                                return '¥' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: isMobile ? 9 : 11 },
                            maxRotation: isMobile ? 45 : 0,
                            minRotation: isMobile ? 45 : 0
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    </script>
</body>

</html>