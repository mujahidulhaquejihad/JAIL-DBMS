<?php
session_start();
include 'db.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prisoner') { 
    header("Location: index.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];

// 2. Fetch Data
// Fetch prisoner data based on the logged-in user ID
$p_data_query = $conn->query("SELECT * FROM prisoner WHERE user_id='$uid'");
$p_data = $p_data_query->fetch_assoc();

// // --- SAFETY CHECK: User exists but has no Prisoner Profile ---
// if (!$p_data) {
//     echo "<div style='font-family:sans-serif; padding:20px; text-align:center; color:#721c24; background:#f8d7da; margin:20px;'>
//             <h2>Profile Not Found</h2>
//             <p>Your user account (ID: $uid) exists, but no Prisoner Profile is linked to it.</p>
//             <p>Please contact the Administrator to fix your records.</p>
//             <a href='logout.php' style='background:#333; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Logout</a>
//           </div>";
//     exit();
// }
// -------------------------------------------------------------

$pid = $p_data['prisoner_id'];
$crime_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid' LIMIT 1")->fetch_assoc();
$sent_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid' LIMIT 1")->fetch_assoc();

// 3. Handle Work Request
if (isset($_POST['submit_work'])) {
    $duty_id = $_POST['duty_id'];
    $hours = $_POST['hours'];
    
    $stmt = $conn->prepare("INSERT INTO duty_assignment (prisoner_id, duty_id, hours_assigned, status) VALUES (?, ?, ?, 'Pending')");
    
    $stmt->bind_param("sii", $pid, $duty_id, $hours);
    
    if ($stmt->execute()) { 
        $_SESSION['flash_msg'] = "Duty request submitted successfully."; 
    } else { 
        $_SESSION['flash_msg'] = "Error submitting request: " . $conn->error; 
    }
    
    header("Location: prisoner_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prisoner Portal</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e9ecef; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        
        /* Card Styles */
        .profile-card { background: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 20px; }
        .header { background: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; font-size: 20px; }
        .logout-btn { color: #ffcccc; text-decoration: none; font-size: 14px; }
        .card-body { padding: 20px; }
        
        /* Stats */
        .stat-box { display: inline-block; width: 48%; margin-bottom: 15px; font-size: 15px; }
        .stat-label { font-weight: bold; color: #555; display: block; margin-bottom: 3px; }
        .status-Paroled { color: green; font-weight: bold; }
        .status-Isolated { color: red; font-weight: bold; }
        .status-Normal { color: #007bff; font-weight: bold; }
        
        /* Personal Info Table */
        .info-table { width: 100%; font-size: 14px; border-collapse: collapse; }
        .info-table td { padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
        .lbl { color: #777; width: 130px; font-weight: 600; }
        .val { color: #333; }
        .sub-header { color: #007bff; font-weight: bold; margin-top: 15px; margin-bottom: 5px; display: block; border-bottom: 1px solid #ddd; }

        /* Form */
        input[type=number], select { width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; background-color: #007BFF; color: white; padding: 10px; margin: 8px 0; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .btn-parole { background: #6f42c1; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-size: 14px; display: inline-block; margin-top: 10px;}
        
        /* History Table */
        .hist-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .hist-table th, .hist-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .hist-table th { background-color: #f8f9fa; color: #333; }
        .badge-pending { background: #ffc107; color: #333; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-approved { background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<?php if(isset($_SESSION['flash_msg'])): ?>
    <script> alert("<?php echo $_SESSION['flash_msg']; ?>"); </script>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<div class="container">

    <!-- 1. MAIN STATS -->
    <div class="profile-card">
        <div class="header">
            <h2>Welcome, <?php echo htmlspecialchars($p_data['name']); ?></h2>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        <div class="card-body">
            <div class="stat-box">
                <span class="stat-label">Crime Type</span>
                <?php echo isset($crime_data['crime_type']) ? $crime_data['crime_type'] : 'N/A'; ?>
            </div>
            <div class="stat-box">
                <span class="stat-label">Sentence</span>
                <?php echo isset($sent_data['duration_in_months']) ? $sent_data['duration_in_months'] . " Months" : 'N/A'; ?>
            </div>
            <div class="stat-box">
                <span class="stat-label">Points</span>
                <span style="font-size: 1.2em; font-weight: bold; color: #333;"><?php echo $p_data['total_points']; ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Status</span>
                <span class="status-<?php echo $p_data['current_status']; ?>"><?php echo $p_data['current_status']; ?></span>
            </div>
            <div style="clear:both; border-top: 1px solid #eee; padding-top: 15px;">
                <a href="prisoner_parole.php" class="btn-parole">🔍 Check Parole Status</a>
            </div>
        </div>
    </div>

    <!-- 2. PERSONAL INFORMATION (NEW) -->
    <div class="profile-card">
        <div class="header"><h2>My Personal File</h2></div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <span class="sub-header">Identification</span>
                    <table class="info-table">
                        <tr><td class="lbl">Prisoner ID:</td><td class="val"><?php echo $p_data['prisoner_id']; ?></td></tr>
                        <tr><td class="lbl">Date of Birth:</td><td class="val"><?php echo $p_data['dob']; ?></td></tr>
                        <tr><td class="lbl">Gender:</td><td class="val"><?php echo $p_data['gender']; ?></td></tr>
                        <tr><td class="lbl">Height/Weight:</td><td class="val"><?php echo $p_data['height_cm']." cm / ".$p_data['weight_kg']." kg"; ?></td></tr>
                        <tr><td class="lbl">Blood Group:</td><td class="val"><?php echo $p_data['blood_group']; ?></td></tr>
                        <tr><td class="lbl">Appearance:</td><td class="val"><?php echo "Eyes: ".$p_data['eye_color'].", Hair: ".$p_data['hair_color']; ?></td></tr>
                    </table>
                </div>
                <div>
                    <span class="sub-header">Family & Contact</span>
                    <table class="info-table">
                        <tr><td class="lbl">Father:</td><td class="val"><?php echo isset($p_data['father_name']) ? $p_data['father_name'] : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Mother:</td><td class="val"><?php echo isset($p_data['mother_name']) ? $p_data['mother_name'] : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Emergency:</td><td class="val"><?php echo isset($p_data['emergency_contact_name']) ? $p_data['emergency_contact_name']." (".$p_data['emergency_contact_no'].")" : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Home:</td><td class="val"><?php echo isset($p_data['permanent_address']) ? $p_data['permanent_address'] : 'N/A'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. REQUEST DUTY -->
    <div class="profile-card">
        <div class="header"><h2>Request Duty / Log Work</h2></div>
        <div class="card-body">
            <form method="post">
                <label>Select Duty Type:</label>
                <select name="duty_id" required>
                    <?php
                    $d_res = $conn->query("SELECT * FROM duty");
                    while($d = $d_res->fetch_assoc()) {
                        echo "<option value='{$d['duty_id']}'>{$d['duty_name']} (Req: {$d['required_hours_per_date']} hrs)</option>";
                    }
                    ?>
                </select>
                <label>Hours Worked:</label>
                <input type="number" name="hours" placeholder="e.g., 5" required min="1" max="12">
                <button type="submit" name="submit_work">Submit Request</button>
            </form>
        </div>
    </div>

    <!-- 4. HISTORY -->
    <div class="profile-card">
        <div class="header"><h2>My Duty History</h2></div>
        <div class="card-body">
            <table class="hist-table">
                <tr><th>Duty Name</th><th>Hours</th><th>Status</th></tr>
                <?php
                $hist = $conn->query("SELECT da.*, d.duty_name FROM duty_assignment da JOIN duty d ON da.duty_id = d.duty_id WHERE da.prisoner_id='$pid' ORDER BY da.assignment_id DESC");
                if ($hist->num_rows > 0) {
                    while($h = $hist->fetch_assoc()){
                        $badge_class = ($h['status'] == 'Pending') ? 'badge-pending' : 'badge-approved';
                        echo "<tr><td>{$h['duty_name']}</td><td>{$h['hours_assigned']}</td><td><span class='$badge_class'>{$h['status']}</span></td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; color:#777;'>No duty history found.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

</div>
</body>
</html>