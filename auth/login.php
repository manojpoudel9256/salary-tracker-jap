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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            max-width: 500px;
            width: 100%;
            perspective: 1000px;
            position: relative;
            z-index: 1;
        }
        
        .container::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
            z-index: -1;
        }
        
        .container::after {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            bottom: -75px;
            left: -75px;
            animation: float 6s ease-in-out infinite reverse;
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
        
        .login-title {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            animation: pulse 2s infinite;
            position: relative;
        }
        
        .login-title::after {
            content: '💰';
            position: absolute;
            margin-left: 10px;
            animation: bounce 1s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .card {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            animation: cardEntrance 1s ease-out;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transform-style: preserve-3d;
        }
        
        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: rotateY(-10deg) translateY(30px);
            }
            to {
                opacity: 1;
                transform: rotateY(0) translateY(0);
            }
        }
        
        .card:hover {
            transform: translateY(-10px) rotateX(2deg) rotateY(-2deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
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
            transition: all 0.6s;
            transform: rotate(-45deg) translateX(-150%);
            z-index: 0;
        }
        
        .card:hover .card-body::before {
            transform: rotate(-45deg) translateX(150%);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            transition: all 0.3s;
            position: relative;
        }
        
        .form-label::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
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
        }
        
        .form-control {
            border-radius: 50px;
            padding: 15px 20px 15px 45px;
            border: 2px solid #dee2e6;
            transition: all 0.3s;
            background-color: rgba(255, 255, 255, 0.8);
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
        
        .btn-login {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 1px;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.6s;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(114, 9, 183, 0.4);
            background: linear-gradient(45deg, var(--secondary), var(--accent));
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:active {
            transform: scale(0.95);
        }
        
        .alert {
            border-radius: 10px;
            animation: shake 0.5s ease-in-out;
            border: none;
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 15px;
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
        }
        
        .register-link {
            color: white;
            text-align: center;
            margin-top: 20px;
            font-weight: 500;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            position: relative;
            display: inline-block;
        }
        
        .register-link a {
            color: white;
            text-decoration: none;
            font-weight: 700;
            position: relative;
            padding: 0 5px;
        }
        
        .register-link a::after {
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
        
        .register-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }
        
        /* Particle animations */
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
<h2 class="login-title">給料トラッカー</h2>

    <!-- Login Form -->
    <div class="card shadow-lg">
        <div class="card-body">
            <form method="post">
                <div class="input-wrapper">
                    <input type="email" name="email" id="email" class="form-control" placeholder="メールアドレス" required>
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="input-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="パスワード" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <span>ログイン</span>
                </button>
            </form>

            <!-- Display error message if login fails -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="register-link text-center mt-4">
        <span>アカウントをお持ちでない方はこちら</span> <a href="register.php">新規登録はこちら</a>
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
        const loginBtn = document.querySelector('.btn-login');
        
        loginBtn.addEventListener('click', function(e) {
            let x = e.clientX - e.target.offsetLeft;
            let y = e.clientY - e.target.offsetTop;
            
            let ripple = document.createElement('span');
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            this.appendChild(ripple);
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
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