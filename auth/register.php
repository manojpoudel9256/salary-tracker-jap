<?php
include '../config/db.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user inputs
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    // Check if email is valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "無効なメールアドレスの形式です。";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            $error = "このメールアドレスは既に登録されています。";
        } else {
            // Hash the password before saving it
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword]);

            $success = "登録が完了しました！ <a href='login.php'>ログイン</a>できます。";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>給料トラッカー - 登録</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        :root {
            --primary: #4361ee;
            --secondary: #7209b7;
            --accent: #f72585;
            --success: #06d6a0;
            --light: #f8f9fa;
            --dark: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(-45deg, #23d5ab, #23a6d5, #e73c7e, #ee7752);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            overflow-x: hidden;
        }

        @keyframes gradient {
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
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        /* Floating decorative circles */
        .float-circle {
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            pointer-events: none;
            z-index: 0;
        }

        .circle-1 {
            width: 150px;
            height: 150px;
            top: 10%;
            right: -50px;
            animation: float 8s ease-in-out infinite;
        }

        .circle-2 {
            width: 100px;
            height: 100px;
            bottom: 15%;
            left: -30px;
            animation: float 6s ease-in-out infinite reverse;
        }

        .circle-3 {
            width: 80px;
            height: 80px;
            top: 50%;
            right: 5%;
            animation: float 7s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        .register-title {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            font-size: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            line-height: 1.3;
        }

        .register-title .emoji {
            font-size: 30px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 8px;
        }

        .step {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.4);
            transition: all 0.3s;
        }

        .step.active {
            background-color: white;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }

            70% {
                box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }

        .card {
            border-radius: 25px;
            border: none;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }

        .card:active {
            transform: scale(0.99);
        }

        .card-body {
            padding: 30px 25px;
            position: relative;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 18px;
        }

        .input-wrapper i {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: var(--primary);
            transition: all 0.3s;
            font-size: 16px;
            z-index: 2;
        }

        .form-control {
            border-radius: 50px;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            min-height: 52px;
            width: 100%;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
            border-color: var(--primary);
            background-color: white;
            outline: none;
        }

        .form-control:focus~i {
            color: var(--accent);
            transform: translateY(-50%) scale(1.1);
        }

        .btn-register {
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            border: none;
            border-radius: 50px;
            padding: 16px 30px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            color: white;
            min-height: 52px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 8px;
        }

        .btn-register:active {
            transform: scale(0.97);
        }

        .btn-register:hover {
            background: linear-gradient(45deg, var(--primary), var(--accent));
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-size: 14px;
            position: relative;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.95);
            color: white;
            animation: shake 0.5s ease-in-out;
        }

        .alert-success {
            background-color: rgba(25, 135, 84, 0.95);
            color: white;
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-8px);
            }

            40% {
                transform: translateX(8px);
            }

            60% {
                transform: translateX(-8px);
            }

            80% {
                transform: translateX(8px);
            }

            100% {
                transform: translateX(0);
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(15px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert a {
            color: white;
            text-decoration: underline;
            font-weight: 700;
        }

        .login-link {
            color: white;
            text-align: center;
            margin-top: 20px;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            line-height: 1.6;
        }

        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            position: relative;
            padding: 0 5px;
            display: inline-block;
            border-bottom: 2px solid white;
            transition: all 0.3s;
        }

        .login-link a:active {
            transform: scale(0.95);
        }

        /* Focus visible for accessibility */
        .form-control:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }

        .btn-register:focus-visible {
            outline: 3px solid white;
            outline-offset: 3px;
        }

        /* Mobile specific optimizations */
        @media (max-width: 576px) {
            body {
                padding: 15px 10px;
            }

            .register-title {
                font-size: 22px;
                margin-bottom: 15px;
            }

            .register-title .emoji {
                font-size: 26px;
            }

            .progress-steps {
                margin-bottom: 15px;
                gap: 6px;
            }

            .step {
                width: 8px;
                height: 8px;
            }

            .card-body {
                padding: 25px 20px;
            }

            .form-control {
                font-size: 16px;
                /* Prevents iOS zoom on input focus */
                padding: 15px 18px 15px 48px;
            }

            .input-wrapper {
                margin-bottom: 16px;
            }

            .input-wrapper i {
                left: 16px;
                font-size: 15px;
            }

            .btn-register {
                font-size: 15px;
                padding: 15px 25px;
            }

            .login-link {
                font-size: 13px;
                margin-top: 18px;
            }

            .alert {
                font-size: 13px;
                padding: 12px 15px;
                margin-bottom: 15px;
            }
        }

        /* Tablet and up */
        @media (min-width: 577px) {
            .container {
                max-width: 480px;
            }

            .card {
                transition: transform 0.3s;
            }

            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            }

            .card:active {
                transform: translateY(-2px) scale(0.99);
            }

            .register-title {
                font-size: 28px;
                margin-bottom: 25px;
            }

            .card-body {
                padding: 35px 30px;
            }
        }

        /* Landscape mobile */
        @media (max-height: 650px) and (orientation: landscape) {
            body {
                padding: 10px;
            }

            .register-title {
                font-size: 20px;
                margin-bottom: 12px;
            }

            .progress-steps {
                margin-bottom: 12px;
            }

            .card-body {
                padding: 20px 20px;
            }

            .input-wrapper {
                margin-bottom: 14px;
            }

            .login-link {
                margin-top: 15px;
            }
        }
    </style>
