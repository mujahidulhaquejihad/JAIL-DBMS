<?php
session_start();
include 'db.php';

// Security: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: index.php"); 
    exit(); 
}

$pid = $_GET['id'];
$curr_user_id = $_SESSION['user_id'];

// Get Current Admin ID
$admin_res = $conn->query("SELECT admin_id FROM admin WHERE user_id='$curr_user_id'");
$curr_admin_id = $admin_res->fetch_assoc()['admin_id'];

// Fetch Prisoner, Sentence, Crime
$p_data = $conn->query("SELECT * FROM prisoner WHERE prisoner_id='$pid'")->fetch_assoc();
$s_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid'")->fetch_assoc();
$c_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid'")->fetch_assoc();

// --- CALCULATE METRICS ---

// 1. Time Served %
$start_date = new DateTime($s_data['start_date']);
$today = new DateTime();
$interval = $start_date->diff($today);
$months_served = ($interval->y * 12) + $interval->m;
$total_duration = $s_data['duration_in_months'];
$percent_served = ($total_duration > 0) ? ($months_served / $total_duration) * 100 : 0;

// 2. Thresholds
$severity = $c_data['severity_level'];
$req_points = 50; 
$req_time_pct = 50; 

switch ($severity) {
    case 'Low': $req_points = 60; $req_time_pct = 30; break;
    case 'Medium': $req_points = 70; $req_time_pct = 50; break;
    case 'Dangerous': $req_points = 85; $req_time_pct = 75; break;
    case 'Extremely Dangerous (Be Cautious)': $req_points = 95; $req_time_pct = 90; break;
}

// 3. Evaluation Decision
$decision = "Normal";
$reasons = [];
$is_eligible = true;

if ($p_data['total_points'] <= 20) {
    $decision = "Isolated";
    $reasons[] = "CRITICAL: Behavior points below 20.";
    $is_eligible = false;
} else {
    if ($s_data['parole_eligibility'] == 0) {
        $reasons[] = "FAIL: Sentence type allows no parole.";
        $is_eligible = false;
    }
    if ($p_data['total_points'] < $req_points) {
        $reasons[] = "FAIL: Points ({$p_data['total_points']}) < Required ($req_points).";
        $is_eligible = false;
    }
    if ($percent_served < $req_time_pct) {
        $reasons[] = "FAIL: Time Served (".round($percent_served)."%) < Required ($req_time_pct%).";
        $is_eligible = false;
    }
    
    if ($is_eligible) {
        $decision = "Paroled";
        $reasons[] = "PASS: All criteria met for $severity crime level.";
    }
}

