<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Get quick stats
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_salary = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_sent = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM remittances WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_records = $stmt->fetch()['count'] ?? 0;

$remaining = $total_salary - $total_sent;
$percentage_sent = $total_salary > 0 ? round(($total_sent / $total_salary) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>送金管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            margin: 0 auto;
            padding: 10px;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            padding: 12px;
            margin-bottom: 12px;
        }

        .welcome-header {
            background: linear-gradient(45deg, #ffbe0b, #ffa500);
            color: white;
            padding: 15px 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(255, 190, 11, 0.3);
        }

        .header-title {
            font-size: clamp(18px, 4vw, 26px);
            font-weight: 700;
            margin-bottom: 3px;
        }

        .header-subtitle {
            font-size: clamp(11px, 2.5vw, 13px);
            opacity: 0.95;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .stat-card.salary {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }

        .stat-card.sent {
            background: linear-gradient(135deg, #f72585, #b5179e);
            color: white;
        }

        .stat-card.remaining {
            background: linear-gradient(135deg, #06d6a0, #02c39a);
            color: white;
        }

        .stat-card.records {
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
            color: white;
        }

        .stat-icon {
            font-size: clamp(20px, 4vw, 28px);
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .stat-label {
            font-size: clamp(9px, 2.2vw, 11px);
            opacity: 0.95;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: clamp(14px, 3.5vw, 20px);
            font-weight: 700;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }

        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 10px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-decoration: none;
            border: 2px solid transparent;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, currentColor, transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-card:active {
            transform: scale(0.96);
        }

        .card-icon {
            font-size: clamp(28px, 6vw, 40px);
            margin-bottom: 8px;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            transition: all 0.3s;
        }

        .action-card:hover .card-icon {
            transform: scale(1.15) rotate(5deg);
        }

        .card-title {
            font-size: clamp(12px, 3vw, 15px);
            font-weight: 700;
            margin-bottom: 2px;
            color: #212529;
            line-height: 1.2;
        }

        .card-subtitle {
            font-size: clamp(9px, 2.2vw, 11px);
            color: #495057;
            font-weight: 500;
            line-height: 1.2;
        }

        .card-add {
            border-color: rgba(6, 214, 160, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e0fff8 100%);
        }

        .card-add .card-icon {
            color: #06d6a0;
        }

        .card-add:hover {
            border-color: #06d6a0;
            background: linear-gradient(135deg, #ffffff 0%, #c2fae9 100%);
        }

        .card-add::before {
            color: #06d6a0;
        }

        .card-view {
            border-color: rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e8ebfe 100%);
        }

        .card-view .card-icon {
            color: #4361ee;
        }

        .card-view:hover {
            border-color: #4361ee;
            background: linear-gradient(135deg, #ffffff 0%, #d1d9fc 100%);
        }

        .card-view::before {
            color: #4361ee;
        }

        .card-summary {
            border-color: rgba(247, 37, 133, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #ffe8f3 100%);
        }

        .card-summary .card-icon {
            color: #f72585;
        }

        .card-summary:hover {
            border-color: #f72585;
            background: linear-gradient(135deg, #ffffff 0%, #ffd1e8 100%);
        }

        .card-summary::before {
            color: #f72585;
        }

        .card-chart {
            border-color: rgba(114, 9, 183, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #f3e5f7 100%);
        }

        .card-chart .card-icon {
            color: #7209b7;
        }

        .card-chart:hover {
            border-color: #7209b7;
            background: linear-gradient(135deg, #ffffff 0%, #e8d4f0 100%);
        }

        .card-chart::before {
            color: #7209b7;
        }

        .card-back {
            border-color: rgba(255, 190, 11, 0.4);
            background: linear-gradient(135deg, #ffffff 0%, #fff8e1 100%);
        }

        .card-back .card-icon {
            color: #ffbe0b;
        }

        .card-back:hover {
            border-color: #ffbe0b;
            background: linear-gradient(135deg, #ffffff 0%, #fff3c4 100%);
        }

        .card-back::before {
            color: #ffbe0b;
        }

        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: clamp(13px, 3vw, 15px);
            min-height: 44px;
        }

        .back-link:hover {
            transform: translateX(-3px);
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        @media (min-width: 576px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
            }

            .actions-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
        }

        @media (min-width: 768px) {
            body {
                padding: 20px;
            }

            .container {
                padding: 15px;
            }

            .dashboard-container {
                padding: 25px;
                margin-bottom: 20px;
            }

            .welcome-header {
                padding: 20px 18px;
                margin-bottom: 20px;
            }

            .stats-grid {
                margin-bottom: 25px;
                gap: 15px;
            }

            .stat-card {
                padding: 15px;
            }

            .actions-grid {
                gap: 15px;
            }

            .action-card {
                padding: 20px 15px;
            }
        }

        @media (min-width: 992px) {
            .actions-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (hover: none) and (pointer: coarse) {
            .action-card:hover {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="welcome-header">
                <h1 class="header-title"><i class="fas fa-paper-plane"></i> 送金管理</h1>
                <p class="header-subtitle">Remittance Management - 母国への送金を管理</p>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card salary">
                    <i class="fas fa-wallet stat-icon"></i>
                    <div class="stat-label">総給与</div>
                    <div class="stat-value">¥<?= number_format($total_salary) ?></div>
                </div>

                <div class="stat-card sent">
                    <i class="fas fa-paper-plane stat-icon"></i>
                    <div class="stat-label">総送金額</div>
                    <div class="stat-value">¥<?= number_format($total_sent) ?></div>
                </div>

                <div class="stat-card remaining">
                    <i class="fas fa-piggy-bank stat-icon"></i>
                    <div class="stat-label">残高</div>
                    <div class="stat-value">¥<?= number_format($remaining) ?></div>
                </div>

                <div class="stat-card records">
                    <i class="fas fa-file-invoice-dollar stat-icon"></i>
                    <div class="stat-label">記録数</div>
                    <div class="stat-value"><?= $total_records ?></div>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="actions-grid">
                <a href="add_remittance.php" class="action-card card-add">
                    <i class="fas fa-plus-circle card-icon"></i>
                    <h3 class="card-title">送金を追加</h3>
                    <p class="card-subtitle">Add Remittance</p>
                </a>

                <a href="view_remittance.php" class="action-card card-view">
                    <i class="fas fa-list card-icon"></i>
                    <h3 class="card-title">送金履歴</h3>
                    <p class="card-subtitle">View History</p>
                </a>

                <a href="remittance_summary.php" class="action-card card-summary">
                    <i class="fas fa-chart-line card-icon"></i>
                    <h3 class="card-title">詳細分析</h3>
                    <p class="card-subtitle">Summary</p>
                </a>

                <a href="remittance_chart.php" class="action-card card-chart">
                    <i class="fas fa-chart-pie card-icon"></i>
                    <h3 class="card-title">チャート分析</h3>
                    <p class="card-subtitle">Chart Analysis</p>
                </a>

                <a href="dashboard.php" class="action-card card-back">
                    <i class="fas fa-home card-icon"></i>
                    <h3 class="card-title">戻る</h3>
                    <p class="card-subtitle">Dashboard</p>
                </a>
            </div>
        </div>
    </div>
</body>

</html>