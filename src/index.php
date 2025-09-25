<?php
require_once 'functions.php';

$message = '';
$show_verification_form = false;
$current_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['action']) && $_POST['action'] === 'send_code') {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            // Check if already registered
            $file = __DIR__ . '/registered_emails.txt';
            if (file_exists($file)) {
                $registered_emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (in_array($email, $registered_emails)) {
                    $message = '<p style="color: blue;">Email is already registered. You can unsubscribe if needed.</p>';
                }
            }

            if (empty($message)) { // Only send code if not already registered or no other message
                $code = generateVerificationCode();
                if (sendVerificationEmail($email, $code, 'verification')) {
                    setVerificationCode($email, $code, 'register');
                    $message = '<p style="color: green;">Verification code sent to <b>' . htmlspecialchars($email) . '</b>.</p>';
                    $show_verification_form = true;
                    $current_email = $email;
                } else {
                    $message = '<p style="color: red;">Failed to send verification email. Please try again.</p>';
                }
            }
        } else {
            $message = '<p style="color: red;">Invalid email address.</p>';
        }
    } elseif (isset($_POST['verification_code']) && isset($_POST['email']) && isset($_POST['action']) && $_POST['action'] === 'verify_code') {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $entered_code = trim($_POST['verification_code']);

        if ($email && $entered_code) {
            $expected_code = getVerificationCode($email, 'register');
            if ($expected_code && $entered_code === $expected_code) {
                if (registerEmail($email)) {
                    clearVerificationCode($email, 'register');
                    $message = '<p style="color: green;">Email <b>' . htmlspecialchars($email) . '</b> successfully registered!</p>';
                } else {
                    $message = '<p style="color: red;">Failed to register email. It might already be registered.</p>';
                }
            } else {
                $message = '<p style="color: red;">Invalid or expired verification code.</p>';
                $show_verification_form = true; // Keep verification form visible on error
                $current_email = $email;
            }
        } else {
            $message = '<p style="color: red;">Invalid email or verification code.</p>';
            $show_verification_form = true; // Keep verification form visible on error
            $current_email = $email;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        h1,
        h2 {
            color: #0056b3;
            margin-bottom: 20px;
        }

        form {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="email"],
        input[type="text"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        p {
            margin-top: 15px;
            text-align: center;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Messages styling */
        p[style*="color: green"] {
            color: #28a745 !important;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        p[style*="color: red"] {
            color: #dc3545 !important;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        p[style*="color: blue"] {
            /* For "already registered" message */
            color: #007bff !important;
            background-color: #cce5ff;
            border: 1px solid #b8daff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Register for GitHub Timeline Updates</h1>

    <?php echo $message; ?>

    <form action="index.php" method="post">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required>
        <button id="submit-email" name="action" value="send_code">Submit</button>
    </form>

    <?php if ($show_verification_form || (isset($_POST['action']) && $_POST['action'] === 'verify_code' && strpos($message, 'Invalid') !== false)): ?>
        <h2>Verify your Email</h2>
        <form action="index.php" method="post">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($current_email); ?>">
            <label for="verification_code">Verification Code:</label><br>
            <input type="text" id="verification_code" name="verification_code" maxlength="6" required>
            <button id="submit-verification" name="action" value="verify_code">Verify</button>
        </form>
    <?php endif; ?>

    <p><a href="unsubscribe.php">Unsubscribe from updates</a></p>
</body>

</html>