<?php
include 'auth/protected.php';
$firstName = explode(' ', $_SESSION['name'])[0];

// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; // Default to English for premium feel, or 'jp' if preferred

// Translations
$trans = [
    'en' => [
        'greeting' => "Hi, $firstName",
        'welcome_back' => 'Welcome back',
        'date_badge' => date('F j, Y'),
        'quote_main' => "Your financial journey<br>looks bright today.",
        'quote_sub' => "Track your progress.",
        'add_salary' => "Add Salary",
        'new_entry' => "New Entry",
        'history' => "History",
        'all_records' => "All Records",
        'analysis' => "Analysis",
        'visual_data' => "Visual Data",
        'trends' => "Trends",
        'monthly_view' => "Monthly View",
        'remit' => "Remit",
        'send_money' => "Send Money",
        'annual' => "Annual",
        'yearly_report' => "Yearly Report",
        'logout' => "Log Out",
        'version' => "Version 2.1 • Premium Edition"
    ],
    'jp' => [
        'greeting' => "こんにちは、$firstName さん",
        'welcome_back' => 'お帰りなさい',
        'date_badge' => date('Y年n月j日'),
        'quote_main' => "今日の財務状況は<br>順調です。",
        'quote_sub' => "進捗を確認しましょう。",
        'add_salary' => "給与を追加",
        'new_entry' => "新しい記録",
        'history' => "履歴",
        'all_records' => "全記録",
        'analysis' => "分析",
        'visual_data' => "視覚的データ",
        'trends' => "傾向",
        'monthly_view' => "月別表示",
        'remit' => "送金",
        'send_money' => "送金記録",
        'annual' => "年間",
        'yearly_report' => "年間レポート",
        'logout' => "ログアウト",
        'version' => "バージョン 2.1 • プレミアム版"
    ]
];

