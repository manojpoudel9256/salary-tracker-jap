<?php
include 'auth/protected.php';
include 'config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store = trim(htmlspecialchars($_POST['store']));
    $amount = $_POST['amount'];
    $received_date = $_POST['received_date'];
    $working_month_input = $_POST['working_month']; // comes in format YYYY-MM
    $notes = htmlspecialchars($_POST['notes']);

    // Convert to full date format (YYYY-MM-01)
    $working_month = $working_month_input . "-01";

    // Validation
    if (empty($store)) {
        $message = "店舗名を入力してください。";
    } elseif (strlen($store) > 100) {
        $message = "店舗名は100文字以内で入力してください。";
    } elseif ($amount <= 0) {
        $message = "金額は正の数でなければなりません。";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO salaries (user_id, store_name, amount, received_date, working_month, notes)
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $store, $amount, $received_date, $working_month, $notes]);

            header('Location: view_salaries.php');
            exit;
        } catch (Exception $e) {
            $message = "給与追加エラー: " . $e->getMessage();
        }
    }
}

// Get all unique store names with count for the current user
try {
    $stmt = $pdo->prepare("SELECT store_name, COUNT(*) as count FROM salaries WHERE user_id = ? GROUP BY store_name ORDER BY store_name ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $recorded_stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recorded_stores = [];
}
?>

<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>給与記録の追加</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #06d6a0;
            --warning: #ffbe0b;
            --info: #4cc9f0;
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
            background: linear-gradient(125deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
        
        .container {
            max-width: 800px;
            width: 100%;
            margin: 40px auto;
            position: relative;
            z-index: 1;
        }
        
        .floating-circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.4;
            filter: blur(5px);
            z-index: -1;
            animation: floatingAnimation 10s infinite ease-in-out;
        }
        
        .circle-1 {
            width: 150px;
            height: 150px;
            background: var(--accent);
            top: -75px;
            right: 100px;
            animation-delay: 0s;
        }
        
        .circle-2 {
            width: 200px;
            height: 200px;
            background: var(--primary);
            bottom: -100px;
            left: -50px;
            animation-delay: 2s;
        }
        
        .circle-3 {
            width: 100px;
            height: 100px;
            background: var(--warning);
            top: 50%;
            right: -50px;
            animation-delay: 4s;
        }
        
        @keyframes floatingAnimation {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(10deg);
            }
        }
        
        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            display: inline-block;
            animation: titleAnimation 1s ease-out;
        }
        
        h2::after {
            content: '💰';
            position: absolute;
            margin-left: 15px;
            animation: bounceMoney 2s infinite ease-in-out;
        }
        
        @keyframes titleAnimation {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounceMoney {
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
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: cardEntrance 1s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-style: preserve-3d;
            transition: all 0.5s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            position: relative;
            display: inline-block;
            margin-left: 0;
            margin-bottom: 8px;
            transition: all 0.3s;
            opacity: 0;
            animation: labelEntrance 0.5s forwards ease-out;
            animation-delay: calc(var(--i) * 0.1s);
        }
        
        @keyframes labelEntrance {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: calc(50% + 10px);
            transform: translateY(-50%);
            color: var(--primary);
            transition: all 0.3s;
            z-index: 2;
        }
        
        .form-control, .form-select {
            border-radius: 50px;
            padding: 12px 20px 12px 45px;
            border: 2px solid transparent;
            background-color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            font-size: 16px;
            opacity: 0;
            animation: inputEntrance 0.5s forwards ease-out;
            animation-delay: calc((var(--i) * 0.1s) + 0.2s);
        }
        
        @keyframes inputEntrance {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.25);
            background-color: white;
        }
        
        .form-control:focus + .input-icon, 
        .form-select:focus + .input-icon {
            color: var(--accent);
            transform: translateY(-50%) scale(1.2);
        }
        
        textarea.form-control {
            border-radius: 20px;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group .input-icon {
            top: 50%;
        }

        .form-group.textarea-group .input-icon {
            top: 25px;
        }
        
        /* Recorded stores section */
        .recorded-stores-section {
            margin-top: 15px;
            padding: 15px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 15px;
            border: 2px dashed rgba(67, 97, 238, 0.2);
        }
        
        .recorded-stores-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .recorded-stores-title i {
            font-size: 16px;
        }
        
        .store-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .store-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 15px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
            animation: tagEntrance 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        .store-tag:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.5);
            background: linear-gradient(135deg, var(--accent), var(--primary));
        }
        
        .store-tag:active {
            transform: translateY(0) scale(0.98);
        }
        
        @keyframes tagEntrance {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .store-tag-count {
            background: rgba(255, 255, 255, 0.3);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .no-stores-message {
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            font-style: italic;
            padding: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
            position: relative;
            overflow: hidden;
            animation: buttonPulse 2s infinite;
        }
        
        @keyframes buttonPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(67, 97, 238, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
            }
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: all 0.6s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            background: linear-gradient(45deg, var(--secondary), var(--accent));
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4);
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:active {
            transform: scale(0.95);
        }
        
        .alert {
            border-radius: 15px;
            padding: 15px;
            border: none;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            animation: alertAnimation 0.5s ease-out;
        }
        
        @keyframes alertAnimation {
            0% {
                opacity:.1;
                transform: scaleY(0.1);
                transform-origin: top;
            }
            100% {
                opacity: 1;
                transform: scaleY(1);
                transform-origin: top;
            }
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
        }
        
        .alert-success {
            background-color: rgba(25, 135, 84, 0.9);
            color: white;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            position: relative;
            margin-top: 20px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        
        .back-link:hover {
            transform: translateX(-5px);
            color: white;
        }
        
        .back-link i {
            margin-right: 8px;
            transition: transform 0.3s;
        }
        
        .back-link:hover i {
            transform: translateX(-5px);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 576px) {
            .container {
                margin: 20px auto;
                padding: 0 10px;
            }
            
            .card {
                padding: 20px !important;
            }
            
            h2 {
                font-size: 24px;
            }
            
            .form-control, .form-select {
                font-size: 14px;
                padding: 10px 15px 10px 40px;
            }
            
            .input-icon {
                left: 12px;
                font-size: 14px;
            }
            
            .form-label {
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 10px 20px;
                font-size: 14px;
                width: 100%;
            }
            
            .floating-circle {
                display: none;
            }
            
            .store-tag {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
        
        /* Floating particles */
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
            opacity: 0.6;
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
        
        /* Currency symbol animation */
        .currency-symbol {
            position: absolute;
            font-size: 24px;
            color: rgba(255, 255, 255, 0.7);
            animation: floatCurrency 8s infinite linear;
            opacity: 0;
        }
        
        @keyframes floatCurrency {
            0% {
                transform: translateY(100px);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateY(-200px) rotate(45deg);
                opacity: 0;
            }
        }

        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            margin-left: 15px;
        }
    </style>
</head>
<body>

<!-- Floating decorative elements -->
<div class="particles" id="particles"></div>
<div class="currency-symbols" id="currency-symbols"></div>

<div class="container">
    <!-- Decorative circles -->
    <div class="floating-circle circle-1"></div>
    <div class="floating-circle circle-2"></div>
    <div class="floating-circle circle-3"></div>
    
    <h2 class="mb-4 w-100 text-center">給与記録の追加</h2>

    <?php if (!empty($message)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $message ?>
    </div>
    <?php endif; ?>

    <form method="post" class="card shadow-lg p-4 p-md-5">
        <div class="form-group" style="--i: 1">
            <label for="store" class="form-label w-100">店舗名</label>
            <div class="position-relative">
                <input type="text" 
                       name="store" 
                       id="store" 
                       class="form-control" 
                       placeholder="例: セブンイレブン、ファミリーマート" 
                       maxlength="100"
                       required 
                       autocomplete="off">
                <i class="fas fa-store input-icon"></i>
            </div>
            <small class="helper-text">勤務先の店舗名を入力してください（下のタグから選択も可能）</small>
            
            <!-- Recorded Stores Section -->
            <?php if (!empty($recorded_stores)): ?>
            <div class="recorded-stores-section">
                <div class="recorded-stores-title">
                    <i class="fas fa-history"></i>
                    記録済みの店舗（クリックで選択）
                </div>
                <div class="store-tags">
                    <?php foreach ($recorded_stores as $index => $store): ?>
                    <div class="store-tag" 
                         data-store="<?= htmlspecialchars($store['store_name']); ?>"
                         style="animation-delay: <?= $index * 0.05; ?>s">
                        <span><?= htmlspecialchars($store['store_name']); ?></span>
                        <span class="store-tag-count"><?= $store['count']; ?>件</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="recorded-stores-section">
                <div class="no-stores-message">
                    <i class="fas fa-info-circle"></i> まだ記録された店舗はありません
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-group" style="--i: 2">
            <label for="amount" class="form-label w-100">金額</label>
            <div class="position-relative">
                <input type="number" name="amount" id="amount" class="form-control" placeholder="例: 150000" required>
                <i class="fas fa-yen-sign input-icon"></i>
            </div>
        </div>

        <div class="form-group" style="--i: 3">
            <label for="received_date" class="form-label w-100">受け取り日</label>
            <div class="position-relative">
                <input type="date" name="received_date" id="received_date" class="form-control" required>
                <i class="fas fa-calendar-alt input-icon"></i>
            </div>
        </div>

        <div class="form-group" style="--i: 4">
            <label for="working_month" class="form-label w-100">勤務月</label>
            <div class="position-relative">
                <input type="month" name="working_month" id="working_month" class="form-control" required>
                <i class="fas fa-calendar-check input-icon"></i>
            </div>
        </div>

        <div class="form-group textarea-group" style="--i: 5">
            <label for="notes" class="form-label w-100">メモ（任意）</label>
            <div class="position-relative">
                <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="追加情報やメモを入力"></textarea>
                <i class="fas fa-sticky-note input-icon"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3" id="submitBtn">
            <span>給与記録を送信</span>
        </button>
    </form>

    <div class="text-center">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const storeInput = document.getElementById('store');
        const storeTags = document.querySelectorAll('.store-tag');
        
        // Click on store tag to fill input
        storeTags.forEach(tag => {
            tag.addEventListener('click', function() {
                const storeName = this.getAttribute('data-store');
                storeInput.value = storeName;
                
                // Highlight effect
                this.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
                
                // Focus on next field (amount)
                document.getElementById('amount').focus();
                
                // Scroll to input if needed
                storeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
        
        // Create floating particles
        const particlesContainer = document.getElementById('particles');
        const colors = ['#4361ee', '#3a0ca3', '#f72585', '#4cc9f0', '#ffbe0b'];
        
        for (let i = 0; i < 20; i++) {
            createParticle(particlesContainer, colors);
        }
        
        // Create floating currency symbols
        createCurrencySymbols();
        
        // Input animation
        const inputs = document.querySelectorAll('.form-control, .form-select');
        
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const icon = this.closest('.position-relative').querySelector('.input-icon');
                if (icon) {
                    icon.style.color = '#f72585';
                    animateIcon(icon);
                }
            });
            
            input.addEventListener('blur', function() {
                if (this.value === '') {
                    const icon = this.closest('.position-relative').querySelector('.input-icon');
                    if (icon) {
                        icon.style.color = '#4361ee';
                    }
                }
            });
        });
        
        // Button ripple effect
        const submitBtn = document.getElementById('submitBtn');
        
        submitBtn.addEventListener('mousedown', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.position = 'absolute';
            ripple.style.width = '100px';
            ripple.style.height = '100px';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.4)';
            ripple.style.transform = 'translate(-50%, -50%)';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.animation = 'ripple 0.6s linear';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Animation for input icons
    function animateIcon(icon) {
        icon.style.animation = 'bounce 0.5s ease';
        setTimeout(() => {
            icon.style.animation = '';
        }, 500);
    }
    
    // Create particles
    function createParticle(container, colors) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        // Random properties
        const size = Math.random() * 8 + 4;
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
    
    // Create currency symbols
    function createCurrencySymbols() {
        const symbols = ['$', '€', '£', '¥', '₹', '฿', '₩'];
        const container = document.getElementById('currency-symbols');
        
        setInterval(() => {
            const symbol = document.createElement('div');
            symbol.classList.add('currency-symbol');
            symbol.textContent = symbols[Math.floor(Math.random() * symbols.length)];
            
            const left = Math.random() * 100;
            symbol.style.left = `${left}%`;
            symbol.style.animationDuration = `${Math.random() * 3 + 5}s`;
            
            container.appendChild(symbol);
            
            setTimeout(() => {
                symbol.remove();
            }, 8000);
        }, 800);
    }
    
    // Add keyframe animation for ripple effect
    const style = document.createElement('style');
    style.textContent = `
    @keyframes ripple {
        0% {
            transform: translate(-50%, -50%) scale(0);
            opacity: 1;
        }
        100% {
            transform: translate(-50%, -50%) scale(3);
            opacity: 0;
        }
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: translateY(-50%);
        }
        50% {
            transform: translateY(-70%);
        }
    }`;
    document.head.appendChild(style);
</script>
</body>
</html>