<?php
include 'auth/protected.php';
$firstName = explode(' ', $_SESSION['name'])[0];

$isFirstVisit = !isset($_SESSION['dashboard_visited']);
$_SESSION['dashboard_visited'] = true;
?>
<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>給与ダッシュボード</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
        }
        
        .dashboard-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .welcome-header {
            background: linear-gradient(45deg, #4361ee, #3a0ca3);
            color: white;
            padding: 15px 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(67,97,238,0.3);
        }
        
        .user-greeting {
            font-size: clamp(16px, 3.5vw, 22px);
            font-weight: 700;
            margin-bottom: 3px;
        }
        
        .subtitle {
            font-size: clamp(11px, 2.5vw, 13px);
            opacity: 0.95;
            margin-bottom: 5px;
        }
        
        .date-display {
            font-size: clamp(10px, 2.2vw, 12px);
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .dashboard-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        
        .dashboard-card::before {
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
        
        .dashboard-card::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, transparent 70%);
            opacity: 0;
            transition: all 0.5s;
            pointer-events: none;
        }
        
        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 25px rgba(0,0,0,0.2);
        }
        
        .dashboard-card:hover::before {
            opacity: 1;
        }
        
        .dashboard-card:hover::after {
            opacity: 0.3;
            bottom: -20%;
            left: -20%;
        }
        
        .dashboard-card:active {
            transform: scale(0.96);
        }
        
        .card-icon {
            font-size: clamp(24px, 5vw, 36px);
            margin-bottom: 8px;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
            transition: all 0.3s;
        }
        
        .dashboard-card:hover .card-icon {
            transform: scale(1.15) rotate(5deg);
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .card-title {
            font-size: clamp(11px, 2.8vw, 14px);
            font-weight: 700;
            margin-bottom: 2px;
            color: #212529;
            line-height: 1.2;
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .card-subtitle {
            font-size: clamp(9px, 2.2vw, 10px);
            color: #495057;
            font-weight: 500;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        
        .card-text {
            font-size: clamp(8px, 2vw, 9px);
            color: #6c757d;
            line-height: 1.3;
            font-weight: 400;
        }
        
        /* Card Colors */
        .card-add-salary { 
            border-color: rgba(76,201,240,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e0f7ff 100%);
        }
        .card-add-salary .card-icon { color: #4cc9f0; }
        .card-add-salary:hover { 
            border-color: #4cc9f0; 
            background: linear-gradient(135deg, #ffffff 0%, #c2f0ff 100%);
            box-shadow: 0 12px 25px rgba(76,201,240,0.3);
        }
        .card-add-salary::before { color: #4cc9f0; }
        
        .card-view-salaries { 
            border-color: rgba(67,97,238,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e8ebfe 100%);
        }
        .card-view-salaries .card-icon { color: #4361ee; }
        .card-view-salaries:hover { 
            border-color: #4361ee; 
            background: linear-gradient(135deg, #ffffff 0%, #d1d9fc 100%);
            box-shadow: 0 12px 25px rgba(67,97,238,0.3);
        }
        .card-view-salaries::before { color: #4361ee; }
        
        .card-salary-charts { 
            border-color: rgba(114,9,183,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #f3e5f7 100%);
        }
        .card-salary-charts .card-icon { color: #7209b7; }
        .card-salary-charts:hover { 
            border-color: #7209b7; 
            background: linear-gradient(135deg, #ffffff 0%, #e8d4f0 100%);
            box-shadow: 0 12px 25px rgba(114,9,183,0.3);
        }
        .card-salary-charts::before { color: #7209b7; }
        
        .card-monthly-chart { 
            border-color: rgba(247,37,133,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #ffe8f3 100%);
        }
        .card-monthly-chart .card-icon { color: #f72585; }
        .card-monthly-chart:hover { 
            border-color: #f72585; 
            background: linear-gradient(135deg, #ffffff 0%, #ffd1e8 100%);
            box-shadow: 0 12px 25px rgba(247,37,133,0.3);
        }
        .card-monthly-chart::before { color: #f72585; }
        
        .card-annual-summary { 
            border-color: rgba(58,12,163,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #e9e5f3 100%);
        }
        .card-annual-summary .card-icon { color: #3a0ca3; }
        .card-annual-summary:hover { 
            border-color: #3a0ca3; 
            background: linear-gradient(135deg, #ffffff 0%, #d6cde8 100%);
            box-shadow: 0 12px 25px rgba(58,12,163,0.3);
        }
        .card-annual-summary::before { color: #3a0ca3; }
        
        .card-remittance { 
            border-color: rgba(255,190,11,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #fff8e1 100%);
        }
        .card-remittance .card-icon { color: #ffbe0b; }
        .card-remittance:hover { 
            border-color: #ffbe0b; 
            background: linear-gradient(135deg, #ffffff 0%, #fff3cc 100%);
            box-shadow: 0 12px 25px rgba(255,190,11,0.3);
        }
        .card-remittance::before { color: #ffbe0b; }
        
        .card-logout { 
            border-color: rgba(220,53,69,0.4);
            background: linear-gradient(135deg, #ffffff 0%, #ffe8eb 100%);
        }
        .card-logout .card-icon { color: #dc3545; }
        .card-logout:hover { 
            border-color: #dc3545; 
            background: linear-gradient(135deg, #ffffff 0%, #ffd1d6 100%);
            box-shadow: 0 12px 25px rgba(220,53,69,0.3);
        }
        .card-logout::before { color: #dc3545; }
        
        .footer {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .footer p {
            margin: 0;
            color: #6c757d;
            font-size: clamp(11px, 2.5vw, 13px);
        }
        
        @media (min-width: 576px) {
            .dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
        }
        
        @media (min-width: 768px) {
            body { padding: 20px; }
            .container { padding: 15px; }
            .dashboard-container { padding: 25px; margin-bottom: 20px; }
            .welcome-header { padding: 25px 20px; margin-bottom: 25px; }
            .dashboard-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }
            .dashboard-card { padding: 25px 20px; }
        }
        
        @media (min-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        @media (hover: none) and (pointer: coarse) {
            .dashboard-card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-container">
            <div class="welcome-header">
                <h1 class="user-greeting">ようこそ、<?php echo $firstName; ?>さん！</h1>
                <p class="subtitle">給与情報を簡単に管理しましょう</p>
                <p class="date-display"><?php echo date('Y年m月d日 (l)'); ?></p>
            </div>

            <div class="dashboard-grid">
                <a href="add_salary.php" class="dashboard-card card-add-salary">
                    <i class="fas fa-plus-circle card-icon"></i>
                    <h3 class="card-title">給与を追加</h3>
                    <p class="card-subtitle">Add Salary</p>
                    <p class="card-text">新しい給与記録</p>
                </a>

                <a href="view_salaries.php" class="dashboard-card card-view-salaries">
                    <i class="fas fa-list card-icon"></i>
                    <h3 class="card-title">給与の閲覧</h3>
                    <p class="card-subtitle">View Salaries</p>
                    <p class="card-text">全記録を管理</p>
                </a>

                <a href="salary_charts.php" class="dashboard-card card-salary-charts">
                    <i class="fas fa-chart-bar card-icon"></i>
                    <h3 class="card-title">給与チャート</h3>
                    <p class="card-subtitle">Salary Charts</p>
                    <p class="card-text">データを視覚化</p>
                </a>

                <a href="monthly_chart.php" class="dashboard-card card-monthly-chart">
                    <i class="fas fa-calendar-alt card-icon"></i>
                    <h3 class="card-title">月別チャート</h3>
                    <p class="card-subtitle">Monthly Chart</p>
                    <p class="card-text">月別分析</p>
                </a>

                <a href="annual_summary.php" class="dashboard-card card-annual-summary">
                    <i class="fas fa-file-invoice-dollar card-icon"></i>
                    <h3 class="card-title">年間サマリー</h3>
                    <p class="card-subtitle">Annual Summary</p>
                    <p class="card-text">年間レポート</p>
                </a>

                <a href="remittance.php" class="dashboard-card card-remittance">
                    <i class="fas fa-paper-plane card-icon"></i>
                    <h3 class="card-title">送金記録</h3>
                    <p class="card-subtitle">Remittance</p>
                    <p class="card-text">母国への送金</p>
                </a>

                <a href="logout.php" class="dashboard-card card-logout">
                    <i class="fas fa-sign-out-alt card-icon"></i>
                    <h3 class="card-title">ログアウト</h3>
                    <p class="card-subtitle">Logout</p>
                    <p class="card-text">安全に終了</p>
                </a>
            </div>
        </div>

        <div class="footer">
            <p>© <?php echo date('Y'); ?> 給与トラッカーダッシュボード - すべての権利を保有</p>
        </div>
    </div>
</body>
</html>