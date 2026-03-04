<?php
include("config/db.php");

$id = $_GET['id'];

// ดึงข้อมูลเดิมมาแสดง
$result = $conn->query("SELECT * FROM policies WHERE id=$id");
$row = $result->fetch_assoc();

// ถ้ากดปุ่ม update
if(isset($_POST['update'])){
    $name = $_POST['customer_name'];
    $number = $_POST['policy_number'];
    $premium = $_POST['premium'];

    $conn->query("UPDATE policies 
                  SET customer_name='$name',
                      policy_number='$number',
                      premium='$premium'
                  WHERE id=$id");

    header("Location: policies.php");
}
?>

<h2>Edit Policy</h2>

<form method="POST">
    Customer Name:
    <input type="text" name="customer_name" 
        value="<?php echo $row['customer_name']; ?>"><br><br>

    Policy Number:
    <input type="text" name="policy_number" 
        value="<?php echo $row['policy_number']; ?>"><br><br>

    Premium:
    <input type="number" name="premium" 
        value="<?php echo $row['premium']; ?>"><br><br>

    <button type="submit" name="update">Update</button>
</form>