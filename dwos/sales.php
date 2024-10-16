<?php
// Start the session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('dwos.php');

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    exit();
}

// Get the logged-in admin's user_id
$user_id = $_SESSION['user_id'];

// Fetch Admin Details from the database using prepared statements
$stmt = $conn->prepare("SELECT user_name, image, password FROM users WHERE user_id = ? AND user_type = 'A'");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    exit();
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    error_log("Error fetching admin details: " . $conn->error); 
    exit();
}

// Queries to calculate sales for different time periods
$current_date = date('Y-m-d');

$sql_today = "SELECT SUM(memberships.price) as total_sales_today 
              FROM subscriptions 
              JOIN memberships ON subscriptions.membership_id = memberships.membership_id
              WHERE subscriptions.start_date = CURDATE()";
$result_today = $conn->query($sql_today);
$row_today = $result_today->fetch_assoc();
$total_sales_today = $row_today['total_sales_today'] ? $row_today['total_sales_today'] : 0;

$sql_week = "SELECT SUM(memberships.price) as total_sales_week 
             FROM subscriptions 
             JOIN memberships ON subscriptions.membership_id = memberships.membership_id
             WHERE YEARWEEK(subscriptions.start_date, 1) = YEARWEEK(CURDATE(), 1)";
$result_week = $conn->query($sql_week);
$row_week = $result_week->fetch_assoc();
$total_sales_week = $row_week['total_sales_week'] ? $row_week['total_sales_week'] : 0;

$sql_month = "SELECT SUM(memberships.price) as total_sales_month 
              FROM subscriptions 
              JOIN memberships ON subscriptions.membership_id = memberships.membership_id
              WHERE MONTH(subscriptions.start_date) = MONTH(CURDATE()) AND YEAR(subscriptions.start_date) = YEAR(CURDATE())";
$result_month = $conn->query($sql_month);
$row_month = $result_month->fetch_assoc();
$total_sales_month = $row_month['total_sales_month'] ? $row_month['total_sales_month'] : 0;

$sql_year = "SELECT SUM(memberships.price) as total_sales_year 
             FROM subscriptions 
             JOIN memberships ON subscriptions.membership_id = memberships.membership_id
             WHERE YEAR(subscriptions.start_date) = YEAR(CURDATE())";
$result_year = $conn->query($sql_year);
$row_year = $result_year->fetch_assoc();
$total_sales_year = $row_year['total_sales_year'] ? $row_year['total_sales_year'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Summary</title>
    <link rel="stylesheet" href="subscribers.css"> 
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fredoka:wght@300..700&display=swap');

        body {
            font-family: 'Fredoka', sans-serif;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: url('image/home.png') no-repeat;
            min-height: 95vh;
            background-size: cover;
            background-position: center;
            overflow: auto;
        }

        h1 {
            color: black;
            padding: 10px 20px;
            text-align: center;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
        }

        .summary-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            margin: 20px auto;
            width: 90%;
            max-width: 1000px;
        }

        .summary-container p {
            background-color: #F1F1F3;
            padding: 15px;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 50%;
            max-width: 600px;
            text-align: center;
            margin: 10px 0;
        }

        .sales-container {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    max-height: 80%;
    overflow-y: auto;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);

    display: flex;
    flex-direction: column; /* Arrange <p> elements in a column */
    align-items: center; /* Align <p> elements to the center horizontally */
}

.sales-container p {
    width: 50%; /* Make all <p> elements take full width of the container */
    max-width: 600px;
    background-color: #F1F1F3;
    padding: 15px;
    border-radius: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
    margin: 10px 0;
}


    </style>
</head>
<body>

<?php include 'adminnavbar.php'; ?>

    <h1>Sales Summary</h1>
    <div class="summary-container">
        <div class="sales-container">
        <h2>Sales Totals</h2>
        <p><strong>Today's Sales:</strong> ₱<?php echo number_format($total_sales_today, 2); ?></p>
        <p><strong>This Week's Sales:</strong> ₱<?php echo number_format($total_sales_week, 2); ?></p>
        <p><strong>This Month's Sales:</strong> ₱<?php echo number_format($total_sales_month, 2); ?></p>
        <p><strong>This Year's Sales:</strong> ₱<?php echo number_format($total_sales_year, 2); ?></p>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