// --- HANDLE CONFIRMATION (PRG PATTERN FIXED) ---
if (isset($_POST['confirm_eval'])) {
    $final_decision = $_POST['decision'];
    $comments = $_POST['comments'];
    
    // Update Prisoner
    $conn->query("UPDATE prisoner SET current_status='$final_decision' WHERE prisoner_id='$pid'");
    
    // Log Evaluation
    $stmt = $conn->prepare("INSERT INTO parole_evaluation (prisoner_id, admin_id, points_at_evaluation, decision, comments) VALUES (?, ?, ?, ?, ?)");
    $points = $p_data['total_points'];
    $stmt->bind_param("siiss", $pid, $curr_admin_id, $points, $final_decision, $comments);
    $stmt->execute();
    
    // Clear any pending requests
    $conn->query("UPDATE parole_requests SET status='Reviewed' WHERE prisoner_id='$pid' AND status='Pending'");

    // SET SESSION MESSAGE & REDIRECT TO SELF
    $_SESSION['flash_msg'] = "Evaluation Confirmed! Status updated to $final_decision.";
    header("Location: evaluate_prisoner.php?id=$pid");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Evaluate Prisoner</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .box { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ddd; }
        .label { font-size: 12px; color: #666; font-weight: bold; text-transform: uppercase; }
        .value { font-size: 18px; font-weight: bold; color: #333; margin-top: 5px; }
        
        .metric-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; align-items: center; }
        .status-pass { color: green; font-weight: bold; }
        .status-fail { color: red; font-weight: bold; }
        
        .recommendation { background: #e9ecef; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 5px solid #333; }
        .rec-label { display: block; font-weight: bold; margin-bottom: 5px; }
        
        .btn-confirm { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 4px; font-size: 16px; cursor: pointer; width: 100%; }
        .btn-confirm:hover { background: #218838; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
</head>
<body>

<!-- SHOW SUCCESS MESSAGE -->
<?php if(isset($_SESSION['flash_msg'])): ?>
    <script>
        alert("<?php echo $_SESSION['flash_msg']; ?>");
    </script>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<div class="container">
    <h1>Parole Evaluation: <?php echo $p_data['name']; ?></h1>
    
    <div class="grid">
        <div class="box">
            <div class="label">Crime Severity</div>
            <div class="value"><?php echo $severity; ?></div>
        </div>
        <div class="box">
            <div class="label">Current Status</div>
            <div class="value"><?php echo $p_data['current_status']; ?></div>
        </div>
    </div>

    <h3>Metrics Analysis</h3>
    <div class="metric-row">
        <span><strong>1. Behavior Points</strong> (Req: <?php echo $req_points; ?>)</span>
        <span class="<?php echo ($p_data['total_points'] >= $req_points) ? 'status-pass' : 'status-fail'; ?>">
            <?php echo $p_data['total_points']; ?> 
            <?php echo ($p_data['total_points'] >= $req_points) ? '✔ PASS' : '✖ FAIL'; ?>
        </span>
    </div>
    <div class="metric-row">
        <span><strong>2. Time Served</strong> (Req: <?php echo $req_time_pct; ?>%)</span>
        <span class="<?php echo ($percent_served >= $req_time_pct) ? 'status-pass' : 'status-fail'; ?>">
            <?php echo round($percent_served, 1); ?>% 
            <?php echo ($percent_served >= $req_time_pct) ? '✔ PASS' : '✖ FAIL'; ?>
        </span>
    </div>
    <div class="metric-row">
        <span><strong>3. Parole Eligibility</strong> (Sentence Type)</span>
        <span class="<?php echo ($s_data['parole_eligibility'] == 1) ? 'status-pass' : 'status-fail'; ?>">
            <?php echo ($s_data['parole_eligibility'] == 1) ? '✔ ELIGIBLE' : '✖ INELIGIBLE'; ?>
        </span>
    </div>

    <form method="post">
        <div class="recommendation" style="border-color: <?php echo $is_eligible ? 'green' : 'red'; ?>;">
            <span class="rec-label">System Recommendation:</span>
            <strong style="font-size: 20px; color: <?php echo $is_eligible ? 'green' : 'red'; ?>">
                <?php echo $decision; ?>
            </strong>
            <ul style="margin-top: 5px; padding-left: 20px; color: #555;">
                <?php foreach($reasons as $r) echo "<li>$r</li>"; ?>
            </ul>
        </div>

        <label style="font-weight:bold;">Final Decision:</label>
        <select name="decision" style="width:100%; padding:10px; margin-bottom:10px;">
            <option value="Normal" <?php if($decision=='Normal') echo 'selected'; ?>>Normal (Deny Parole)</option>
            <option value="Paroled" <?php if($decision=='Paroled') echo 'selected'; ?>>Parole Granted</option>
            <option value="Isolated" <?php if($decision=='Isolated') echo 'selected'; ?>>Isolate Prisoner</option>
        </select>

        <label style="font-weight:bold;">Comments / Reason:</label>
        <textarea name="comments" rows="3" style="width:100%; padding:10px; margin-bottom:20px;"><?php echo implode(", ", $reasons); ?></textarea>

        <button type="submit" name="confirm_eval" class="btn-confirm">Confirm Decision</button>
    </form>
    
    <a href="admin_dashboard.php" class="btn-back">Cancel</a>
</div>

</body>
</html>