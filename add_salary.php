<?php
include 'auth/protected.php';
include 'config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store = trim(htmlspecialchars($_POST['store']));
    $amount = $_POST['amount'];
    $received_date = $_POST['received_date'];
    $working_month_input = $_POST['working_month'];
    $notes = htmlspecialchars($_POST['notes']);
    $working_month = $working_month_input . "-01";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>給与記録の追加</title>
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
            padding: 15px 10px;
        }
        
        @keyframes gradientBG {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 10px;
        }
        
        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            font-size: clamp(20px, 5vw, 28px);
        }
        
        .card {
            border-radius: 15px;
            border: none;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
            font-size: clamp(13px, 3vw, 15px);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #4361ee;
            transition: all 0.3s;
            z-index: 2;
            pointer-events: none;
        }
        
        .form-control, .form-select {
            border-radius: 25px;
            padding: 12px 15px 12px 45px;
            border: 2px solid transparent;
            background-color: rgba(255,255,255,0.9);
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            font-size: 16px;
            min-height: 44px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67,97,238,0.2);
            background-color: white;
        }
        
        textarea.form-control {
            border-radius: 15px;
            resize: vertical;
            min-height: 80px;
        }
        
        .helper-text {
            font-size: clamp(11px, 2.5vw, 12px);
            color: #6c757d;
            margin-top: 5px;
            margin-left: 10px;
        }
        
        .recorded-stores-section {
            margin-top: 12px;
            padding: 12px;
            background: rgba(67,97,238,0.05);
            border-radius: 12px;
            border: 2px dashed rgba(67,97,238,0.2);
        }
        
        .recorded-stores-title {
            font-size: clamp(12px, 3vw, 14px);
            font-weight: 600;
            color: #4361ee;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
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
            padding: 8px 14px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 20px;
            font-size: clamp(11px, 2.8vw, 13px);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 8px rgba(67,97,238,0.3);
            min-height: 36px;
        }
        
        .store-tag:active {
            transform: scale(0.95);
        }
        
        .store-tag-count {
            background: rgba(255,255,255,0.3);
            padding: 2px 7px;
            border-radius: 8px;
            font-size: clamp(10px, 2.5vw, 11px);
            font-weight: 600;
        }
        
        .no-stores-message {
            text-align: center;
            color: #6c757d;
            font-size: clamp(12px, 3vw, 13px);
            padding: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4361ee, #3a0ca3);
            border: none;
            border-radius: 25px;
            padding: 14px 30px;
            font-weight: 600;
            font-size: clamp(14px, 3.5vw, 16px);
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(67,97,238,0.3);
            width: 100%;
            min-height: 48px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(67,97,238,0.4);
        }
        
        .btn-primary:active {
            transform: scale(0.98);
        }
        
        .alert {
            border-radius: 12px;
            padding: 12px 15px;
            border: none;
            margin-bottom: 20px;
            font-size: clamp(13px, 3vw, 14px);
        }
        
        .alert-danger {
            background-color: rgba(220,53,69,0.9);
            color: white;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            font-size: clamp(13px, 3vw, 15px);
            min-height: 44px;
        }
        
        .back-link:hover {
            transform: translateX(-3px);
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        @media (min-width: 768px) {
            body { padding: 20px; }
            .container { margin: 40px auto; }
            .card { padding: 30px; }
            h2 { margin-bottom: 30px; }
            .store-tag:hover {
                transform: translateY(-2px) scale(1.05);
                box-shadow: 0 5px 15px rgba(67,97,238,0.5);
            }
            .btn-primary { width: auto; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-plus-circle"></i> 給与記録の追加</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card">
        <div class="form-group">
            <label for="store" class="form-label">店舗名</label>
            <div class="position-relative">
                <input type="text" name="store" id="store" class="form-control" 
                       placeholder="例: セブンイレブン" maxlength="100" required autocomplete="off">
                <i class="fas fa-store input-icon"></i>
            </div>
            <small class="helper-text">勤務先の店舗名（下のタグから選択可能）</small>
            
            <?php if (!empty($recorded_stores)): ?>
                <div class="recorded-stores-section">
                    <div class="recorded-stores-title">
                        <i class="fas fa-history"></i>
                        記録済み店舗
                    </div>
                    <div class="store-tags">
                        <?php foreach ($recorded_stores as $store): ?>
                            <div class="store-tag" data-store="<?= htmlspecialchars($store['store_name']); ?>">
                                <span><?= htmlspecialchars($store['store_name']); ?></span>
                                <span class="store-tag-count"><?= $store['count']; ?></span>
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

        <div class="form-group">
            <label for="amount" class="form-label">金額</label>
            <div class="position-relative">
                <input type="number" name="amount" id="amount" class="form-control" 
                       placeholder="例: 150000" required>
                <i class="fas fa-yen-sign input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="received_date" class="form-label">受け取り日</label>
            <div class="position-relative">
                <input type="date" name="received_date" id="received_date" class="form-control" required>
                <i class="fas fa-calendar-alt input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="working_month" class="form-label">勤務月</label>
            <div class="position-relative">
                <input type="month" name="working_month" id="working_month" class="form-control" required>
                <i class="fas fa-calendar-check input-icon"></i>
            </div>
        </div>

        <div class="form-group">
            <label for="notes" class="form-label">メモ（任意）</label>
            <div class="position-relative">
                <textarea name="notes" id="notes" rows="3" class="form-control" 
                          placeholder="追加情報やメモを入力"></textarea>
                <i class="fas fa-sticky-note input-icon" style="top: 25px;"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            <i class="fas fa-paper-plane me-2"></i>給与記録を送信
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const storeInput = document.getElementById('store');
    const storeTags = document.querySelectorAll('.store-tag');
    
    storeTags.forEach(tag => {
        tag.addEventListener('click', function() {
            const storeName = this.getAttribute('data-store');
            storeInput.value = storeName;
            this.style.transform = 'scale(1.1)';
            setTimeout(() => this.style.transform = '', 200);
            document.getElementById('amount').focus();
        });
    });
    
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            const icon = this.previousElementSibling;
            if (icon && icon.classList.contains('input-icon')) {
                icon.style.color = '#f72585';
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.value === '') {
                const icon = this.previousElementSibling;
                if (icon && icon.classList.contains('input-icon')) {
                    icon.style.color = '#4361ee';
                }
            }
        });
    });
});
</script>
</body>
</html>