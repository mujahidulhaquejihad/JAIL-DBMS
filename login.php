<?php
session_start();
include 'db.php';

$error_msg = ""; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prevent SQL Injection (Basic protection)
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query 'user_account' table
    $sql = "SELECT * FROM user_account WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role'] = $row['role'];
        
        if ($row['role'] == 'admin') {
            header("Location: admin_dashboard.php");
            exit(); // <--- CRITICAL FIX: Stops script execution here
        } else {
            header("Location: prisoner_dashboard.php");
            exit(); // <--- CRITICAL FIX: Stops script execution here
        }
    } else {
        $error_msg = "Invalid Username or Password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>JDBMS Login</title>
    <style>
        /* General Page Reset */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full viewport height */
        }

        /* Login Card Container */
        .login-card {
            background: white;
            width: 350px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        /* Header Style */
        .login-header {
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .login-header h2 { margin: 0; font-size: 22px; font-weight: 500; }
        .login-header p { margin: 5px 0 0; font-size: 12px; color: #ccc; }

        /* Form Body */
        .login-body { padding: 30px; }

        /* Input Fields */
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; font-size: 14px; }
        
        input[type=text], input[type=password] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't break width */
        }

        /* Login Button */
        button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background-color: #0056b3; }

        /* Error Message Box */
        .error-box {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-header">
            <h2>JDBMS Access</h2>
            <p>Jail Database Management System</p>
        </div>
        
        <div class="login-body">
            
            <?php if(!empty($error_msg)): ?>
                <div class="error-box"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="post">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>

                <button type="submit">Secure Login</button>
            </form>
        </div>
    </div>

</body>
</html>