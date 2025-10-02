<?php
include 'auth/protected.php';
$firstName = explode(' ', $_SESSION['name'])[0];

// Check if user is coming from login (first visit) or navigating back from sub-pages
$isFirstVisit = !isset($_SESSION['dashboard_visited']);
$_SESSION['dashboard_visited'] = true;
$loadingTime = $isFirstVisit ? 2000 : 600; // 2 seconds for first visit, 0.6 seconds for returns
?>
<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>給与ダッシュボード</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--primary-color:#4361ee;--secondary-color:#3a0ca3;--success-color:#4cc9f0;--warning-color:#f72585;--info-color:#7209b7;--light-color:#f8f9fa;--dark-color:#212529}
        body{font-family:'Poppins',sans-serif;background-color:#f4f7fe;margin:0;padding:0;min-height:100vh;overflow-x:hidden;position:relative}
        .bg-animation{position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;overflow:hidden}
        .bg-bubble{position:absolute;border-radius:50%;background:rgba(67,97,238,0.1);animation:float 8s ease-in-out infinite}
        .bg-bubble:nth-child(1){width:80px;height:80px;left:10%;top:10%;animation-delay:0s}
        .bg-bubble:nth-child(2){width:120px;height:120px;left:20%;top:40%;animation-delay:1s;background:rgba(114,9,183,0.1)}
        .bg-bubble:nth-child(3){width:180px;height:180px;right:10%;top:20%;animation-delay:2s;background:rgba(247,37,133,0.1)}
        .bg-bubble:nth-child(4){width:50px;height:50px;right:30%;bottom:20%;animation-delay:3s;background:rgba(76,201,240,0.1)}
        .bg-bubble:nth-child(5){width:150px;height:150px;left:5%;bottom:10%;animation-delay:4s;background:rgba(58,12,163,0.1)}
        @keyframes float{0%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-20px) rotate(180deg)}100%{transform:translateY(0) rotate(360deg)}}
        .dashboard-container{background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:2rem;margin-top:2rem;margin-bottom:2rem}
        .welcome-header{background:linear-gradient(45deg,var(--primary-color),var(--secondary-color));color:white;padding:1.5rem;border-radius:15px;margin-bottom:2rem;box-shadow:0 5px 15px rgba(0,0,0,0.1);position:relative;overflow:hidden}
        .welcome-header::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:linear-gradient(transparent,rgba(255,255,255,0.2),transparent);transform:rotate(45deg);animation:shine 4s infinite linear}
        @keyframes shine{0%{left:-150%}100%{left:150%}}
        .user-greeting{font-size:2rem;font-weight:700;margin-bottom:0.5rem}
        .subtitle{font-size:1rem;opacity:0.9}
        .dashboard-card{background:white;border-radius:15px;box-shadow:0 5px 15px rgba(0,0,0,0.05);transition:all 0.3s ease;margin-bottom:1.5rem;height:100%;overflow:hidden;position:relative;border:none}
        .dashboard-card:hover{transform:translateY(-10px);box-shadow:0 15px 30px rgba(0,0,0,0.1)}
        .card-icon{font-size:2.5rem;margin-bottom:1rem;display:inline-block;padding:1rem;border-radius:50%;background:rgba(67,97,238,0.1);color:var(--primary-color)}
        .card-add-salary .card-icon{background:rgba(76,201,240,0.1);color:var(--success-color)}
        .card-view-salaries .card-icon{background:rgba(67,97,238,0.1);color:var(--primary-color)}
        .card-salary-charts .card-icon{background:rgba(114,9,183,0.1);color:var(--info-color)}
        .card-monthly-chart .card-icon{background:rgba(247,37,133,0.1);color:var(--warning-color)}
        .card-annual-summary .card-icon{background:rgba(58,12,163,0.1);color:var(--secondary-color)}
        .card-logout .card-icon{background:rgba(220,53,69,0.1);color:#dc3545}
        .card-title{font-size:1.25rem;font-weight:600;margin-bottom:0.5rem}
        .card-text{color:#6c757d;font-size:0.9rem;margin-bottom:1.5rem}
        .dashboard-btn{padding:0.8rem 1.5rem;border-radius:10px;font-weight:600;letter-spacing:0.5px;transition:all 0.3s ease;text-transform:uppercase;font-size:0.85rem;border:none;width:100%;position:relative;z-index:10}
        .btn-add-salary{background:linear-gradient(45deg,#4cc9f0,#4895ef);color:white}
        .btn-view-salaries{background:linear-gradient(45deg,#4361ee,#3f37c9);color:white}
        .btn-salary-charts{background:linear-gradient(45deg,#7209b7,#560bad);color:white}
        .btn-monthly-chart{background:linear-gradient(45deg,#f72585,#b5179e);color:white}
        .btn-annual-summary{background:linear-gradient(45deg,#3a0ca3,#480ca8);color:white}
        .btn-logout{background:linear-gradient(45deg,#dc3545,#c71f37);color:white}
        .dashboard-btn:hover{transform:translateY(-3px);box-shadow:0 5px 15px rgba(0,0,0,0.2)}
        .footer{background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);border-radius:20px 20px 0 0;padding:1.5rem;text-align:center;margin-top:2rem;box-shadow:0 -5px 15px rgba(0,0,0,0.05)}
        .footer p{margin-bottom:0;color:#6c757d}
        .card-hover-overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(45deg,rgba(67,97,238,0.9),rgba(76,201,240,0.9));opacity:0;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;border-radius:15px;pointer-events:none;z-index:5}
        .card-add-salary:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(76,201,240,0.7),rgba(72,149,239,0.7));opacity:1}
        .card-view-salaries:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(67,97,238,0.7),rgba(63,55,201,0.7));opacity:1}
        .card-salary-charts:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(114,9,183,0.7),rgba(86,11,173,0.7));opacity:1}
        .card-monthly-chart:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(247,37,133,0.7),rgba(181,23,158,0.7));opacity:1}
        .card-annual-summary:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(58,12,163,0.7),rgba(72,12,168,0.7));opacity:1}
        .card-logout:hover .card-hover-overlay{background:linear-gradient(45deg,rgba(220,53,69,0.7),rgba(199,31,55,0.7));opacity:1}
        .card-hover-text{color:white;font-weight:600;text-transform:uppercase;letter-spacing:1px;pointer-events:none}
        .dashboard-card{cursor:pointer}
        .dashboard-card-link{position:absolute;width:100%;height:100%;top:0;left:0;z-index:4}
        @media (max-width:768px){.dashboard-container{padding:1.5rem}.welcome-header{padding:1rem}.user-greeting{font-size:1.5rem}.card-icon{font-size:2rem;padding:0.8rem}}
        
        /* 3D Money Loading Animation */
        .money-loader{position:fixed;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;z-index:9999;transition:opacity 0.8s ease,transform 0.8s ease}
        .money-loader.hide{opacity:0;transform:scale(1.1)}
        .money-scene{perspective:1000px;position:relative;width:300px;height:300px}
        .money-container{position:absolute;width:100%;height:100%;transform-style:preserve-3d;animation:rotate3d 4s infinite linear}
        @keyframes rotate3d{0%{transform:rotateX(-20deg) rotateY(0deg)}100%{transform:rotateX(-20deg) rotateY(360deg)}}
        .money-bill,.coin{position:absolute;transform-style:preserve-3d}
        .money-bill{width:120px;height:60px;background:linear-gradient(135deg,#2ecc71,#27ae60);border-radius:8px;box-shadow:0 10px 40px rgba(46,204,113,0.4);animation:float-bill 3s ease-in-out infinite}
        .money-bill::before{content:'¥';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:36px;font-weight:bold;color:#fff;text-shadow:2px 2px 4px rgba(0,0,0,0.3)}
        .money-bill::after{content:'';position:absolute;inset:8px;border:2px solid rgba(255,255,255,0.3);border-radius:4px}
        .coin{width:80px;height:80px;background:linear-gradient(135deg,#f39c12,#e67e22);border-radius:50%;box-shadow:0 10px 30px rgba(243,156,18,0.5);animation:float-coin 2.5s ease-in-out infinite}
        .coin::before{content:'¥';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:32px;font-weight:bold;color:#fff;text-shadow:2px 2px 4px rgba(0,0,0,0.4)}
        .coin::after{content:'';position:absolute;inset:6px;border:3px solid rgba(255,255,255,0.4);border-radius:50%}
        .bill-1{top:10%;left:50%;transform:translateX(-50%) translateZ(50px);animation-delay:0s}
        .bill-2{top:30%;right:10%;transform:translateZ(80px) rotateZ(30deg);animation-delay:0.5s}
        .bill-3{bottom:20%;left:15%;transform:translateZ(40px) rotateZ(-20deg);animation-delay:1s}
        .coin-1{top:50%;left:50%;transform:translate(-50%,-50%) translateZ(120px);animation-delay:0.3s}
        .coin-2{top:15%;left:20%;transform:translateZ(90px);animation-delay:0.8s}
        .coin-3{bottom:25%;right:25%;transform:translateZ(60px);animation-delay:1.2s}
        @keyframes float-bill{0%,100%{transform:translateX(-50%) translateY(0) translateZ(50px) rotateZ(0deg)}50%{transform:translateX(-50%) translateY(-20px) translateZ(80px) rotateZ(10deg)}}
        @keyframes float-coin{0%,100%{transform:translate(-50%,-50%) translateY(0) translateZ(120px) rotateX(0deg)}50%{transform:translate(-50%,-50%) translateY(-30px) translateZ(150px) rotateX(180deg)}}
        .loading-text{margin-top:80px;color:#fff;font-size:28px;font-weight:700;text-align:center;animation:pulse-text 1.5s ease-in-out infinite;text-shadow:0 4px 12px rgba(0,0,0,0.3)}
        @keyframes pulse-text{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.7;transform:scale(1.05)}}
        .loading-subtext{color:rgba(255,255,255,0.8);font-size:16px;margin-top:10px;animation:fade-in-out 2s ease-in-out infinite}
        @keyframes fade-in-out{0%,100%{opacity:0.5}50%{opacity:1}}
        .sparkle{position:absolute;width:4px;height:4px;background:#fff;border-radius:50%;animation:sparkle 2s ease-in-out infinite;box-shadow:0 0 10px #fff}
        @keyframes sparkle{0%,100%{opacity:0;transform:scale(0)}50%{opacity:1;transform:scale(1)}}
        .sparkle-1{top:20%;left:30%;animation-delay:0s}
        .sparkle-2{top:60%;right:25%;animation-delay:0.5s}
        .sparkle-3{bottom:30%;left:20%;animation-delay:1s}
        .sparkle-4{top:40%;right:35%;animation-delay:1.5s}
        .sparkle-5{top:70%;left:40%;animation-delay:0.7s}
        .sparkle-6{bottom:20%;right:30%;animation-delay:1.2s}
    </style>
</head>
<body>
    <!-- 3D Money Loading Animation -->
    <div class="money-loader" id="moneyLoader">
        <div class="money-scene">
            <div class="money-container">
                <div class="money-bill bill-1"></div>
                <div class="money-bill bill-2"></div>
                <div class="money-bill bill-3"></div>
                <div class="coin coin-1"></div>
                <div class="coin coin-2"></div>
                <div class="coin coin-3"></div>
            </div>
            <div class="sparkle sparkle-1"></div>
            <div class="sparkle sparkle-2"></div>
            <div class="sparkle sparkle-3"></div>
            <div class="sparkle sparkle-4"></div>
            <div class="sparkle sparkle-5"></div>
            <div class="sparkle sparkle-6"></div>
        </div>
        <div class="loading-text">給与ダッシュボード</div>
        <div class="loading-subtext">読み込み中...</div>
    </div>

    <!-- Background Animation -->
    <div class="bg-animation">
        <div class="bg-bubble"></div>
        <div class="bg-bubble"></div>
        <div class="bg-bubble"></div>
        <div class="bg-bubble"></div>
        <div class="bg-bubble"></div>
    </div>

    <div class="container">
        <div class="dashboard-container animate__animated animate__fadeIn">
            <div class="welcome-header animate__animated animate__fadeInDown">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="user-greeting">ようこそ、<?php echo $firstName; ?>さん！</h1>
                        <p class="subtitle">給与情報を簡単に管理しましょう</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p class="mb-0 text-light"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.1s">
                    <div class="dashboard-card card-add-salary">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-plus-circle"></i></span>
                            <h3 class="card-title">給与を追加</h3>
                            <p class="card-text">新しい給与の記録をデータベースに追加します。</p>
                            <a href="add_salary.php" class="btn dashboard-btn btn-add-salary">給与を追加</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">新しい給与を記録</span></div>
                        <a href="add_salary.php" class="dashboard-card-link"></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.2s">
                    <div class="dashboard-card card-view-salaries">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-list"></i></span>
                            <h3 class="card-title">給与の閲覧</h3>
                            <p class="card-text">すべての給与記録を閲覧・管理します。</p>
                            <a href="view_salaries.php" class="btn dashboard-btn btn-view-salaries">記録を閲覧</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">すべての記録を管理</span></div>
                        <a href="view_salaries.php" class="dashboard-card-link"></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.3s">
                    <div class="dashboard-card card-salary-charts">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
                            <h3 class="card-title">給与チャート</h3>
                            <p class="card-text">インタラクティブなチャートで給与データを視覚化します。</p>
                            <a href="salary_charts.php" class="btn dashboard-btn btn-salary-charts">チャートを表示</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">データを視覚化</span></div>
                        <a href="salary_charts.php" class="dashboard-card-link"></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.4s">
                    <div class="dashboard-card card-monthly-chart">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-calendar-alt"></i></span>
                            <h3 class="card-title">月別チャート</h3>
                            <p class="card-text">月ごとの給与のトレンドを確認しましょう。</p>
                            <a href="monthly_chart.php" class="btn dashboard-btn btn-monthly-chart">月別分析</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">月別内訳</span></div>
                        <a href="monthly_chart.php" class="dashboard-card-link"></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.5s">
                    <div class="dashboard-card card-annual-summary">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                            <h3 class="card-title">年間サマリー</h3>
                            <p class="card-text">あなたの年間財務の完全な概要を取得します。</p>
                            <a href="annual_summary.php" class="btn dashboard-btn btn-annual-summary">年間レポート</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">年末レビュー</span></div>
                        <a href="annual_summary.php" class="dashboard-card-link"></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay:0.6s">
                    <div class="dashboard-card card-logout">
                        <div class="card-body text-center p-4">
                            <span class="card-icon"><i class="fas fa-sign-out-alt"></i></span>
                            <h3 class="card-title">ログアウト</h3>
                            <p class="card-text">安全にアカウントからログアウトします。</p>
                            <a href="logout.php" class="btn dashboard-btn btn-logout">ログアウト</a>
                        </div>
                        <div class="card-hover-overlay"><span class="card-hover-text">安全なログアウト</span></div>
                        <a href="logout.php" class="dashboard-card-link"></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer animate__animated animate__fadeInUp">
            <p>© <?php echo date('Y'); ?> 給与トラッカーダッシュボード. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script>
        const loadingTime = <?php echo $loadingTime; ?>;
        $(window).on('load',function(){setTimeout(function(){$('#moneyLoader').addClass('hide');setTimeout(function(){$('#moneyLoader').css('display','none')},800)},loadingTime)});
        $(document).ready(function(){$('.dashboard-btn').on('click',function(e){e.stopPropagation()});$(document).on('mousemove',function(e){const moveX=(e.pageX*-1)/100;const moveY=(e.pageY*-1)/100;$('.bg-bubble').css({'transform':'translate('+moveX+'px, '+moveY+'px) rotate(0deg)'})});$('.welcome-header').on('mouseenter',function(){$(this).addClass('animate__pulse')}).on('mouseleave',function(){$(this).removeClass('animate__pulse')})});
    </script>
</body>
</html>