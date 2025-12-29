<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$pid = $_GET['id'];

// Fetch Existing Data
$p_data = $conn->query("SELECT * FROM prisoner WHERE prisoner_id='$pid'")->fetch_assoc();
if (!$p_data) {
    die("Prisoner not found.");
}
$c_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid'")->fetch_assoc();
$s_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid'")->fetch_assoc();

// --- HANDLE UPDATE ---
if (isset($_POST['update_prisoner'])) {
    // 1. Personal Basic
    $name = $_POST['fullname'];
    $dob = $_POST['dob'];
    $cell = $_POST['cell'];
    
    // 2. Family & Address
    $father = $_POST['father_name'];
    $mother = $_POST['mother_name'];
    $present = $_POST['present_address'];
    $permanent = $_POST['permanent_address'];

    // 3. Physical Attributes
    $gender = $_POST['gender'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $blood = $_POST['blood_group'];
    $eye = $_POST['eye_color'];
    $hair = $_POST['hair_color'];
    
    // 4. Emergency Contact
    $e_name = $_POST['e_name'];
    $e_no = $_POST['e_no'];
    
    // 5. Crime & Sentence
    $crime_type = $_POST['crime_type'];
    $severity = $_POST['severity']; 
    $crime_loc = $_POST['location'];
    $s_duration = $_POST['duration']; 
    $s_start = $_POST['start_date'];
    $s_eligibility = $_POST['eligibility']; 

    $conn->begin_transaction();
    try {
        // Update Prisoner Entity
        $stmt = $conn->prepare("UPDATE prisoner SET 
            name=?, dob=?, cell_no=?, 
            father_name=?, mother_name=?, present_address=?, permanent_address=?,
            gender=?, height_cm=?, weight_kg=?, blood_group=?, eye_color=?, hair_color=?, 
            emergency_contact_name=?, emergency_contact_no=?
            WHERE prisoner_id=?");
        
        $stmt->bind_param("ssssssssiiisssss", 
            $name, $dob, $cell, 
            $father, $mother, $present, $permanent,
            $gender, $height, $weight, $blood, $eye, $hair, 
            $e_name, $e_no, $pid
        );
        $stmt->execute();

        // Update Crime
        $stmt_crime = $conn->prepare("UPDATE crime SET crime_type=?, severity_level=?, location=? WHERE prisoner_id=?");
        $stmt_crime->bind_param("ssss", $crime_type, $severity, $crime_loc, $pid);
        $stmt_crime->execute();

        // Update Sentence
        $stmt_sent = $conn->prepare("UPDATE sentence SET duration_in_months=?, start_date=?, parole_eligibility=? WHERE prisoner_id=?");
        $stmt_sent->bind_param("isss", $s_duration, $s_start, $s_eligibility, $pid);
        $stmt_sent->execute();

        $conn->commit();
        echo "<script>alert('Prisoner Updated Successfully!'); window.location.href='prisoner_profile.php?id=$pid';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Prisoner: <?php echo $p_data['name']; ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { font-weight: bold; color: #555; display: block; margin-top: 10px; font-size: 14px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input[type=text], input[type=password], input[type=date], input[type=number], select, textarea {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .section-title { 
            grid-column: span 2; margin-top: 20px; border-bottom: 2px solid #eee; 
            padding-bottom: 5px; color: #007bff; font-weight: bold; font-size: 16px;
        }
        .btn-submit { grid-column: span 2; width: 100%; background: #007bff; color: white; padding: 12px; border: none; margin-top: 20px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background: #0056b3; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Edit Prisoner: <?php echo $p_data['name']; ?></h2>
        
        <form method="post" class="form-grid">
            
            <div class="section-title">Personal Information</div>
            <div><label>Full Name</label><input type="text" name="fullname" value="<?php echo $p_data['name']; ?>" required></div>
            <div><label>Date of Birth</label><input type="date" name="dob" value="<?php echo $p_data['dob']; ?>" required></div>
            <div>
                <label>Gender</label>
                <select name="gender" required>
                    <option value="Male" <?php if($p_data['gender']=='Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if($p_data['gender']=='Female') echo 'selected'; ?>>Female</option>
                    <option value="Other" <?php if($p_data['gender']=='Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div><label>Cell Block</label><input type="text" name="cell" value="<?php echo $p_data['cell_no']; ?>" required></div>

            <div class="section-title">Family & Address Information</div>
            <div><label>Father's Name</label><input type="text" name="father_name" value="<?php echo $p_data['father_name']; ?>" required></div>
            <div><label>Mother's Name</label><input type="text" name="mother_name" value="<?php echo $p_data['mother_name']; ?>" required></div>
            <div><label>Present Address</label><input type="text" name="present_address" value="<?php echo $p_data['present_address']; ?>" required></div>
            <div><label>Permanent Address</label><input type="text" name="permanent_address" value="<?php echo $p_data['permanent_address']; ?>" required></div>

            <div class="section-title">Physical Identification</div>
            <div><label>Height (cm)</label><input type="number" name="height" value="<?php echo $p_data['height_cm']; ?>" required></div>
            <div><label>Weight (kg)</label><input type="number" name="weight" value="<?php echo $p_data['weight_kg']; ?>" required></div>
            <div>
                <label>Blood Group</label>
                <select name="blood_group" required>
                    <?php 
                    $bgs = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                    foreach($bgs as $bg) {
                        $sel = ($p_data['blood_group'] == $bg) ? 'selected' : '';
                        echo "<option value='$bg' $sel>$bg</option>";
                    }
                    ?>
                </select>
            </div>
            <div><label>Eye Color</label><input type="text" name="eye_color" value="<?php echo $p_data['eye_color']; ?>" required></div>
            <div style="grid-column: span 2;"><label>Hair Color</label><input type="text" name="hair_color" value="<?php echo $p_data['hair_color']; ?>" required></div>

            <div class="section-title">Emergency Contact</div>
            <div><label>Guardian Name</label><input type="text" name="e_name" value="<?php echo $p_data['emergency_contact_name']; ?>" required></div>
            <div><label>Phone Number</label><input type="text" name="e_no" value="<?php echo $p_data['emergency_contact_no']; ?>" required></div>
            
            <div class="section-title">Crime & Sentencing</div>
            <div><label>Crime Type</label><input type="text" name="crime_type" value="<?php echo $c_data['crime_type']; ?>" required></div>
            <div>
                <label>Severity Level</label>
                <select name="severity" required>
                    <?php 
                    $sevs = ['Extremely Dangerous (Be Cautious)', 'Dangerous', 'Medium', 'Low'];
                    foreach($sevs as $sv) {
                        $sel = ($c_data['severity_level'] == $sv) ? 'selected' : '';
                        echo "<option value='$sv' $sel>$sv</option>";
                    }
                    ?>
                </select>
            </div>
            <div style="grid-column: span 2;"><label>Location of Crime</label><input type="text" name="location" value="<?php echo $c_data['location']; ?>" required></div>
            <div><label>Sentence Duration (Months)</label><input type="number" name="duration" value="<?php echo $s_data['duration_in_months']; ?>" required></div>
            <div><label>Start Date</label><input type="date" name="start_date" value="<?php echo $s_data['start_date']; ?>" required></div>
            <div style="grid-column: span 2;">
                <label>Parole Eligibility</label>
                <select name="eligibility" required>
                    <option value="1" <?php if($s_data['parole_eligibility']==1) echo 'selected'; ?>>Eligible for Parole</option>
                    <option value="0" <?php if($s_data['parole_eligibility']==0) echo 'selected'; ?>>No Parole (Ineligible)</option>
                </select>
            </div>
            
            <button type="submit" name="update_prisoner" class="btn-submit">Update Prisoner Record</button>
        </form>

        <a href="prisoner_profile.php?id=<?php echo $pid; ?>" class="btn-back">Cancel & Back to Profile</a>
    </div>

</body>
</html>