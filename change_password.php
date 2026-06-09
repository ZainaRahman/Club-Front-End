<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = false;

if (isset($_POST['submit'])) {
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($current_password)) {
        $errors[] = 'Current password cannot be empty';
    }
    if (empty($new_password)) {
        $errors[] = 'New password cannot be empty';
    }
    if (empty($confirm_password)) {
        $errors[] = 'Please confirm your new password';
    }

    if (!empty($new_password) && !empty($confirm_password) && $new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match';
    }

    if (!empty($new_password) && !empty($current_password) && $new_password === $current_password) {
        $errors[] = 'New password must be different from the current password';
    }

    if (empty($errors)) {
        $connection = mysqli_connect('localhost', 'root', '', 'club_db');

        if (!$connection) {
            $errors[] = 'Connection Failed: ' . mysqli_connect_error();
        } else {
            $email = $_SESSION['email'];
            $current_password_query = 'SELECT password FROM club_table WHERE email = ? LIMIT 1';
            $statement = mysqli_prepare($connection, $current_password_query);

            if (!$statement) {
                $errors[] = 'Query Failed: ' . mysqli_error($connection);
            } else {
                mysqli_stmt_bind_param($statement, 's', $email);
                mysqli_stmt_execute($statement);
                $result = mysqli_stmt_get_result($statement);

                if ($result && mysqli_num_rows($result) === 1) {
                    $row = mysqli_fetch_assoc($result);

                     if (!password_verify($current_password, $row['password'])) {
                        $errors[] = 'Current password is incorrect';
                    } else {
                        $update_query = 'UPDATE club_table SET password = ? WHERE email = ?';
                        $update_statement = mysqli_prepare($connection, $update_query);

                        if (!$update_statement) {
                            $errors[] = 'Update Failed: ' . mysqli_error($connection);
                        } else {
                            $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                            mysqli_stmt_bind_param($update_statement, 'ss', $hashed_new_password, $email);
                            if (mysqli_stmt_execute($update_statement)) {
                                $success = true;
                                mysqli_stmt_close($update_statement);
                                mysqli_stmt_close($statement);
                                mysqli_close($connection);
                                header('Location: Landing_page.php?password_changed=true');
                                exit();
                            }

                            $errors[] = 'Update Failed: ' . mysqli_stmt_error($update_statement);
                            mysqli_stmt_close($update_statement);
                        }
                    }
                } else {
                    $errors[] = 'User record not found';
                }

                mysqli_stmt_close($statement);
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
    <title>K-MiNDS | Change Password</title>
    <style>
        :root {
            --primary-color: #092a4b;
            --accent-color: #3498db;
            --accent-dark: #2980b9;
            --light-bg: #eef6fc;
            --text-color: #1e3145;
            --gray-text: #6d7f92;
            --shadow: 0 18px 50px rgba(9, 42, 75, 0.14);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            padding: 24px;
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
            background: rgba(255, 255, 255, 0.96);
            color: var(--primary-color);
            border-bottom: 1px solid rgba(9, 42, 75, 0.08);
        }

        .card-header p {
            margin-top: 8px;
            color: var(--gray-text);
            line-height: 1.6;
        }

        .card-body {
            padding: 28px;
        }

        .notice,
        .error-box {
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .error-box {
            background: #fff1f1;
            border: 1px solid #f2c6c6;
            color: #a33;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--primary-color);
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #dce8f3;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 180ms ease, box-shadow 180ms ease;
        }

        input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.16);
        }

        .helper {
            margin-top: 8px;
            color: var(--gray-text);
            font-size: 0.9rem;
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
            padding: 0 18px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

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
            margin-top: 16px;
            text-align: center;
        }

        .footer-link a {
            color: var(--accent-dark);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .card-body,
            .card-header {
                padding: 22px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="card">
        <header class="card-header">
            <h1>Change Password</h1>
            <p>Update your account password. The new password must be different from your current one.</p>
        </header>

        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <?php foreach ($errors as $error): ?>
                        <div>✗ <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="change_password.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="helper">Choose a password that is different from your current one.</div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="actions">
                    <a class="btn btn-secondary" href="Landing_page.php">Back to Home</a>
                    <button type="submit" name="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
               
        </div>
    </main>
</body>
</html>
