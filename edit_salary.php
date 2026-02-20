<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$lang = $_SESSION['lang'] ?? 'en';
$id = $_GET['id'] ?? null;

// Translations
$trans = [
    'en' => [
        'title' => 'Edit Salary',
        'subtitle' => 'Update salary record',
        'store' => 'Store Name',
        'store_placeholder' => 'Enter store name',
        'store_hint' => 'Select from previous stores',
        'amount' => 'Amount (¥)',
        'amount_placeholder' => 'e.g. 250000',
        'received' => 'Received Date',
        'working' => 'Working Month',
        'notes' => 'Notes',
        'notes_opt' => 'Optional',
        'notes_placeholder' => 'Additional info or memo',
        'submit' => 'Save Changes',
        'back' => 'Back to List',
        'error_id' => 'Invalid ID.',
        'error_record' => 'Record not found.',
        'created' => 'Created'
    ],
    'jp' => [
        'title' => '給与記録の編集',
        'subtitle' => '給与記録を更新',
        'store' => '店舗名',
        'store_placeholder' => '店舗名を入力',
        'store_hint' => '以前の店舗から選択',
        'amount' => '金額（¥）',
        'amount_placeholder' => '例: 250000',
        'received' => '受領日',
        'working' => '勤務月',
        'notes' => 'メモ',
        'notes_opt' => '任意',
        'notes_placeholder' => '追加情報やメモを入力',
        'submit' => '変更を保存',
        'back' => 'リストに戻る',
        'error_id' => '無効なIDです。',
        'error_record' => 'レコードが見つかりませんでした。',
        'created' => '記録日'
    ]
];
$t = $trans[$lang];

if (!$id) {
    echo $t['error_id'];
    exit;
}

