<?php
$errors = [];
$success = false;

if (isset($_POST['submit'])) {
    $username = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm']) ? $_POST['confirm'] : '';

    // Validation: Empty fields
    if (empty($username)) {
        $errors[] = "Full Name cannot be empty";
    }
    if (empty($email)) {
        $errors[] = "Email cannot be empty";
    }
    if (empty($password)) {
        $errors[] = "Password cannot be empty";
    }
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password";
    }

    // Validation: Password confirmation
    if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Validation: Password structure (min 8 chars, at least 1 uppercase, 1 lowercase, 1 number)
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
    }

    // Validation: Email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // If no errors, check database
    if (empty($errors)) {
        $connection = mysqli_connect("localhost", "root", "", "club_db");

        if (!$connection) {
            $errors[] = "Connection Failed: " . mysqli_connect_error();
        } else {
            // Check if username already exists
            $check_username_query = "SELECT username FROM club_table WHERE username = '$username'";
            $check_result = mysqli_query($connection, $check_username_query);

            if (!$check_result) {
                $errors[] = "Query Failed: " . mysqli_error($connection);
            } elseif (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Username already exists. Please choose a different name";
            } else {
                // Check if email already exists
                $check_email_query = "SELECT email FROM club_table WHERE email = '$email'";
                $check_email_result = mysqli_query($connection, $check_email_query);

                if (!$check_email_result) {
                    $errors[] = "Query Failed: " . mysqli_error($connection);
                } elseif (mysqli_num_rows($check_email_result) > 0) {
                    $errors[] = "Email already registered. Please use a different email";
                } else {
                    // All validations passed, insert into database
                    $insert_query = "INSERT INTO club_table (username, email, password) ";
                    $insert_query .= "VALUES ('$username', '$email', '$password')";
                    $insert = mysqli_query($connection, $insert_query);

                    if (!$insert) {
                        $errors[] = "Registration Failed: " . mysqli_error($connection);
                    } else {
                        $success = true;
                        mysqli_close($connection);
                        header("Location: Landing_page.php?signup_success=true");
                        exit();
                    }
                }
            }

            mysqli_close($connection);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
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
        <h2>Create an Account</h2>

        <?php
        if (!empty($errors)) {
            echo "<div class='error-box'>";
            foreach ($errors as $error) {
                echo "<p>✗ " . htmlspecialchars($error) . "</p>";
            }
            echo "</div>";
        }
        ?>

        <form action="signup.php" method="POST">
            <div class="form-group">
                <label for="signup-name">Full Name:</label>
                <input type="text" id="signup-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="signup-email">Email:</label>
                <input type="email" id="signup-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="signup-password">Password:</label>
                <input type="password" id="signup-password" name="password" required>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Must have: 8+ characters, 1 uppercase, 1 lowercase, 1 number
                </small>
            </div>
            <div class="form-group">
                <label for="signup-confirm">Confirm Password:</label>
                <input type="password" id="signup-confirm" name="confirm" required>
            </div>
            <button type="submit" name="submit" class="form-btn">Sign Up</button>
        </form>
        <p class="toggle-form">Already have an account? <a href="Landing_page.html">Login</a></p>
    </div>
</body>
</html>
