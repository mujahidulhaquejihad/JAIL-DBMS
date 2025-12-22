<?php
session_start();
include 'db.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// --- FILTER & SEARCH LOGIC ---
$sql_query = "SELECT * FROM prisoner WHERE 1=1";
$filter_mode = "All";
$search_term = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $sql_query .= " AND (prisoner_id = '$search_term' OR name LIKE '%$search_term%')";
    $filter_mode = "Search Results: '$search_term'";
}
elseif (isset($_GET['filter']) && $_GET['filter'] == 'pending') {
    $sql_query = "SELECT DISTINCT p.* FROM prisoner p 
                  JOIN duty_assignment da ON p.prisoner_id = da.prisoner_id 
                  WHERE da.status = 'Pending'";
    $filter_mode = "Pending Approvals";
}
// NEW: Filter for Parole Requests
elseif (isset($_GET['filter']) && $_GET['filter'] == 'parole_req') {
    $sql_query = "SELECT DISTINCT p.* FROM prisoner p 
                  JOIN parole_requests pr ON p.prisoner_id = pr.prisoner_id 
                  WHERE pr.status = 'Pending'";
    $filter_mode = "Parole Requests";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { margin: 0; color: #333; }
        
        .btn-add { background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-add:hover { background: #218838; }
        .btn-logout { color: #dc3545; text-decoration: none; font-weight: bold; }
        
        .tools-bar { 
            display: flex; justify-content: space-between; align-items: center; 
            background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #eee;
        }
        .filter-links a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .filter-links a.active { font-weight: bold; color: #0056b3; text-decoration: underline; }

        .search-form { display: flex; gap: 5px; }
        .search-input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 200px; }
        .btn-search { background: #333; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-clear { background: #6c757d; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-size: 13.3px; display: inline-block; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #343a40; color: white; }
        
        .btn-profile { background: #007BFF; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .badge-alert { background: #dc3545; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: 5px; }
        
        /* Evaluate Button Style */
        .btn-eval {
            background-color: #6f42c1; color: white; border: none; padding: 6px 12px; 
            border-radius: 4px; cursor: pointer; transition: 0.3s; text-decoration: none; font-size: 13px;
        }
        .btn-eval:hover { background-color: #59359a; }
        .req-flag { color: red; font-weight: bold; font-size: 12px; display: block; margin-bottom: 2px;}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Admin Dashboard</h1>
        <div>
            <a href="add_prisoner.php" class="btn-add">+ Add New Prisoner</a>
            <span style="margin: 0 10px;">|</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="tools-bar">
        <div class="filter-links">
            <strong>View:</strong>
            <a href="admin_dashboard.php" class="<?php if($filter_mode=='All') echo 'active'; ?>">Show All</a>
            <a href="admin_dashboard.php?filter=pending" class="<?php if($filter_mode=='Pending Approvals') echo 'active'; ?>">⚠ Pending Approvals</a>
            <a href="admin_dashboard.php?filter=parole_req" class="<?php if($filter_mode=='Parole Requests') echo 'active'; ?>">📩 Parole Requests</a>
        </div>

        <form class="search-form" method="get" action="admin_dashboard.php">
            <input type="text" name="search" class="search-input" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn-search">Search</button>
            <?php if(!empty($search_term)): ?>
                <a href="admin_dashboard.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Cell</th>
            <th>Points</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        $result = $conn->query($sql_query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pid = $row['prisoner_id'];
                
                // Check for pending duty assignments
                $pend_q = $conn->query("SELECT COUNT(*) as cnt FROM duty_assignment WHERE prisoner_id='$pid' AND status='Pending'");
                $pending_count = $pend_q->fetch_assoc()['cnt'];

                // Check for parole requests
                $req_q = $conn->query("SELECT COUNT(*) as cnt FROM parole_requests WHERE prisoner_id='$pid' AND status='Pending'");
                $req_count = $req_q->fetch_assoc()['cnt'];

                echo "<tr>
                    <td>{$row['prisoner_id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['cell_no']}</td>
                    <td>{$row['total_points']}</td>
                    <td><b>{$row['current_status']}</b></td>
                    <td>
                        <a href='prisoner_profile.php?id={$row['prisoner_id']}' class='btn-profile'>
                            View Profile
                            " . ($pending_count > 0 ? "<span class='badge-alert'>$pending_count</span>" : "") . "
                        </a>
                        
                        <div style='display:inline-block; margin-left: 10px; vertical-align: middle;'>
                            " . ($req_count > 0 ? "<span class='req-flag'>Requesting Parole</span>" : "") . "
                            <a href='evaluate_prisoner.php?id={$row['prisoner_id']}' class='btn-eval'>Run Evaluation</a>
                        </div>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6' style='text-align:center; padding:20px; color: #777;'>No prisoners found matching your criteria.</td></tr>";
        }
        ?>
    </table>
</div>

</body>
</html>