// Get salary entry
$stmt = $pdo->prepare("SELECT * FROM salaries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$salary = $stmt->fetch();

if (!$salary) {
    echo $t['error_record'];
    exit;
}

// Get unique store names
$stmt_stores = $pdo->prepare("SELECT DISTINCT store_name FROM salaries WHERE user_id = ? ORDER BY store_name ASC");
$stmt_stores->execute([$user_id]);
$available_stores = $stmt_stores->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store = trim(htmlspecialchars($_POST['store']));
    $amount = $_POST['amount'];
    $received_date = $_POST['received_date'];
    $working_month_input = $_POST['working_month'];
    $notes = htmlspecialchars($_POST['notes']);
    $working_month = $working_month_input . "-01";

    $stmt = $pdo->prepare("UPDATE salaries SET store_name = ?, amount = ?, received_date = ?, working_month = ?, notes = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$store, $amount, $received_date, $working_month, $notes, $id, $user_id]);

    header("Location: view_salaries.php");
    exit;
}

$working_month_value = date('Y-m', strtotime($salary['working_month']));
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
            --accent: #4facfe;
            --accent-glow: rgba(79, 172, 254, 0.3);
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

        /* Created badge */
        .created-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(79, 172, 254, 0.15);
            border: 1px solid rgba(79, 172, 254, 0.25);
            border-radius: 10px;
            font-size: 11px;
            color: #4facfe;
            margin-bottom: 16px;
            animation: fadeInUp 0.4s ease-out 0.1s backwards;
        }

        /* Form Card */
        .form-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            animation: fadeInUp 0.5s ease-out 0.15s backwards;
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

        input[type="date"].field-input,
        input[type="month"].field-input {
            color-scheme: dark;
        }

        textarea.field-input {
            resize: vertical;
            min-height: 80px;
        }

        /* Store Chips */
        .chips-section {
            margin-top: 12px;
            padding: 12px;
            background: rgba(79, 172, 254, 0.06);
            border: 1px dashed rgba(79, 172, 254, 0.2);
            border-radius: 12px;
        }

        .chips-title {
            font-size: 11px;
            font-weight: 600;
            color: #4facfe;
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
            gap: 4px;
            padding: 8px 14px;
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.25), rgba(67, 233, 123, 0.15));
            border: 1px solid rgba(79, 172, 254, 0.3);
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

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #4facfe, #00f2fe);
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

        /* Back link */
        .back-link {
            display: block;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
            text-decoration: none;
            padding: 12px;
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
            <a href="view_salaries.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <div class="header-text">
                <h1><i class="fas fa-edit me-2"></i><?= $t['title'] ?></h1>
                <p><?= $t['subtitle'] ?></p>
            </div>
        </div>

        <!-- Created Badge -->
        <div class="created-badge">
            <i class="fas fa-info-circle"></i>
            <?= $t['created'] ?>: <?= date('Y/m/d', strtotime($salary['created_at'] ?? $salary['received_date'])) ?>
        </div>

        <!-- Form -->
        <form method="post" class="form-card">

            <!-- Store Name -->
            <div class="field-group">
                <label for="store" class="field-label">
                    <i class="fas fa-store"></i> <?= $t['store'] ?>
                </label>
                <input type="text" name="store" id="store" class="field-input"
                    placeholder="<?= $t['store_placeholder'] ?>" maxlength="100"
                    value="<?= htmlspecialchars($salary['store_name']) ?>" required autocomplete="off">

                <?php if (!empty($available_stores)): ?>
                    <div class="chips-section">
                        <div class="chips-title">
                            <i class="fas fa-history"></i> <?= $t['store_hint'] ?>
                        </div>
                        <div class="chips-wrap">
                            <?php foreach ($available_stores as $store): ?>
                                <div class="chip" data-store="<?= htmlspecialchars($store) ?>">
                                    <i class="fas fa-store" style="font-size:10px;opacity:0.6;"></i>
                                    <?= htmlspecialchars($store) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Amount -->
            <div class="field-group">
                <label for="amount" class="field-label">
                    <i class="fas fa-yen-sign"></i> <?= $t['amount'] ?>
                </label>
                <input type="number" name="amount" id="amount" class="field-input"
                    placeholder="<?= $t['amount_placeholder'] ?>" value="<?= $salary['amount'] ?>" required>
            </div>

            <!-- Received Date -->
            <div class="field-group">
                <label for="received_date" class="field-label">
                    <i class="fas fa-calendar-alt"></i> <?= $t['received'] ?>
                </label>
                <input type="date" name="received_date" id="received_date" class="field-input"
                    value="<?= $salary['received_date'] ?>" required>
            </div>

            <!-- Working Month -->
            <div class="field-group">
                <label for="working_month" class="field-label">
                    <i class="fas fa-calendar-check"></i> <?= $t['working'] ?>
                </label>
                <input type="month" name="working_month" id="working_month" class="field-input"
                    value="<?= $working_month_value ?>" required>
            </div>

            <!-- Notes -->
            <div class="field-group">
                <label for="notes" class="field-label">
                    <i class="fas fa-sticky-note"></i> <?= $t['notes'] ?>
                    <span class="opt-badge"><?= $t['notes_opt'] ?></span>
                </label>
                <textarea name="notes" id="notes" rows="3" class="field-input"
                    placeholder="<?= $t['notes_placeholder'] ?>"><?= htmlspecialchars($salary['notes']) ?></textarea>
            </div>

            <!-- Submit -->
            <button type="submit" class="submit-btn">
                <i class="fas fa-save"></i> <?= $t['submit'] ?>
            </button>
        </form>

        <a href="view_salaries.php" class="back-link">
            <i class="fas fa-arrow-left me-1"></i> <?= $t['back'] ?>
        </a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Store chip quick-select
            document.querySelectorAll('.chip').forEach(chip => {
                chip.addEventListener('click', function () {
                    const storeInput = document.getElementById('store');
                    storeInput.value = this.getAttribute('data-store');
                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => this.style.transform = '', 200);
                    document.getElementById('amount').focus();
                });
            });

            // Focus glow
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