<?php
include '../config/db.php';

$lang = $_COOKIE['lang'] ?? 'en';

$trans = [
    'en' => [
        'app_name' => 'Salary Tracker',
        'join' => 'Create Account',
        'name_ph' => 'Full name',
        'email_ph' => 'Email address',
        'pass_ph' => 'Create password',
        'register' => 'Sign Up',
        'have_account' => 'Already have an account?',
        'login_link' => 'Sign in here',
        'error_email' => 'Invalid email format.',
        'error_exists' => 'This email is already registered.',
        'success' => 'Registration complete! You can now <a href="login.php">sign in</a>.',
        'error_email_jp' => '',
        'error_exists_jp' => ''
    ],
    'jp' => [
        'app_name' => '給料トラッカー',
        'join' => 'アカウント作成',
        'name_ph' => '名前',
        'email_ph' => 'メールアドレス',
        'pass_ph' => 'パスワードを作成',
        'register' => '登録する',
        'have_account' => 'すでにアカウントをお持ちですか？',
        'login_link' => 'こちらからログイン',
        'error_email' => '無効なメールアドレスの形式です。',
        'error_exists' => 'このメールアドレスは既に登録されています。',
        'success' => '登録が完了しました！ <a href="login.php">ログイン</a>できます。'
    ]
];
$t = $trans[$lang];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $t['error_email'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = $t['error_exists'];
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword]);
            $success = $t['success'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['app_name'] ?> - <?= $t['register'] ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="../icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="../icon/apple-touch-icon.png">

    <style>
        :root {
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-blur: blur(20px);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.6);
            --accent: #43e97b;
            --accent-glow: rgba(67, 233, 123, 0.3);
        }

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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
            overflow-x: hidden;
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

        /* Floating orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(60px);
            opacity: 0.15;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            background: #43e97b;
            top: -80px;
            left: -80px;
            animation: orbFloat 8s ease-in-out infinite;
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            background: #fa709a;
            bottom: -60px;
            right: -60px;
            animation: orbFloat 10s ease-in-out infinite reverse;
        }

        .orb-3 {
            width: 150px;
            height: 150px;
            background: #4facfe;
            top: 40%;
            right: 20%;
            animation: orbFloat 7s ease-in-out infinite;
        }

        @keyframes orbFloat {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-30px) scale(1.05);
            }
        }

        .container {
            max-width: 420px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        /* Brand */
        .brand {
            text-align: center;
            margin-bottom: 28px;
            animation: fadeInDown 0.6s ease-out;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            border-radius: 18px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #0f0c29;
            box-shadow: 0 8px 30px rgba(67, 233, 123, 0.3);
        }

        .brand h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 26px;
            margin: 0;
        }

        .brand p {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Lang Toggle */
        .lang-toggle {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            gap: 4px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 3px;
        }

        .lang-btn {
            padding: 4px 10px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .lang-btn.active {
            background: rgba(67, 233, 123, 0.2);
            color: #43e97b;
        }

        /* Progress dots */
        .progress-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 20px;
            animation: fadeInDown 0.6s ease-out 0.1s backwards;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }

        .dot.active {
            background: var(--accent);
            box-shadow: 0 0 8px var(--accent-glow);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 var(--accent-glow);
            }

            50% {
                box-shadow: 0 0 0 6px transparent;
            }
        }

        /* Card */
        .auth-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 24px;
            padding: 28px 24px;
            animation: fadeInUp 0.5s ease-out 0.15s backwards;
        }

        /* Input */
        .input-group {
            margin-bottom: 14px;
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.35);
            font-size: 14px;
            z-index: 2;
            transition: color 0.3s;
        }

        .auth-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 16px 16px 16px 44px;
            color: var(--text-primary);
            font-size: 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s;
            -webkit-appearance: none;
        }

        .auth-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .auth-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            background: rgba(255, 255, 255, 0.1);
        }

        .auth-input:focus~.input-icon {
            color: var(--accent);
        }

        /* Password strength */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.1);
            margin-top: 6px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s;
        }

        /* Alerts */
        .alert-error {
            background: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b7a;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 14px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.4s ease-in-out;
        }

        .alert-success {
            background: rgba(67, 233, 123, 0.15);
            border: 1px solid rgba(67, 233, 123, 0.3);
            color: #43e97b;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 14px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: fadeInUp 0.3s ease-out;
        }

        .alert-success a {
            color: #4facfe;
            text-decoration: underline;
            font-weight: 600;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-6px);
            }

            75% {
                transform: translateX(6px);
            }
        }

        /* Submit */
        .submit-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: #0f0c29;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .submit-btn:active {
            transform: scale(0.97);
        }

        /* Link */
        .auth-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-secondary);
            animation: fadeInUp 0.5s ease-out 0.3s backwards;
        }

        .auth-link a {
            color: #43e97b;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px solid rgba(67, 233, 123, 0.3);
            padding-bottom: 1px;
        }

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
    </style>
</head>

<body>
    <!-- Floating Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="container">

        <!-- Language Toggle -->
        <div class="lang-toggle">
            <button class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>" onclick="setLang('en')">EN</button>
            <button class="lang-btn <?= $lang === 'jp' ? 'active' : '' ?>" onclick="setLang('jp')">JP</button>
        </div>

        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-user-plus"></i></div>
            <h1><?= $t['app_name'] ?></h1>
            <p><?= $t['join'] ?></p>
        </div>

        <!-- Progress Dots -->
        <div class="progress-dots">
            <div class="dot active"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

        <!-- Register Card -->
        <div class="auth-card">

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="input-group">
                    <input type="text" name="name" class="auth-input" placeholder="<?= $t['name_ph'] ?>" required
                        autocomplete="name">
                    <i class="fas fa-user input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="email" name="email" class="auth-input" placeholder="<?= $t['email_ph'] ?>" required
                        autocomplete="email">
                    <i class="fas fa-envelope input-icon"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" id="password" class="auth-input"
                        placeholder="<?= $t['pass_ph'] ?>" required autocomplete="new-password" minlength="6">
                    <i class="fas fa-lock input-icon"></i>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> <?= $t['register'] ?>
                </button>
            </form>
        </div>

        <!-- Login Link -->
        <div class="auth-link">
            <?= $t['have_account'] ?><br>
            <a href="login.php"><?= $t['login_link'] ?></a>
        </div>

    </div>

    <script>
        function setLang(lang) {
            document.cookie = 'lang=' + lang + ';path=/;max-age=31536000';
            location.reload();
        }

        // Password strength indicator
        const pw = document.getElementById('password');
        const fill = document.getElementById('strengthFill');

        pw.addEventListener('input', function () {
            const len = this.value.length;
            if (len >= 8) {
                fill.style.width = '100%';
                fill.style.background = '#43e97b';
            } else if (len >= 6) {
                fill.style.width = '66%';
                fill.style.background = '#ffd700';
            } else if (len > 0) {
                fill.style.width = '33%';
                fill.style.background = '#fa709a';
            } else {
                fill.style.width = '0%';
            }
        });
    </script>
</body>

</html>