<?php
session_start();
include("config/db.php");

$message = "";

if(isset($_POST['register'])){

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(empty($username) || empty($password)){
        $message = "<div class='alert alert-danger'>Please fill all fields.</div>";
    } else {

        // เช็ค username ซ้ำ (ใช้ Prepared)
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows > 0){
            $message = "<div class='alert alert-warning'>Username already exists!</div>";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $insert->bind_param("s", $username, $hashedPassword);

            if($insert->execute()){
                header("Location: login.php?registered=success");
                exit();
            } else {
                $message = "<div class='alert alert-danger'>Something went wrong.</div>";
            }

            $insert->close();
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4 mx-auto" style="max-width:400px;">
        
        <h3 class="text-center mb-4">Create Account</h3>

        <?php echo $message; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>

            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100">
                Register
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php">Already have an account? Login</a>
        </div>

    </div>
</div>

</body>
</html>