$t = $trans[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Dashboard | Salary Tracker</title>

    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Flag Icons -->
    <link href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css" rel="stylesheet">

    <!-- App Icons -->
    <link rel="icon" href="icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="icon/apple-touch-icon.png">

    <style>
        :root {
            /* Premium Color Palette */
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;

            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            --glass-blur: blur(20px);

            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-tertiary: rgba(255, 255, 255, 0.5);

            --accent-primary: #4facfe;
            --accent-secondary: #00f2fe;
            --accent-glow: rgba(79, 172, 254, 0.4);

            --card-radius: 24px;
            --btn-radius: 16px;

            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
        }

        /* ... existing styles ... */
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
            overflow-x: hidden;
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

        .app-container {
            max-width: 480px;
            /* Mobile-first optimizations */
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out;
        }

        .user-info h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 4px;
            background: linear-gradient(to right, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Premium Language Toggle */
        .lang-switch-container {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 30px;
            padding: 4px;
            display: flex;
            position: relative;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .lang-option {
            position: relative;
            z-index: 2;
            width: 40px;
            height: 32px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            text-decoration: none;
            opacity: 0.5;
            transform: scale(0.9);
        }

        .lang-option.active {
            opacity: 1;
            transform: scale(1);
        }

        .lang-option .fi {
            font-size: 18px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        /* Sliding Pill Background */
        .slide-pill {
            position: absolute;
            top: 4px;
            left: 4px;
            width: 40px;
            height: 32px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            z-index: 1;
            transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Slide Logic based on active state */
        .lang-switch-container[data-active="jp"] .slide-pill {
            transform: translateX(40px);
        }

        /* Greeting Card */
        .greeting-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--card-radius);
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out 0.1s backwards;
        }

        .greeting-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
        }

        .date-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .greeting-text {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 600;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .greeting-sub {
            font-size: 14px;
            color: var(--text-tertiary);
        }

        /* Dashboard Grid */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 24px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            height: 160px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            animation: fadeInUp 0.8s ease-out backwards;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .action-card:active {
            transform: scale(0.96);
            background: rgba(255, 255, 255, 0.12);
        }

        .action-card .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .card-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .card-content p {
            font-size: 11px;
            color: var(--text-tertiary);
            line-height: 1.3;
        }

        /* Card Variations */
        .card-primary {
            grid-column: span 2;
            height: 120px;
            flex-direction: row;
            align-items: center;
        }

        .card-primary .icon-box {
            margin-bottom: 0;
            margin-right: 20px;
            width: 60px;
            height: 60px;
            font-size: 28px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .card-primary .card-arrow {
            margin-left: auto;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* Staggered Animation Delays */
        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.3s;
        }

        .delay-3 {
            animation-delay: 0.4s;
        }

        .delay-4 {
            animation-delay: 0.5s;
        }

        .delay-5 {
            animation-delay: 0.6s;
        }

        .delay-6 {
            animation-delay: 0.7s;
        }

        /* Icon Colors */
        .icon-salary {
            color: #4facfe;
        }

        .icon-view {
            color: #43e97b;
        }

        .icon-chart {
            color: #fa709a;
        }

        .icon-monthly {
            color: #fee140;
        }

        .icon-remit {
            color: #f83600;
        }

        .icon-logout {
            color: #ff5858;
        }

        .btn-logout {
            width: 100%;
            padding: 18px;
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 20px;
            color: #ff6b6b;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s;
            animation: fadeInUp 0.8s ease-out 0.8s backwards;
            backdrop-filter: blur(10px);
        }

        .btn-logout:active {
            transform: scale(0.98);
            background: rgba(220, 53, 69, 0.25);
        }

        .version-text {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: var(--text-tertiary);
            animation: fadeIn 1s ease-out 1s backwards;
        }

        /* Animations */
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">

        <!-- Header -->
        <header class="header">
            <div class="user-info">
                <h1><?php echo $t['greeting']; ?></h1>
                <p><?php echo $t['welcome_back']; ?></p>
            </div>

            <!-- Premium Language Toggle -->
            <div class="lang-switch-container" data-active="<?php echo $lang; ?>">
                <div class="slide-pill"></div>
                <a href="?lang=en" class="lang-option <?php echo $lang === 'en' ? 'active' : ''; ?>">
                    <span class="fi fi-us"></span>
                </a>
                <a href="?lang=jp" class="lang-option <?php echo $lang === 'jp' ? 'active' : ''; ?>">
                    <span class="fi fi-jp"></span>
                </a>
            </div>
        </header>

        <!-- Greeting/Stats Area -->
        <div class="greeting-card">
            <div class="date-badge">
                <i class="far fa-calendar-alt me-2"></i> <?php echo $t['date_badge']; ?>
            </div>
            <div class="greeting-text">
                <?php echo $t['quote_main']; ?>
            </div>
            <div class="greeting-sub"><?php echo $t['quote_sub']; ?></div>
        </div>

        <!-- Navigation Grid -->
        <div class="grid-container">

            <!-- Primary Action: Add Salary -->
            <a href="add_salary.php" class="action-card card-primary delay-1">
                <div class="icon-box">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['add_salary']; ?></h3>
                    <p><?php echo $t['new_entry']; ?></p>
                </div>
                <div class="card-arrow"><i class="fas fa-chevron-right"></i></div>
            </a>

            <!-- View Salaries -->
            <a href="view_salaries.php" class="action-card delay-2">
                <div class="icon-box">
                    <i class="fas fa-wallet icon-view"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['history']; ?></h3>
                    <p><?php echo $t['all_records']; ?></p>
                </div>
            </a>

            <!-- Salary Charts -->
            <a href="salary_charts.php" class="action-card delay-3">
                <div class="icon-box">
                    <i class="fas fa-chart-pie icon-chart"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['analysis']; ?></h3>
                    <p><?php echo $t['visual_data']; ?></p>
                </div>
            </a>

            <!-- Monthly Chart -->
            <a href="monthly_chart.php" class="action-card delay-4">
                <div class="icon-box">
                    <i class="fas fa-chart-line icon-monthly"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['trends']; ?></h3>
                    <p><?php echo $t['monthly_view']; ?></p>
                </div>
            </a>

            <!-- Remittance -->
            <a href="remittance.php" class="action-card delay-5">
                <div class="icon-box">
                    <i class="fas fa-plane-departure icon-remit"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['remit']; ?></h3>
                    <p><?php echo $t['send_money']; ?></p>
                </div>
            </a>

            <!-- Annual Summary -->
            <a href="annual_summary.php" class="action-card delay-6">
                <div class="icon-box">
                    <i class="fas fa-file-invoice-dollar" style="color: #a29bfe;"></i>
                </div>
                <div class="card-content">
                    <h3><?php echo $t['annual']; ?></h3>
                    <p><?php echo $t['yearly_report']; ?></p>
                </div>
            </a>

        </div>

        <!-- Logout Button -->
        <a href="logout.php" class="btn-logout">
            <i class="fas fa-power-off"></i> <?php echo $t['logout']; ?>
        </a>

        <div class="version-text">
            <?php echo $t['version']; ?>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>