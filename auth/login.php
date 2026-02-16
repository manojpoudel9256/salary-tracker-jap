<?php
session_start();
include '../config/db.php';

$error = '';

// Handle login on form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input to prevent XSS
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    // Fetch user from DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        
        // Redirect after successful login
        header("Location: ../dashboard.php");
        exit;
    } else {
        $error = "メールアドレスまたはパスワードが間違っています。";
    }
}
?>

<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>給料トラッカー - ログイン</title>
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
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
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
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .container {
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .login-title {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 25px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-title .emoji {
            font-size: 32px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
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
            padding: 35px 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
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
        
        .form-control:focus ~ i {
            color: var(--accent);
            transform: translateY(-50%) scale(1.1);
        }
        
        .btn-login {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
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
            margin-top: 10px;
        }
        
        .btn-login:active {
            transform: scale(0.97);
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            box-shadow: 0 8px 20px rgba(114, 9, 183, 0.3);
        }
        
        .alert {
            border-radius: 15px;
            animation: shake 0.5s ease-in-out;
            border: none;
            background-color: rgba(220, 53, 69, 0.95);
            color: white;
            padding: 14px 18px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-8px); }
            80% { transform: translateX(8px); }
            100% { transform: translateX(0); }
        }
        
        .register-link {
            color: white;
            text-align: center;
            margin-top: 25px;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .register-link a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            position: relative;
            padding: 0 5px;
            display: inline-block;
            border-bottom: 2px solid white;
            transition: all 0.3s;
        }
        
        .register-link a:active {
            transform: scale(0.95);
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
            top: 60%;
            right: 10%;
            animation: float 7s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Focus visible for accessibility */
        .form-control:focus-visible {
            outline: 3px solid var(--primary);
            outline-offset: 2px;
        }
        
        .btn-login:focus-visible {
            outline: 3px solid white;
            outline-offset: 3px;
        }
        
        /* Mobile specific optimizations */
        @media (max-width: 576px) {
            body {
                padding: 15px 10px;
            }
            
            .login-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .login-title .emoji {
                font-size: 28px;
            }
            
            .card-body {
                padding: 30px 20px;
            }
            
            .form-control {
                font-size: 16px; /* Prevents iOS zoom on input focus */
                padding: 15px 18px 15px 48px;
            }
            
            .input-wrapper i {
                left: 16px;
                font-size: 15px;
            }
            
            .btn-login {
                font-size: 15px;
                padding: 15px 25px;
            }
            
            .register-link {
                font-size: 13px;
                margin-top: 20px;
            }
            
            .alert {
                font-size: 13px;
                padding: 12px 15px;
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
        }
        
        /* Landscape mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 10px;
            }
            
            .login-title {
                font-size: 20px;
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 20px 20px;
            }
            
            .input-wrapper {
                margin-bottom: 15px;
            }
            
            .register-link {
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
    <h2 class="login-title">
        給料トラッカー
        <span class="emoji">💰</span>
    </h2>

    <!-- Login Form -->
    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control" 
                        placeholder="メールアドレス" 
                        required
                        autocomplete="email"
                    >
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="input-wrapper">
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control" 
                        placeholder="パスワード" 
                        required
                        autocomplete="current-password"
                    >
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn btn-login">
                    <span>ログイン</span>
                </button>
            </form>

            <!-- Display error message if login fails -->
            <?php if (!empty($error)): ?>
                <div class="alert" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="register-link">
        アカウントをお持ちでない方はこちら<br>
        <a href="register.php">新規登録はこちら</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Input focus animations
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const icon = this.nextElementSibling;
                if (icon) {
                    icon.style.color = '#f72585';
                }
            });
            
            input.addEventListener('blur', function() {
                const icon = this.nextElementSibling;
                if (icon && this.value === '') {
                    icon.style.color = '#4361ee';
                }
            });
        });
        
        // Prevent double-tap zoom on buttons (iOS)
        const loginBtn = document.querySelector('.btn-login');
        let lastTouchEnd = 0;
        
        loginBtn.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    });
</script>
</body>
</html>