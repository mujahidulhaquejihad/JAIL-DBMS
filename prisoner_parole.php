<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prisoner') { 
    header("Location: index.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];
$p_data = $conn->query("SELECT * FROM prisoner WHERE user_id='$uid'")->fetch_assoc();

// Check if prisoner data exists
if (!$p_data) {
    echo "Error: Prisoner profile not found.";
    exit();
}

$pid = $p_data['prisoner_id'];
$s_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid'")->fetch_assoc();
$c_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid'")->fetch_assoc();

// --- LOGIC CALCULATION (Read-Only) ---
$start_date = new DateTime($s_data['start_date']);
$today = new DateTime();
$interval = $start_date->diff($today);
$months_served = ($interval->y * 12) + $interval->m;
$total_duration = $s_data['duration_in_months'];
$percent_served = ($total_duration > 0) ? ($months_served / $total_duration) * 100 : 0;

$severity = $c_data['severity_level'];
$req_points = 50; $req_time_pct = 50;
switch ($severity) {
    case 'Low': $req_points = 60; $req_time_pct = 30; break;
    case 'Medium': $req_points = 70; $req_time_pct = 50; break;
    case 'Dangerous': $req_points = 85; $req_time_pct = 75; break;
    case 'Extremely Dangerous (Be Cautious)': $req_points = 95; $req_time_pct = 90; break;
}

$eligible = true;
if($p_data['total_points'] < $req_points) $eligible = false;
if($percent_served < $req_time_pct) $eligible = false;
if($s_data['parole_eligibility'] == 0) $eligible = false;

// --- HANDLE REQUEST ---
$msg = "";
if(isset($_POST['request_review'])) {
    // Check if pending request exists
    $check = $conn->query("SELECT * FROM parole_requests WHERE prisoner_id='$pid' AND status='Pending'");
    if($check->num_rows == 0) {
        $conn->query("INSERT INTO parole_requests (prisoner_id) VALUES ('$pid')");
        $msg = "Request submitted to Administration.";
    } else {
        $msg = "You already have a pending request.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parole Status</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        
        .status-box { padding: 20px; border-radius: 6px; text-align: center; margin-bottom: 20px; color: white; }
        .eligible { background: #28a745; }
        .not-eligible { background: #dc3545; }
        
        .metric { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .metric-title { font-weight: bold; color: #555; }
        .metric-val { float: right; font-weight: bold; }
        .pass { color: green; }
        .fail { color: red; }
        
        .btn-req { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .btn-req:disabled { background: #ccc; cursor: not-allowed; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>My Parole Status</h1>
    
    <div class="status-box <?php echo $eligible ? 'eligible' : 'not-eligible'; ?>">
        <h2><?php echo $eligible ? "YOU ARE ELIGIBLE" : "NOT YET ELIGIBLE"; ?></h2>
        <p><?php echo $eligible ? "You meet the criteria for a parole hearing." : "You must meet all requirements below."; ?></p>
    </div>

    <?php if(!empty($msg)) echo "<p style='color:blue; text-align:center;'>$msg</p>"; ?>

    <div class="metric">
        <span class="metric-title">1. Behavior Points (Target: <?php echo $req_points; ?>)</span>
        <span class="metric-val <?php echo ($p_data['total_points'] >= $req_points) ? 'pass' : 'fail'; ?>">
            <?php echo $p_data['total_points']; ?>
        </span>
    </div>
    
    <div class="metric">
        <span class="metric-title">2. Time Served (Target: <?php echo $req_time_pct; ?>%)</span>
        <span class="metric-val <?php echo ($percent_served >= $req_time_pct) ? 'pass' : 'fail'; ?>">
            <?php echo round($percent_served, 1); ?>%
        </span>
    </div>

    <form method="post">
        <?php if($eligible): ?>
            <button type="submit" name="request_review" class="btn-req">Request Parole Review</button>
        <?php else: ?>
             <button type="button" disabled class="btn-req" style="background:#ccc; cursor:not-allowed;">Requirements Not Met</button>
        <?php endif; ?>
    </form>
    
    <a href="prisoner_dashboard.php" class="btn-back">Back to Dashboard</a>
</div>

</body>
</html>