<?php
require_once 'functions.php';

$message = '';
$show_unsubscribe_verification_form = false;
$current_unsubscribe_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_email']) && isset($_POST['action']) && $_POST['action'] === 'send_unsubscribe_code') {
        $email = filter_var($_POST['unsubscribe_email'], FILTER_VALIDATE_EMAIL);
        if ($email) {
            // Check if email is actually registered before sending code
            $file = __DIR__ . '/registered_emails.txt';
            $is_registered = false;
            if (file_exists($file)) {
                $registered_emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (in_array($email, $registered_emails)) {
                    $is_registered = true;
                }
            }

            if ($is_registered) {
                $code = generateVerificationCode();
                if (sendVerificationEmail($email, $code, 'unsubscribe_confirmation')) {
                    setVerificationCode($email, $code, 'unsubscribe');
                    $message = '<p style="color: green;">Unsubscribe verification code sent to <b>' . htmlspecialchars($email) . '</b>.</p>';
                    $show_unsubscribe_verification_form = true;
                    $current_unsubscribe_email = $email;
                } else {
                    $message = '<p style="color: red;">Failed to send unsubscribe verification email. Please try again.</p>';
                }
            } else {
                $message = '<p style="color: orange;">This email is not currently subscribed.</p>';
            }
        } else {
            $message = '<p style="color: red;">Invalid email address.</p>';
        }
    } elseif (isset($_POST['unsubscribe_verification_code']) && isset($_POST['unsubscribe_email']) && isset($_POST['action']) && $_POST['action'] === 'verify_unsubscribe_code') {
        $email = filter_var($_POST['unsubscribe_email'], FILTER_VALIDATE_EMAIL);
        $entered_code = trim($_POST['unsubscribe_verification_code']);

        if ($email && $entered_code) {
            $expected_code = getVerificationCode($email, 'unsubscribe');
            if ($expected_code && $entered_code === $expected_code) {
                if (unsubscribeEmail($email)) {
                    clearVerificationCode($email, 'unsubscribe');
                    $message = '<p style="color: green;">Email <b>' . htmlspecialchars($email) . '</b> successfully unsubscribed!</p>';
                } else {
                    $message = '<p style="color: red;">Failed to unsubscribe email. It might not be registered or an error occurred.</p>';
                }
            } else {
                $message = '<p style="color: red;">Invalid or expired verification code.</p>';
                $show_unsubscribe_verification_form = true; // Keep verification form visible on error
                $current_unsubscribe_email = $email;
            }
        } else {
            $message = '<p style="color: red;">Invalid email or verification code.</p>';
            $show_unsubscribe_verification_form = true; // Keep verification form visible on error
            $current_unsubscribe_email = $email;
        }
    }
} elseif (isset($_GET['email'])) {
    $current_unsubscribe_email = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    if ($current_unsubscribe_email) {
        // Pre-fill email and prompt for code if coming from an unsubscribe link
        $message = '<p style="color: blue;">Please enter the code sent to <b>' . htmlspecialchars($current_unsubscribe_email) . '</b> to confirm unsubscription.</p>';
        $show_unsubscribe_verification_form = true;
        // Automatically send the code when landing from the link (optional, but good UX)
        $code = generateVerificationCode();
        if (sendVerificationEmail($current_unsubscribe_email, $code, 'unsubscribe_confirmation')) {
            setVerificationCode($current_unsubscribe_email, $code, 'unsubscribe');
            $message .= '<p style="color: green;">Verification code sent to <b>' . htmlspecialchars($current_unsubscribe_email) . '</b>.</p>';
        } else {
            $message .= '<p style="color: red;">Failed to send unsubscribe verification email. Please try again.</p>';
        }
    } else {
        $message = '<p style="color: red;">Invalid email address in the unsubscribe link.</p>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
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
            background-color: #dc3545;
            /* Red for unsubscribe */
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
            background-color: #c82333;
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

        p[style*="color: orange"] {
            /* For "not subscribed" message */
            color: #fd7e14 !important;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Unsubscribe from GitHub Timeline Updates</h1>

    <?php echo $message; ?>

    <form action="unsubscribe.php" method="post">
        <label for="unsubscribe_email">Email:</label><br>
        <input type="email" id="unsubscribe_email" name="unsubscribe_email" value="<?php echo htmlspecialchars($current_unsubscribe_email); ?>" required>
        <button id="submit-unsubscribe" name="action" value="send_unsubscribe_code">Unsubscribe</button>
    </form>

    <?php if ($show_unsubscribe_verification_form || (isset($_POST['action']) && $_POST['action'] === 'verify_unsubscribe_code' && strpos($message, 'Invalid') !== false)): ?>
        <h2>Confirm Unsubscription</h2>
        <form action="unsubscribe.php" method="post">
            <input type="hidden" name="unsubscribe_email" value="<?php echo htmlspecialchars($current_unsubscribe_email); ?>">
            <label for="unsubscribe_verification_code">Verification Code:</label><br>
            <input type="text" id="unsubscribe_verification_code" name="unsubscribe_verification_code" maxlength="6" required>
            <button id="verify-unsubscribe" name="action" value="verify_unsubscribe_code">Verify</button>
        </form>
    <?php endif; ?>

    <p><a href="index.php">Back to registration</a></p>
</body>

</html>