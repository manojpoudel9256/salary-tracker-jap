<?php
include 'auth/protected.php';
include 'config/db.php';

$user_id = $_SESSION['user_id'];

// Prepare CSV export headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="salary_data.csv"');

$output = fopen('php://output', 'w');

// CSV column headers
fputcsv($output, ['Store Name', 'Amount', 'Received Date', 'Working Month', 'Notes']);

$stmt = $pdo->prepare("SELECT store_name, amount, received_date, working_month, notes FROM salaries WHERE user_id = ?");
$stmt->execute([$user_id]);

// Fetch data and write to CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Salary Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5 text-center">
        <h2 class="mb-4">Export Salary Data</h2>
        <div class="alert alert-info">
            Your salary data is being exported as a CSV file. The download should start automatically. If not, <a
                href="export_csv.php" class="alert-link">click here to download the file manually.</a>
        </div>

        <a href="view_salaries.php" class="btn btn-secondary">Back to Salary Records</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

</body>

</html>