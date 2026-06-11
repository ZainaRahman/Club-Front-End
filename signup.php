<?php
session_start();
$errors = [];
$success = false;

if (isset($_POST['submit'])) {
    $username         = isset($_POST['name'])     ? trim($_POST['name'])     : '';
    $email            = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $password         = isset($_POST['password']) ? $_POST['password']       : '';
    $confirm_password = isset($_POST['confirm'])  ? $_POST['confirm']        : '';
    $role             = isset($_POST['role'])     ? $_POST['role']           : 'member';

   
    if (!in_array($role, ['member', 'admin'], true)) {
        $role = 'member';
    }

    if (empty($username))         $errors[] = "Full Name cannot be empty";
    if (empty($email))            $errors[] = "Email cannot be empty";
    if (empty($password))         $errors[] = "Password cannot be empty";
    if (empty($confirm_password)) $errors[] = "Please confirm your password";

    if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password)
        $errors[] = "Passwords do not match";

    if (!empty($password)) {
        if (strlen($password) < 8)              $errors[] = "Password must be at least 8 characters long";
        if (!preg_match('/[A-Z]/', $password))  $errors[] = "Password must contain at least one uppercase letter";
        if (!preg_match('/[a-z]/', $password))  $errors[] = "Password must contain at least one lowercase letter";
        if (!preg_match('/[0-9]/', $password))  $errors[] = "Password must contain at least one number";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format";

    if (empty($errors)) {
        $connection = mysqli_connect("localhost", "root", "", "club_db");

        if (!$connection) {
            $errors[] = "Connection Failed: " . mysqli_connect_error();
        } else {
            $safe_username = mysqli_real_escape_string($connection, $username);
            $safe_email    = mysqli_real_escape_string($connection, $email);

            $check_username = mysqli_query($connection, "SELECT username FROM club_table WHERE username = '$safe_username'");
            if (!$check_username) {
                $errors[] = "Query Failed: " . mysqli_error($connection);
            } elseif (mysqli_num_rows($check_username) > 0) {
                $errors[] = "Username already exists. Please choose a different name";
            } else {
                $check_email = mysqli_query($connection, "SELECT email FROM club_table WHERE email = '$safe_email'");
                if (!$check_email) {
                    $errors[] = "Query Failed: " . mysqli_error($connection);
                } elseif (mysqli_num_rows($check_email) > 0) {
                    $errors[] = "Email already registered. Please use a different email";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $safe_role       = mysqli_real_escape_string($connection, $role);
                    $insert = mysqli_query($connection,
                        "INSERT INTO club_table (username, email, password, role)
                         VALUES ('$safe_username', '$safe_email', '$hashed_password', '$safe_role')"
                    );
                    if (!$insert) {
                        $errors[] = "Registration Failed: " . mysqli_error($connection);
                    } else {
                        mysqli_close($connection);
                        ?>
                        <script>
                            if (window.parent !== window) {
                                window.parent.postMessage({ type: 'signup_success' }, window.location.origin);
                            } else {
                                window.location.href = 'Landing_page.php?signup_success=true';
                            }
                        </script>
                        <?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-MiNDS | Create Account</title>
    <link rel="icon" type="image/png" href="logo.png">
    <style>
        :root {
            --primary-color: #092a4b;
            --accent-color: #3498db;
            --accent-dark: #2980b9;
            --text-color: #1e3145;
            --gray-text: #6d7f92;
            --shadow: 0 18px 50px rgba(9, 42, 75, 0.14);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(52, 152, 219, 0.16), transparent 30%),
                linear-gradient(180deg, #f8fcff 0%, #eef5fb 100%);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .card {
            width: 100%;
            max-width: 560px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(9, 42, 75, 0.08);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .card-header {
            padding: 28px 28px 22px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a5799 100%);
            color: white;
        }

        .card-header h1 {
            font-size: 1.65rem;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }

        .card-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .card-body { padding: 28px; }

        .error-box {
            background: #fff1f1;
            border: 1px solid #f2c6c6;
            color: #a33;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 0.95rem;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #dce8f3;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            color: var(--text-color);
            transition: border-color 180ms ease, box-shadow 180ms ease;
            appearance: none;
            -webkit-appearance: none;
        }

        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23092a4b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.16);
        }

        .helper {
            margin-top: 7px;
            color: var(--gray-text);
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* Role selector pills */
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 4px;
        }

        .role-option { position: relative; }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .role-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 14px 10px;
            border: 2px solid #dce8f3;
            border-radius: 14px;
            background: white;
            cursor: pointer;
            transition: border-color 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
            font-weight: 700;
            text-align: center;
            color: var(--gray-text);
            font-size: 0.95rem;
        }

        .role-option label .role-icon {
            font-size: 1.5rem;
            line-height: 1;
        }

        .role-option label .role-desc {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--gray-text);
        }

        .role-option input[type="radio"]:checked + label {
            border-color: var(--accent-color);
            background: rgba(52, 152, 219, 0.07);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.12);
            color: var(--primary-color);
        }

        .role-option input[type="radio"]:checked + label .role-desc {
            color: var(--accent-dark);
        }

        .role-option label:hover {
            border-color: rgba(52, 152, 219, 0.5);
            background: rgba(52, 152, 219, 0.04);
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 22px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            font-size: 0.98rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }

        .btn:hover { transform: translateY(-2px); }

        .btn-secondary {
            background: #eef5fb;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), var(--accent-dark));
            color: white;
            box-shadow: 0 12px 24px rgba(52, 152, 219, 0.22);
        }

        .footer-link {
            margin-top: 18px;
            text-align: center;
            color: var(--gray-text);
            font-size: 0.93rem;
        }

        .footer-link a {
            color: var(--accent-dark);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-link a:hover { text-decoration: underline; }

        @media (max-width: 560px) {
            .card-body, .card-header { padding: 22px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<main class="card">
    <header class="card-header">
        <h1>Create Account</h1>
        <p>Join K-MiNDS — fill in your details and choose your role below.</p>
    </header>

    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $e): ?>
                    <div>✗ <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">

            <div class="form-group">
                <label for="signup-name">Full Name</label>
                <input type="text" id="signup-name" name="name"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="signup-email">Email Address</label>
                <input type="email" id="signup-email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="password" required>
                <div class="helper">Minimum 8 characters · 1 uppercase · 1 lowercase · 1 number</div>
            </div>

            <div class="form-group">
                <label for="signup-confirm">Confirm Password</label>
                <input type="password" id="signup-confirm" name="confirm" required>
            </div>

            <div class="form-group">
                <label>Account Role</label>
                <div class="role-selector">
                    <div class="role-option">
                        <input type="radio" id="role-member" name="role" value="member"
                            <?= (($_POST['role'] ?? 'member') === 'member') ? 'checked' : '' ?>>
                        <label for="role-member">
                            <span class="role-icon">🎓</span>
                            Member
                            <span class="role-desc">Standard club access</span>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="role-admin" name="role" value="admin"
                            <?= (($_POST['role'] ?? '') === 'admin') ? 'checked' : '' ?>>
                        <label for="role-admin">
                            <span class="role-icon">🛡️</span>
                            Admin
                            <span class="role-desc">Full management access</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" name="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>

        <p class="footer-link">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</main>
</body>
</html>