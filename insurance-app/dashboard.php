<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

// ===== ดึงข้อมูลสรุป =====
$totalPoliciesQuery = $conn->query("SELECT COUNT(*) as total FROM policies");
$totalPolicies = $totalPoliciesQuery->fetch_assoc()['total'] ?? 0;

$totalPremiumQuery = $conn->query("SELECT SUM(premium) as total FROM policies");
$totalPremium = $totalPremiumQuery->fetch_assoc()['total'] ?? 0;

$thisMonthQuery = $conn->query("
    SELECT SUM(premium) as total 
    FROM policies 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$thisMonth = $thisMonthQuery->fetch_assoc()['total'] ?? 0;

// ===== ดึงรายได้ 6 เดือนล่าสุดสำหรับกราฟ =====
$monthlyData = [];
$monthlyLabels = [];

for($i = 5; $i >= 0; $i--){
    $month = date("m", strtotime("-$i months"));
    $year = date("Y", strtotime("-$i months"));
    $label = date("M", strtotime("-$i months"));

    $query = $conn->query("
        SELECT SUM(premium) as total 
        FROM policies 
        WHERE MONTH(created_at) = '$month'
        AND YEAR(created_at) = '$year'
    ");

    $data = $query->fetch_assoc()['total'] ?? 0;

    $monthlyLabels[] = $label;
    $monthlyData[] = $data;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Insurance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .hover-card {
            transition: 0.3s;
        }
        .hover-card:hover {
            transform: translateY(-6px);
        }
    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">Insurance System</a>

        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                Welcome, <b><?php echo $_SESSION['username']; ?></b>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Dashboard Overview</h3>
        <a href="policies.php" class="btn btn-primary">Manage Policies</a>
    </div>

    <div class="row g-4">

        <!-- Total Policies -->
        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 p-4 hover-card">
                <h6 class="text-muted">Total Policies</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="text-primary fw-bold mb-0">
                        <?php echo number_format($totalPolicies); ?>
                    </h2>
                    <span style="font-size:30px;">📄</span>
                </div>
            </div>
        </div>

        <!-- Total Premium -->
        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 p-4 hover-card">
                <h6 class="text-muted">Total Premium</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="text-success fw-bold mb-0">
                        <?php echo number_format($totalPremium,2); ?> บาท
                    </h2>
                    <span style="font-size:30px;">💰</span>
                </div>
            </div>
        </div>

        <!-- This Month Revenue -->
        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 p-4 hover-card">
                <h6 class="text-muted">This Month Revenue</h6>
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="text-warning fw-bold mb-0">
                        <?php echo number_format($thisMonth,2); ?> บาท
                    </h2>
                    <span style="font-size:30px;">📊</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Chart Section -->
    <div class="card mt-5 shadow border-0 rounded-4 p-4">
        <h5 class="fw-bold mb-3">Revenue Last 6 Months</h5>
        <canvas id="revenueChart"></canvas>
    </div>

    <!-- Extra Section -->
    <div class="card mt-5 shadow border-0 rounded-4 p-4">
        <h5 class="fw-bold mb-3">System Summary</h5>
        <p class="text-muted">
            This dashboard provides an overview of policy performance and revenue statistics.
            Use the Manage Policies button to add, edit, or review customer policies.
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($monthlyLabels); ?>,
        datasets: [{
            label: 'Revenue (Baht)',
            data: <?php echo json_encode($monthlyData); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        }
    }
});
</script>

</body>
</html>