</head>

<body>

    <!-- Floating decorative circles -->
    <div class="float-circle circle-1"></div>
    <div class="float-circle circle-2"></div>
    <div class="float-circle circle-3"></div>

    <div class="container">
        <h2 class="register-title">
            給与トラッカーに参加
            <span class="emoji">📊</span>
        </h2>

        <div class="progress-steps">
            <div class="step active"></div>
            <div class="step"></div>
            <div class="step"></div>
        </div>

        <!-- Registration Form -->
        <div class="card">
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="input-wrapper">
                        <input type="text" name="name" id="name" class="form-control" placeholder="名前" required
                            autocomplete="name">
                        <i class="fas fa-user"></i>
                    </div>

                    <div class="input-wrapper">
                        <input type="email" name="email" id="email" class="form-control" placeholder="メールアドレス" required
                            autocomplete="email">
                        <i class="fas fa-envelope"></i>
                    </div>

                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="パスワードを作成"
                            required autocomplete="new-password" minlength="6">
                        <i class="fas fa-lock"></i>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <span>アカウントを作成</span>
                    </button>
                </form>

                <div class="login-link">
                    すでにアカウントをお持ちですか？<br>
                    <a href="login.php">こちらからログイン</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Input focus animations
            const inputs = document.querySelectorAll('.form-control');

            inputs.forEach(input => {
                input.addEventListener('focus', function () {
                    const icon = this.nextElementSibling;
                    if (icon) {
                        icon.style.color = '#f72585';
                    }
                });

                input.addEventListener('blur', function () {
                    const icon = this.nextElementSibling;
                    if (icon && this.value === '') {
                        icon.style.color = '#4361ee';
                    }
                });
            });

            // Prevent double-tap zoom on buttons (iOS)
            const registerBtn = document.querySelector('.btn-register');
            let lastTouchEnd = 0;

            registerBtn.addEventListener('touchend', function (e) {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, false);

            // Optional: Form validation visual feedback
            const form = document.querySelector('form');
            form.addEventListener('submit', function (e) {
                const inputs = this.querySelectorAll('input');
                let valid = true;

                inputs.forEach(input => {
                    if (!input.validity.valid) {
                        valid = false;
                        input.style.borderColor = '#dc3545';

                        // Add shake animation to invalid inputs
                        input.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            input.style.animation = '';
                            input.style.borderColor = '#e0e0e0';
                        }, 500);
                    }
                });
            });

            // Password strength indicator (optional)
            const passwordInput = document.getElementById('password');
            passwordInput.addEventListener('input', function () {
                const password = this.value;
                const icon = this.nextElementSibling;

                if (password.length >= 8) {
                    icon.style.color = '#06d6a0'; // Strong
                } else if (password.length >= 6) {
                    icon.style.color = '#ffbe0b'; // Medium
                } else if (password.length > 0) {
                    icon.style.color = '#f72585'; // Weak
                } else {
                    icon.style.color = '#4361ee'; // Default
                }
            });
        });
    </script>
</body>

</html>