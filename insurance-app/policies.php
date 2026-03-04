<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

// เพิ่มข้อมูล
if(isset($_POST['add'])){
    $name = $_POST['customer_name'];
    $number = $_POST['policy_number'];
    $premium = $_POST['premium'];

    $conn->query("INSERT INTO policies (customer_name, policy_number, premium)
                  VALUES ('$name', '$number', '$premium')");
}

// ลบข้อมูล
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM policies WHERE id=$id");
}

// 🔎 ค้นหา
$search = "";
if(isset($_GET['search']) && $_GET['search'] != ''){
    $search = $_GET['search'];
    $result = $conn->query("
        SELECT * FROM policies 
        WHERE customer_name LIKE '%$search%' 
        ORDER BY id DESC
    ");
} else {
    $result = $conn->query("SELECT * FROM policies ORDER BY id DESC");
}

$count = $result->num_rows;

// 💰 Total Premium
if($search != ''){
    $totalQuery = $conn->query("
        SELECT SUM(premium) as total 
        FROM policies 
        WHERE customer_name LIKE '%$search%'
    ");
} else {
    $totalQuery = $conn->query("SELECT SUM(premium) as total FROM policies");
}
$total = $totalQuery->fetch_assoc();

// 📊 Chart รายเดือน
$chartQuery = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           SUM(premium) as total
    FROM policies
    GROUP BY month
    ORDER BY month ASC
");

$months = [];
$totals = [];

while($rowChart = $chartQuery->fetch_assoc()){
    $months[] = $rowChart['month'];
    $totals[] = $rowChart['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insurance Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand">Insurance System</span>
    <div class="text-white">
        Welcome, <b><?php echo $_SESSION['username']; ?></b>
        <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container mt-5">

    <!-- Total -->
    <div class="card p-4 mb-4 shadow-sm">
        <h5>Total Premium</h5>
        <h2 class="text-primary">
            <?php echo number_format($total['total'] ?? 0,2); ?> บาท
        </h2>
    </div>

    <div class="card p-4 shadow-sm">

        <h5>Add Policy</h5>

        <!-- Add Form -->
        <form method="POST" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="customer_name" class="form-control" placeholder="Customer Name" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="policy_number" class="form-control" placeholder="Policy Number" required>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="premium" class="form-control" placeholder="Premium" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
            </div>
        </form>

        <!-- Search -->
        <form method="GET" class="mb-3 d-flex gap-2">
            <input type="text" name="search" class="form-control"
                   placeholder="Search Customer..."
                   value="<?php echo $search; ?>">
            <button type="submit" class="btn btn-dark">Search</button>
            <a href="policies.php" class="btn btn-secondary">Clear</a>
        </form>

        <!-- Result Info -->
        <?php if($search != ''): ?>
            <div class="alert alert-info">
                Showing results for: <b><?php echo $search; ?></b>
                (<?php echo $count; ?> record found)
            </div>
        <?php else: ?>
            <div class="alert alert-secondary">
                Total Policies: <?php echo $count; ?>
            </div>
        <?php endif; ?>

        <h5>Policy List</h5>

        <!-- Table -->
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Policy No.</th>
                    <th>Premium</th>
                    <th>Date</th>
                    <th width="160">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['customer_name']; ?></td>
                    <td><?php echo $row['policy_number']; ?></td>
                    <td class="text-success fw-semibold">
                        <?php echo number_format($row['premium'],2); ?>
                    </td>
                    <td>
                        <?php echo date("d M Y H:i", strtotime($row['created_at'])); ?>
                    </td>
                    <td>
                        <a href="edit_policy.php?id=<?php echo $row['id']; ?>"
                           class="btn btn-sm btn-warning">Edit</a>

                        <button class="btn btn-sm btn-danger"
                                onclick="confirmDelete(<?php echo $row['id']; ?>)">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Chart -->
        <div class="mt-5">
            <h5>Monthly Revenue</h5>
            <canvas id="revenueChart"></canvas>
        </div>

    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This policy will be deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "?delete=" + id;
        }
    });
}

const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Monthly Revenue',
            data: <?php echo json_encode($totals); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>