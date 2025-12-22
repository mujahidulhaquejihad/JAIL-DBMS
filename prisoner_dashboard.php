<?php
include 'db.php';
if ($_SESSION['role'] != 'prisoner') { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Get Prisoner Info
$stmt = $conn->prepare("SELECT * FROM prisoners WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

// --- LOG WORK HOURS ---
if (isset($_POST['log_work'])) {
    $hours = $_POST['hours'];
    $type = $_POST['work_type'];
    $pid = $me['id'];
    
    // Insert as Pending
    $sql = "INSERT INTO work_logs (prisoner_id, hours_worked, work_type, status) VALUES ('$pid', '$hours', '$type', 'Pending')";
    if($conn->query($sql)) {
        echo "Work log submitted for Admin approval.";
    }
}
?>

<h1>Welcome, <?php echo $me['full_name']; ?></h1>
<p>Status: <strong><?php echo $me['status']; ?></strong></p>
<p>Behavior Points: <?php echo $me['behavior_points']; ?></p>

<h3>Log Work Hours</h3>
<form method="POST">
    <input type="number" name="hours" placeholder="Hours Worked">
    <select name="work_type">
        <option value="Kitchen">Kitchen</option>
        <option value="Cleaning">Cleaning</option>
        <option value="Laundry">Laundry</option>
    </select>
    <button type="submit" name="log_work">Submit for Approval</button>
</form>