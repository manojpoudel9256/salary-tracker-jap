<?php
include 'auth/protected.php';
include 'config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $date_sent = $_POST['date_sent'];
    $recipient = trim(htmlspecialchars($_POST['recipient']));
    $purpose = htmlspecialchars($_POST['purpose']);

    if ($amount <= 0) {
        $message = "金額は正の数でなければなりません。";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO remittances (user_id, amount, date_sent, recipient, purpose)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $date_sent, $recipient, $purpose]);
            header('Location: view_remittance.php');
            exit;
        } catch (Exception $e) {
            $message = "送金記録追加エラー: " . $e->getMessage();
        }
    }
}

// Get all unique recipients for the current user
try {
    $stmt = $pdo->prepare("SELECT recipient, COUNT(*) as count FROM remittances WHERE user_id = ? AND recipient != '' GROUP BY recipient ORDER BY count DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $recorded_recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recorded_recipients = [];
}
?>
<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>送金記録の追加</title>
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

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
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
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            font-size: clamp(20px, 5vw, 28px);
        }

        .card {
            border-radius: 15px;
            border: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
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
            color: #ffbe0b;
            transition: all 0.3s;
            z-index: 2;
            pointer-events: none;
        }

        .form-control,
        .form-select {
            border-radius: 25px;
            padding: 12px 15px 12px 45px;
            border: 2px solid transparent;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            font-size: 16px;
            min-height: 44px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #ffbe0b;
            box-shadow: 0 0 0 3px rgba(255, 190, 11, 0.2);
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

        .recorded-recipients-section {
            margin-top: 12px;
            padding: 12px;
            background: rgba(255, 190, 11, 0.05);
            border-radius: 12px;
            border: 2px dashed rgba(255, 190, 11, 0.2);
        }

        .recorded-recipients-title {
            font-size: clamp(12px, 3vw, 14px);
            font-weight: 600;
            color: #ffbe0b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .recipient-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .recipient-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #ffbe0b, #ffa500);
            color: white;
            border-radius: 20px;
            font-size: clamp(11px, 2.8vw, 13px);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 3px 8px rgba(255, 190, 11, 0.3);
            min-height: 36px;
        }

        .recipient-tag:active {
            transform: scale(0.95);
        }

        .recipient-tag-count {
            background: rgba(255, 255, 255, 0.3);
            padding: 2px 7px;
            border-radius: 8px;
            font-size: clamp(10px, 2.5vw, 11px);
            font-weight: 600;
        }

        .no-recipients-message {
            text-align: center;
            color: #6c757d;
            font-size: clamp(12px, 3vw, 13px);
            padding: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffbe0b, #ffa500);
            border: none;
            border-radius: 25px;
            padding: 14px 30px;
            font-weight: 600;
            font-size: clamp(14px, 3.5vw, 16px);
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(255, 190, 11, 0.3);
            width: 100%;
            min-height: 48px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 190, 11, 0.4);
            background: linear-gradient(45deg, #ffa500, #ff8c00);
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
            background-color: rgba(220, 53, 69, 0.9);
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
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            transition: all 0.3s;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-size: clamp(13px, 3vw, 15px);
            min-height: 44px;
        }

        .back-link:hover {
            transform: translateX(-3px);
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        @media (min-width: 768px) {
            body {
                padding: 20px;
            }

            .container {
                margin: 40px auto;
            }

            .card {
                padding: 30px;
            }

            h2 {
                margin-bottom: 30px;
            }

            .recipient-tag:hover {
                transform: translateY(-2px) scale(1.05);
                box-shadow: 0 5px 15px rgba(255, 190, 11, 0.5);
            }

            .btn-primary {
                width: auto;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2><i class="fas fa-paper-plane"></i> 送金記録の追加</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post" class="card">
            <div class="form-group">
                <label for="amount" class="form-label">送金額 (Amount)</label>
                <div class="position-relative">
                    <input type="number" name="amount" id="amount" class="form-control" placeholder="例: 50000" required>
                    <i class="fas fa-yen-sign input-icon"></i>
                </div>
                <small class="helper-text">母国へ送った金額を入力してください</small>
            </div>

            <div class="form-group">
                <label for="date_sent" class="form-label">送金日 (Date Sent)</label>
                <div class="position-relative">
                    <input type="date" name="date_sent" id="date_sent" class="form-control" required>
                    <i class="fas fa-calendar-alt input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="recipient" class="form-label">受取人 (Recipient) - 任意</label>
                <div class="position-relative">
                    <input type="text" name="recipient" id="recipient" class="form-control" placeholder="例: 母、家族、兄弟"
                        maxlength="100" autocomplete="off">
                    <i class="fas fa-user input-icon"></i>
                </div>
                <small class="helper-text">送金先の人を入力（省略可）</small>

                <?php if (!empty($recorded_recipients)): ?>
                    <div class="recorded-recipients-section">
                        <div class="recorded-recipients-title">
                            <i class="fas fa-history"></i>
                            過去の受取人
                        </div>
                        <div class="recipient-tags">
                            <?php foreach ($recorded_recipients as $rec): ?>
                                <div class="recipient-tag" data-recipient="<?= htmlspecialchars($rec['recipient']); ?>">
                                    <span>
                                        <?= htmlspecialchars($rec['recipient']); ?>
                                    </span>
                                    <span class="recipient-tag-count">
                                        <?= $rec['count']; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="purpose" class="form-label">目的・メモ (Purpose) - 任意</label>
                <div class="position-relative">
                    <textarea name="purpose" id="purpose" rows="3" class="form-control"
                        placeholder="例: 月々の支援、緊急費用、医療費"></textarea>
                    <i class="fas fa-sticky-note input-icon" style="top: 25px;"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">
                <i class="fas fa-paper-plane me-2"></i>送金記録を追加
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="remittance.php" class="back-link">
                <i class="fas fa-arrow-left"></i> ダッシュボードに戻る
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const recipientInput = document.getElementById('recipient');
            const recipientTags = document.querySelectorAll('.recipient-tag');

            recipientTags.forEach(tag => {
                tag.addEventListener('click', function () {
                    const recipientName = this.getAttribute('data-recipient');
                    recipientInput.value = recipientName;
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => this.style.transform = '', 200);
                    document.getElementById('purpose').focus();
                });
            });

            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function () {
                    const icon = this.previousElementSibling;
                    if (icon && icon.classList.contains('input-icon')) {
                        icon.style.color = '#ff8c00';
                    }
                });

                input.addEventListener('blur', function () {
                    if (this.value === '') {
                        const icon = this.previousElementSibling;
                        if (icon && icon.classList.contains('input-icon')) {
                            icon.style.color = '#ffbe0b';
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>