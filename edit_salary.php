<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "無効なIDです。";
    exit;
}

// Get salary entry to edit
$stmt = $pdo->prepare("SELECT * FROM salaries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$salary = $stmt->fetch();

if (!$salary) {
    echo "レコードが見つかりませんでした。";
    exit;
}

// Get all unique store names for suggestions
$stmt_stores = $pdo->prepare("SELECT DISTINCT store_name FROM salaries WHERE user_id = ? ORDER BY store_name ASC");
$stmt_stores->execute([$user_id]);
$available_stores = $stmt_stores->fetchAll(PDO::FETCH_COLUMN);

// If form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store = trim(htmlspecialchars($_POST['store']));
    $amount = $_POST['amount'];
    $received_date = $_POST['received_date'];
    $working_month_input = $_POST['working_month'];
    $notes = htmlspecialchars($_POST['notes']);

    $working_month = $working_month_input . "-01";

    $stmt = $pdo->prepare("UPDATE salaries 
                           SET store_name = ?, amount = ?, received_date = ?, working_month = ?, notes = ? 
                           WHERE id = ? AND user_id = ?");
    $stmt->execute([$store, $amount, $received_date, $working_month, $notes, $id, $user_id]);

    header("Location: view_salaries.php");
    exit;
}

$working_month_value = date('Y-m', strtotime($salary['working_month']));
?>

<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>給与記録の編集</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(125deg, #4cc9f0, #4361ee, #7209b7, #f72585);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            max-width: 700px;
        }

        h2 {
            color: white;
            text-align: center;
            font-weight: 700;
            margin-bottom: 30px;
            text-shadow: 0px 2px 10px rgba(0, 0, 0, 0.3);
            animation: fadeInDown 1s ease-out;
        }

        h2::before {
            content: '✏️ ';
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

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            animation: cardEntrance 1s ease-out;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 50px;
            padding: 12px 20px;
            border: 2px solid transparent;
            background-color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.25);
            background-color: white;
        }

        textarea.form-control {
            border-radius: 20px;
        }

        .btn {
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4361ee, #3a0ca3);
            border: none;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            background: linear-gradient(45deg, #3a0ca3, #f72585);
            box-shadow: 0 15px 25px rgba(67, 97, 238, 0.4);
        }

        .btn-secondary {
            background: rgba(108, 117, 125, 0.9);
            border: none;
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            background: rgba(108, 117, 125, 1);
        }

        .store-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
            padding: 10px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 15px;
        }

        .store-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .store-tag:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.5);
        }

        .helper-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        @media (max-width: 576px) {
            .form-card {
                padding: 25px;
            }

            h2 {
                font-size: 24px;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .d-flex {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>給与記録の編集</h2>

        <div class="form-card">
            <form method="post">
                <div class="mb-3">
                    <label for="store" class="form-label">
                        <i class="fas fa-store"></i> 店舗名
                    </label>
                    <input type="text" name="store" id="store" class="form-control"
                        value="<?= htmlspecialchars($salary['store_name']) ?>" placeholder="店舗名を入力" maxlength="100"
                        required autocomplete="off">

                    <?php if (!empty($available_stores)): ?>
                        <small class="helper-text">以前の店舗から選択:</small>
                        <div class="store-tags">
                            <?php foreach ($available_stores as $store): ?>
                                <div class="store-tag" data-store="<?= htmlspecialchars($store) ?>">
                                    <?= htmlspecialchars($store) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">
                        <i class="fas fa-yen-sign"></i> 金額
                    </label>
                    <input type="number" name="amount" id="amount" class="form-control" value="<?= $salary['amount'] ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label for="received_date" class="form-label">
                        <i class="fas fa-calendar-alt"></i> 受領日
                    </label>
                    <input type="date" name="received_date" id="received_date" class="form-control"
                        value="<?= $salary['received_date'] ?>" required>
                </div>

                <div class="mb-3">
                    <label for="working_month" class="form-label">
                        <i class="fas fa-calendar-check"></i> 勤務月
                    </label>
                    <input type="month" name="working_month" id="working_month" class="form-control"
                        value="<?= $working_month_value ?>" required>
                </div>

                <div class="mb-4">
                    <label for="notes" class="form-label">
                        <i class="fas fa-sticky-note"></i> メモ
                    </label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"
                        placeholder="追加情報やメモを入力"><?= htmlspecialchars($salary['notes']) ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> 更新
                    </button>
                    <a href="view_salaries.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> リストに戻る
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const storeInput = document.getElementById('store');
            const storeTags = document.querySelectorAll('.store-tag');

            storeTags.forEach(tag => {
                tag.addEventListener('click', function () {
                    const storeName = this.getAttribute('data-store');
                    storeInput.value = storeName;

                    this.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 200);

                    storeInput.focus();
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>