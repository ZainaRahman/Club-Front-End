<?php
session_start();

$errors = [];

if (isset($_POST['submit'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

  
    if (empty($email)) {
        $errors[] = "Email cannot be empty";
    }
    if (empty($password)) {
        $errors[] = "Password cannot be empty";
    }

    
    if (empty($errors)) {
        $connection = mysqli_connect("localhost", "root", "", "club_db");

        if (!$connection) {
            $errors[] = "Connection Failed: " . mysqli_connect_error();
        } else {
           
            $safe_email  = mysqli_real_escape_string($connection, $email);
            $login_query = "SELECT username, email, password, role FROM club_table WHERE email = '$safe_email' LIMIT 1";
            $login_result = mysqli_query($connection, $login_query);

            if (!$login_result) {
                $errors[] = "Query Failed: " . mysqli_error($connection);
            } elseif (mysqli_num_rows($login_result) > 0) {
                $row = mysqli_fetch_assoc($login_result);
                if (password_verify($password, $row['password'])) {
                    
                    session_regenerate_id(true);
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['email']    = $row['email'];
                    $_SESSION['role']     = $row['role'] ?? 'member'; 
                    mysqli_close($connection);
                   
                    $redirectTo = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'Landing_page.php';
                    
                    ?>
                    <script>
                        if (window.parent !== window) {
                            window.parent.postMessage({ type: 'login_success', redirect: '<?= $redirectTo ?>' }, window.location.origin);
                        } else {
                            window.location.href = '<?= $redirectTo ?>';
                        }
                    </script>
                    <?php
                    exit();
                } else {
                    $errors[] = "Invalid Email or Password";
                }
            } else {
                $errors[] = "Invalid Email or Password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error-box {
            color: red;
            background-color: #f8d7da;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
        .error-box p {
            margin: 5px 0;
        }
        .toggle-form {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .toggle-form a {
            color: #4CAF50;
            text-decoration: none;
        }
        .toggle-form a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php
        if (!empty($errors)) {
            echo "<div class='error-box'>";
            foreach ($errors as $error) {
                echo "<p>✗ " . htmlspecialchars($error) . "</p>";
            }
            echo "</div>";
        }
        ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login-email">Email:</label>
                <input type="email" id="login-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="login-password">Password:</label>
                <input type="password" id="login-password" name="password" required>
            </div>
            <button type="submit" name="submit" class="form-btn">Login</button>
        </form>
        <p class="toggle-form">Don't have an account? <a href="signup.php">Sign Up</a></p>
    </div>
</body>
</html>