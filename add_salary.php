<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';

// Translations
$trans = [
    'en' => [
        'title' => 'Add Salary',
        'store_label' => 'Store Name',
        'store_placeholder' => 'e.g. 7-Eleven',
        'store_helper' => 'Tap a tag below to quick-fill',
        'amount_label' => 'Amount',
        'amount_placeholder' => 'e.g. 150000',
        'date_received' => 'Received Date',
        'working_month' => 'Working Month',
        'notes_label' => 'Notes (Optional)',
        'notes_placeholder' => 'Any additional details...',
        'submit_btn' => 'Save Record',
        'back_dashboard' => 'Dashboard',
        'history_title' => 'Recent Stores',
        'no_history' => 'No recent stores found.',
        'error_store' => 'Please enter a store name.',
        'error_amount' => 'Amount must be a positive number.',
        'success' => 'Salary record added successfully!',
        'error_general' => 'Error adding record: '
    ],
    'jp' => [
        'title' => '給与記録の追加',
        'store_label' => '店舗名',
        'store_placeholder' => '例: セブンイレブン',
        'store_helper' => '下のタグをタップして自動入力',
        'amount_label' => '金額',
        'amount_placeholder' => '例: 150000',
        'date_received' => '受領日',
        'working_month' => '勤務月',
        'notes_label' => 'メモ（任意）',
        'notes_placeholder' => '追加情報など...',
        'submit_btn' => '記録を保存',
        'back_dashboard' => 'ダッシュボード',
        'history_title' => '最近の店舗',
        'no_history' => '記録された店舗はありません',
        'error_store' => '店舗名を入力してください。',
        'error_amount' => '金額は正の数でなければなりません。',
        'success' => '給与記録が正常に追加されました！',
        'error_general' => 'エラーが発生しました: '
    ]
];
$t = $trans[$lang];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store = trim(htmlspecialchars($_POST['store']));
    $amount = $_POST['amount'];
    $received_date = $_POST['received_date'];
    $working_month_input = $_POST['working_month'];
    $notes = htmlspecialchars($_POST['notes']);
    $working_month = $working_month_input . "-01";

    if (empty($store)) {
        $message = $t['error_store'];
        $messageType = 'danger';
    } elseif ($amount <= 0) {
        $message = $t['error_amount'];
        $messageType = 'danger';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO salaries (user_id, store_name, amount, received_date, working_month, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $store, $amount, $received_date, $working_month, $notes]);

            // Redirect to history page on success
            header('Location: view_salaries.php');
            exit;
        } catch (Exception $e) {
            $message = $t['error_general'] . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch recent stores for "Quick Select" content
try {
    $stmt = $pdo->prepare("SELECT store_name, COUNT(*) as count FROM salaries WHERE user_id = ? GROUP BY store_name ORDER BY count DESC, store_name ASC LIMIT 8");
    $stmt->execute([$user_id]);
    $recorded_stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recorded_stores = [];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $t['title'] ?> | Salary Tracker</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- App Icons -->
    <link rel="icon" href="icon/salarytrackericon.png" type="image/png">
    <link rel="apple-touch-icon" href="icon/apple-touch-icon.png">

    <style>
        :root {
            /* Premium Color Palette */
            --bg-gradient-start: #0f0c29;
            --bg-gradient-mid: #302b63;
            --bg-gradient-end: #24243e;

            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-blur: blur(20px);

            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);

            --accent-primary: #4facfe;
            --accent-glow: rgba(79, 172, 254, 0.4);

            --input-bg: rgba(0, 0, 0, 0.2);
            --input-border: rgba(255, 255, 255, 0.1);

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
        }

        .page-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 24px;
            margin: 0;
        }

        /* Form Card */
        .form-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 24px;
            padding: 24px;
            animation: fadeInUp 0.5s ease-out;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 500;
            margin-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 16px;
            color: var(--text-primary);
            padding: 16px 16px 16px 48px;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
        }

        .form-control:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 4px var(--accent-glow);
            outline: none;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            pointer-events: none;
            transition: color 0.3s;
        }

        .form-control:focus+.input-icon {
            color: var(--accent-primary);
        }

        /* Textarea specific */
        textarea.form-control {
            min-height: 100px;
            padding-left: 16px;
            /* No icon for textarea usually, or adjust */
        }

        .textarea-icon {
            top: 24px;
            transform: none;
        }

        textarea.has-icon {
            padding-left: 48px;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 16px;
            padding: 16px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-submit:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
        }

        /* Chips */
        .chips-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .chip {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .chip:active {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(0.95);
        }

        .chip i {
            font-size: 10px;
            margin-right: 4px;
            opacity: 0.7;
        }

        .helper-text {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 6px;
            margin-left: 4px;
            opacity: 0.7;
        }

        /* Alert */
        .alert {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            backdrop-filter: blur(10px);
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
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="page-title"><?= $t['title'] ?></h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="form-card">
            <form method="post">

                <!-- Store Name -->
                <div class="form-group">
                    <label class="form-label"><?= $t['store_label'] ?></label>
                    <div class="input-wrapper">
                        <input type="text" name="store" id="store" class="form-control"
                            placeholder="<?= $t['store_placeholder'] ?>" required autocomplete="off">
                        <i class="fas fa-store input-icon"></i>
                    </div>

                    <!-- Quick Select Chips -->
                    <?php if (!empty($recorded_stores)): ?>
                        <div class="chips-container">
                            <?php foreach ($recorded_stores as $store): ?>
                                <div class="chip" onclick="fillStore('<?= htmlspecialchars($store['store_name']) ?>')">
                                    <i class="fas fa-history"></i> <?= htmlspecialchars($store['store_name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Amount -->
                <div class="form-group">
                    <label class="form-label"><?= $t['amount_label'] ?></label>
                    <div class="input-wrapper">
                        <input type="number" name="amount" class="form-control"
                            placeholder="<?= $t['amount_placeholder'] ?>" required inputmode="numeric">
                        <i class="fas fa-yen-sign input-icon"></i>
                    </div>
                </div>

                <!-- Received Date -->
                <div class="form-group">
                    <label class="form-label"><?= $t['date_received'] ?></label>
                    <div class="input-wrapper">
                        <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>"
                            required>
                        <i class="fas fa-calendar-alt input-icon"></i>
                    </div>
                </div>

                <!-- Working Month -->
                <div class="form-group">
                    <label class="form-label"><?= $t['working_month'] ?></label>
                    <div class="input-wrapper">
                        <input type="month" name="working_month" class="form-control" value="<?= date('Y-m') ?>"
                            required>
                        <i class="fas fa-briefcase input-icon"></i>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label"><?= $t['notes_label'] ?></label>
                    <div class="input-wrapper">
                        <textarea name="notes" class="form-control has-icon"
                            placeholder="<?= $t['notes_placeholder'] ?>"></textarea>
                        <i class="fas fa-sticky-note input-icon textarea-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <?= $t['submit_btn'] ?> <i class="fas fa-chevron-right ms-2"></i>
                </button>

            </form>
        </div>

    </div>

    <!-- Scripts -->
    <script>
        function fillStore(name) {
            const input = document.getElementById('store');
            input.value = name;
            // Visual feedback
            input.focus();

            // Highlight effect for the chip
            event.currentTarget.style.background = 'rgba(79, 172, 254, 0.3)';
            setTimeout(() => {
                event.target.closest('.chip').style.background = '';
            }, 300);
        }
    </script>
</body>

</html>