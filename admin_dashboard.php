<?php
include 'db.php';
// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

// --- LOGIC: ADD PRISONER ---
if (isset($_POST['add_prisoner'])) {
    $name = $_POST['name'];
    $crime = $_POST['crime'];
    // In a real app, you would create a 'user' login for them first here
    $sql = "INSERT INTO prisoners (full_name, crime, sentence_duration) VALUES ('$name', '$crime', 12)";
    $conn->query($sql);
}

// --- LOGIC: EVALUATE PRISONER (The Update Feature) ---
if (isset($_POST['evaluate_id'])) {
    $p_id = $_POST['evaluate_id'];
    $points = $_POST['current_points'];
    
    // Automated Logic based on requirements
    if ($points >= 80) {
        $new_status = 'Paroled';
    } elseif ($points <= 20) {
        $new_status = 'Isolated';
    } else {
        $new_status = 'Normal';
    }
    
    $conn->query("UPDATE prisoners SET status='$new_status' WHERE id=$p_id");
    echo "Prisoner Status Updated to $new_status!";
}

// --- VIEW PRISONERS ---
$result = $conn->query("SELECT * FROM prisoners");
?>

<h2>Admin Dashboard</h2>

<form method="POST">
    <h3>Add Prisoner</h3>
    <input type="text" name="name" placeholder="Name" required>
    <input type="text" name="crime" placeholder="Crime" required>
    <button type="submit" name="add_prisoner">Add Prisoner</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Points</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td><?php echo $row['behavior_points']; ?></td>
        <td><?php echo $row['status']; ?></td>
        <td>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="evaluate_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="current_points" value="<?php echo $row['behavior_points']; ?>">
                <button type="submit">Evaluate Status</button>
            </form>
            <a href="assign_duty.php?id=<?php echo $row['id']; ?>">Assign Duty</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>