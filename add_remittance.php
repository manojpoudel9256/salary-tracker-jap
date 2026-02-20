<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Add Remittance',
        'subtitle' => 'Record a new transfer',
        'amount' => 'Amount (¥)',
        'amount_hint' => 'Enter the amount sent',
        'amount_placeholder' => 'e.g. 50000',
        'date' => 'Date Sent',
        'recipient' => 'Recipient',
        'recipient_opt' => 'Optional',
        'recipient_hint' => 'Enter recipient name',
        'recipient_placeholder' => 'e.g. Mother, Family',
        'past_recipients' => 'Recent Recipients',
        'purpose' => 'Purpose / Memo',
        'purpose_opt' => 'Optional',
        'purpose_placeholder' => 'e.g. Monthly support, Emergency',
        'submit' => 'Save Remittance',
        'error_amount' => 'Amount must be a positive number.',
        'error_db' => 'Error adding record: '
    ],
    'jp' => [
        'title' => '送金記録の追加',
        'subtitle' => '新しい送金を記録',
        'amount' => '送金額（¥）',
        'amount_hint' => '送った金額を入力してください',
        'amount_placeholder' => '例: 50000',
        'date' => '送金日',
        'recipient' => '受取人',
        'recipient_opt' => '任意',
        'recipient_hint' => '送金先の人を入力',
        'recipient_placeholder' => '例: 母、家族、兄弟',
        'past_recipients' => '過去の受取人',
        'purpose' => '目的・メモ',
        'purpose_opt' => '任意',
        'purpose_placeholder' => '例: 月々の支援、緊急費用',
        'submit' => '送金記録を追加',
        'error_amount' => '金額は正の数でなければなりません。',
        'error_db' => '送金記録追加エラー: '
    ]
];
$t = $trans[$lang];

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = $_POST['amount'];
    $date_sent = $_POST['date_sent'];
    $recipient = trim(htmlspecialchars($_POST['recipient']));
    $purpose = htmlspecialchars($_POST['purpose']);

    if ($amount <= 0) {
        $message = $t['error_amount'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO remittances (user_id, amount, date_sent, recipient, purpose)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $amount, $date_sent, $recipient, $purpose]);
            header('Location: view_remittance.php');
            exit;
        } catch (Exception $e) {
            $message = $t['error_db'] . $e->getMessage();
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
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['title'] ?> | Salary Tracker</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link rel="icon" href="icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="icon/apple-touch-icon.png">

    <style>
        :root {
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-blur: blur(20px);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --accent: #fa709a;
            --accent-glow: rgba(250, 112, 154, 0.3);
            --safe-top: env(safe-area-inset-top);
            --safe-bottom: env(safe-area-inset-bottom);
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
            color: var(--text-primary);
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

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            animation: fadeInDown 0.5s ease-out;
        }

        .back-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            text-decoration: none;
            backdrop-filter: var(--glass-blur);
            margin-right: 16px;
            flex-shrink: 0;
        }

        .header-text h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 22px;
            margin: 0;
        }

        .header-text p {
            font-size: 12px;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Alert */
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #ff6b7a;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            animation: fadeInUp 0.3s ease-out;
        }

        /* Form Card */
        .form-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            animation: fadeInUp 0.5s ease-out 0.1s backwards;
        }

        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .field-label i {
            color: var(--accent);
            font-size: 14px;
        }

        .opt-badge {
            font-size: 9px;
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            color: rgba(255, 255, 255, 0.4);
            margin-left: auto;
        }

        .field-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-primary);
            font-size: 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s;
            -webkit-appearance: none;
        }

        .field-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            background: rgba(255, 255, 255, 0.1);
        }

        .field-input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        /* Date input fix */
        input[type="date"].field-input {
            color-scheme: dark;
        }

        textarea.field-input {
            resize: vertical;
            min-height: 80px;
        }

        .field-hint {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.35);
            margin-top: 6px;
            padding-left: 4px;
        }

        /* Quick Recipient Chips */
        .chips-section {
            margin-top: 12px;
            padding: 12px;
            background: rgba(250, 112, 154, 0.06);
            border: 1px dashed rgba(250, 112, 154, 0.2);
            border-radius: 12px;
        }

        .chips-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--accent);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .chips-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, rgba(250, 112, 154, 0.25), rgba(254, 225, 64, 0.15));
            border: 1px solid rgba(250, 112, 154, 0.3);
            color: var(--text-primary);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .chip:active {
            transform: scale(0.95);
        }

        .chip-count {
            background: rgba(255, 255, 255, 0.15);
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #fa709a, #fee140);
            color: #1a1a2e;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:active {
            transform: scale(0.97);
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
    <div class="container">

        <!-- Header -->
        <div class="page-header">
            <a href="remittance.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><i class="fas fa-paper-plane me-2"></i><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($message)): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" class="form-card">

            <!-- Amount -->
            <div class="field-group">
                <label for="amount" class="field-label">
                    <i class="fas fa-yen-sign"></i> <?= $t['amount'] ?>
                </label>
                <input type="number" name="amount" id="amount" class="field-input"
                    placeholder="<?= $t['amount_placeholder'] ?>" required>
                <div class="field-hint"><?= $t['amount_hint'] ?></div>
            </div>

            <!-- Date -->
            <div class="field-group">
                <label for="date_sent" class="field-label">
                    <i class="fas fa-calendar-alt"></i> <?= $t['date'] ?>
                </label>
                <input type="date" name="date_sent" id="date_sent" class="field-input" required>
            </div>

            <!-- Recipient -->
            <div class="field-group">
                <label for="recipient" class="field-label">
                    <i class="fas fa-user"></i> <?= $t['recipient'] ?>
                    <span class="opt-badge"><?= $t['recipient_opt'] ?></span>
                </label>
                <input type="text" name="recipient" id="recipient" class="field-input"
                    placeholder="<?= $t['recipient_placeholder'] ?>" maxlength="100" autocomplete="off">
                <div class="field-hint"><?= $t['recipient_hint'] ?></div>

                <?php if (!empty($recorded_recipients)): ?>
                    <div class="chips-section">
                        <div class="chips-title">
                            <i class="fas fa-history"></i> <?= $t['past_recipients'] ?>
                        </div>
                        <div class="chips-wrap">
                            <?php foreach ($recorded_recipients as $rec): ?>
                                <div class="chip" data-recipient="<?= htmlspecialchars($rec['recipient']); ?>">
                                    <span><?= htmlspecialchars($rec['recipient']); ?></span>
                                    <span class="chip-count"><?= $rec['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Purpose -->
            <div class="field-group">
                <label for="purpose" class="field-label">
                    <i class="fas fa-sticky-note"></i> <?= $t['purpose'] ?>
                    <span class="opt-badge"><?= $t['purpose_opt'] ?></span>
                </label>
                <textarea name="purpose" id="purpose" rows="3" class="field-input"
                    placeholder="<?= $t['purpose_placeholder'] ?>"></textarea>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> <?= $t['submit'] ?>
            </button>
        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Set today as default date
            const dateInput = document.getElementById('date_sent');
            if (!dateInput.value) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }

            // Recipient chip quick-select
            document.querySelectorAll('.chip').forEach(chip => {
                chip.addEventListener('click', function () {
                    const recipientInput = document.getElementById('recipient');
                    recipientInput.value = this.getAttribute('data-recipient');
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => this.style.transform = '', 200);
                    document.getElementById('purpose').focus();
                });
            });

            // Focus glow effect
            document.querySelectorAll('.field-input').forEach(input => {
                input.addEventListener('focus', function () {
                    this.parentElement.style.transform = 'scale(1.01)';
                });
                input.addEventListener('blur', function () {
                    this.parentElement.style.transform = '';
                });
            });
        });
    </script>
</body>

</html>