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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            padding: 20px;
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
            max-width: 550px;
            width: 100%;
            perspective: 1000px;
            position: relative;
            z-index: 1;
        }
        
        .container::before {
            content: '';
            position: absolute;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            top: -80px;
            right: -130px;
            animation: float 10s ease-in-out infinite;
            z-index: -1;
        }
        
        .container::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            bottom: -90px;
            left: -90px;
            animation: float 8s ease-in-out infinite reverse;
            z-index: -1;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }
        
        .register-title {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            animation: slideInDown 1s ease-out;
            position: relative;
        }
        
        .register-title::after {
            content: '📊';
            position: absolute;
            margin-left: 10px;
            animation: floatEmoji 3s infinite;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes floatEmoji {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-10px) rotate(10deg);
            }
        }
        
        .card {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: cardEntrance 1.2s ease-out;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transform-style: preserve-3d;
        }
        
        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: rotateY(10deg) translateY(50px);
            }
            to {
                opacity: 1;
                transform: rotateY(0) translateY(0);
            }
        }
        
        .card:hover {
            transform: translateY(-10px) rotateX(3deg) rotateY(3deg);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .card-body {
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .card-body::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            width: 120%;
            height: 120%;
            background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.4) 50%, rgba(255,255,255,0.1) 100%);
            transition: all 0.8s;
            transform: rotate(-45deg) translateX(-180%);
            z-index: 0;
        }
        
        .card:hover .card-body::before {
            transform: rotate(-45deg) translateX(180%);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            transition: all 0.3s;
            position: relative;
            display: inline-block;
        }
        
        .form-label::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }
        
        .form-control:focus + .form-label::after {
            width: 100%;
        }
        
        .input-wrapper {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-wrapper i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--primary);
            transition: all 0.3s;
            z-index: 1;
        }
        
        .form-control {
            border-radius: 50px;
            padding: 15px 20px 15px 45px;
            border: 2px solid #dee2e6;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.25);
            border-color: var(--primary);
            background-color: white;
        }
        
        .form-control:focus + i {
            color: var(--accent);
            transform: translateY(-50%) scale(1.2);
        }
        
        .btn-register {
            background: linear-gradient(45deg, var(--secondary), var(--primary));
            border: none;
            border-radius: 50px;
            padding: 14px 30px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 1px;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.6s;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.4);
            background: linear-gradient(45deg, var(--primary), var(--accent));
        }
        
        .btn-register:hover::before {
            left: 100%;
        }
        
        .btn-register:active {
            transform: scale(0.95);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
            animation: shake 0.5s ease-in-out;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.9);
            color: white;
            animation: slideInUp 0.5s ease-out;
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(20px);
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
            font-weight: 600;
        }
        
        .login-link {
            color: white;
            text-align: center;
            margin-top: 20px;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            position: relative;
            display: inline-block;
        }
        
        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            position: relative;
            padding: 0 5px;
        }
        
        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: white;
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }
        
        .login-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }
        
        /* Progress indicators for registration steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            position: relative;
            z-index: 1;
        }
        
        .step.active {
            background-color: white;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }
        
        /* Particles animation */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            animation: particleMove 15s infinite linear;
            opacity: 0.5;
        }
        
        @keyframes particleMove {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-800px) translateX(100px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<!-- Particles animation -->
<div class="particles" id="particles"></div>

<div class="container">
    <h2 class="register-title">給与トラッカーに参加する</h2>
    
    <div class="progress-steps">
        <div class="step active"></div>
        <div class="step"></div>
        <div class="step"></div>
    </div>

    <!-- Registration Form -->
    <div class="card shadow-lg">
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
                    <input type="text" name="name" id="name" class="form-control" placeholder="名前" required>
                    <i class="fas fa-user"></i>
                </div>

                <div class="input-wrapper">
                    <input type="email" name="email" id="email" class="form-control" placeholder="メールアドレス" required>
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="パスワードを作成" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn btn-register w-100">
                    <span>アカウントを作成</span>
                </button>
            </form>

            <div class="text-center mt-4">
                <div class="login-link">
                    <span>すでにアカウントをお持ちですか？</span> <a href="login.php">こちらからログイン</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script>
    // Create floating particles
    document.addEventListener('DOMContentLoaded', function() {
        const particlesContainer = document.getElementById('particles');
        const colors = ['#4361ee', '#3a0ca3', '#f72585', '#4cc9f0', '#7209b7'];
        
        for (let i = 0; i < 30; i++) {
            createParticle(particlesContainer, colors);
        }
        
        // Input animations
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            // Add label animation
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#f72585';
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    this.parentElement.querySelector('i').style.color = '#4361ee';
                }
            });
        });
        
        // Add button ripple effect
        const registerBtn = document.querySelector('.btn-register');
        
        registerBtn.addEventListener('mousedown', function(e) {
            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;
            
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Optional: Form validation visual feedback
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input');
            let valid = true;
            
            inputs.forEach(input => {
                if (!input.validity.valid) {
                    valid = false;
                    input.classList.add('is-invalid');
                    
                    // Add shake animation to invalid inputs
                    input.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        input.style.animation = '';
                    }, 500);
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    
    function createParticle(container, colors) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random properties
        const size = Math.random() * 10 + 5;
        const color = colors[Math.floor(Math.random() * colors.length)];
        const left = Math.random() * 100;
        const delay = Math.random() * 10;
        const duration = Math.random() * 10 + 10;
        
        // Apply styles
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.backgroundColor = color;
        particle.style.left = `${left}%`;
        particle.style.bottom = '-20px';
        particle.style.animationDuration = `${duration}s`;
        particle.style.animationDelay = `${delay}s`;
        
        container.appendChild(particle);
    }
</script>
</body>
</html>