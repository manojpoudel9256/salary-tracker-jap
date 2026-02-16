<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

if (!$id) {
    echo "無効なIDです。";
    exit;
}

// Check if record exists and belongs to the user
$stmt = $pdo->prepare("SELECT * FROM salaries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$salary = $stmt->fetch();

if (!$salary) {
    echo "レコードが見つからないか、権限がありません。";
    exit;
}

// If the form is submitted to delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete the salary record
    $stmt = $pdo->prepare("DELETE FROM salaries WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);

    // Redirect after deletion
    header("Location: view_salaries.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="jp">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>給与記録を削除</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <h2 class="text-center mb-4">削除の確認</h2>

        <div class="alert alert-warning text-center">
            本当に<strong><?= htmlspecialchars($salary['store_name']) ?></strong>の給与記録（金額:
            <strong>¥<?= number_format($salary['amount']) ?></strong>、受領日:
            <strong><?= htmlspecialchars($salary['received_date']) ?></strong>）を削除してもよろしいですか？
        </div>


        <!-- Confirmation Form -->
        <form method="post" class="text-center">
            <button type="submit" class="btn btn-danger btn-lg">削除</button>
            <a href="view_salaries.php" class="btn btn-secondary btn-lg">キャンセル</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>

</html>