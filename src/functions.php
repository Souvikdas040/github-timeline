<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code ,string $subject_type = 'verification'): bool {
    $to = $email;
    $from = "no-reply@example.com";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from . "\r\n";

    if ($subject_type === 'verification') {
        $subject = 'Your Verification Code';
        $message = '<p>Your verification code is: <strong>' . htmlspecialchars($code) . '</strong></p>';
    } elseif ($subject_type === 'unsubscribe_confirmation') {
        $subject = 'Confirm Unsubscription';
        $message = '<p>To confirm unsubscription, use this code: <strong>' . htmlspecialchars($code) . '</strong></p>';
    } else {
        return false; // Invalid subject type
    }

    return mail($to, $subject, $message, $headers);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0777, true);
    }
    // Check if email already registered
    if (file_exists($file)) {
        $registered_emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $registered_emails)) {
            return false; // Email already registered
        }
    }
    return file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) {
        return false;
    }

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $initial_count = count($emails);
    $emails = array_filter($emails, function($e) use ($email) {
        return $e !== $email;
    });

    if (count($emails) === $initial_count) {
        return false; // Email not found
    }

    return file_put_contents($file, implode(PHP_EOL, $emails) . (empty($emails) ? '' : PHP_EOL), LOCK_EX) !== false;
}

/**
 * Fetch GitHub timeline.
 */
function fetchGitHubTimeline() {
    $url = 'https://api.github.com/events';
    $options = [
        "http" => [
            "header" => "User-Agent: PHP\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        error_log("Failed to fetch GitHub timeline from: " . $url);
        return null;
    }
    return $data;
}

/**
 * Format GitHub timeline data. Returns a valid HTML string.
 */
function formatGitHubData(?string $data): string {
    if ($data === null) {
        return '<p>No GitHub timeline data available.</p>';
    }

    $events = json_decode($data, true);
    if ($events === null) {
        return '<p>Invalid GitHub data format.</p>';
    }

    $html = '<h2>GitHub Timeline Updates</h2><table border="1"><tr><th>Type</th><th>User</th></tr>';
    foreach (array_slice($events, 0, 5) as $event) {
        $html .= '<tr><td>' . htmlspecialchars($event['type']) . '</td><td>' . htmlspecialchars($event['actor']['login']) . '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}

/**
 * Send the formatted GitHub updates to registered emails.
 */
function sendGitHubUpdatesToSubscribers(): void {
  $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) {
        error_log("registered_emails.txt not found for sending updates.");
        return;
    }

    $registered_emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($registered_emails)) {
        error_log("No registered emails to send updates to.");
        return;
    }

    $github_data = fetchGitHubTimeline();
    $formatted_html = formatGitHubData($github_data);

    $subject = 'Latest GitHub Updates';
    $from = "no-reply@example.com";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $from . "\r\n";

    foreach ($registered_emails as $email) {
        $unsubscribe_link = 'http://localhost/unsubscribe.php?email=' . urlencode($email); // Assuming localhost for testing
        $message = $formatted_html . '<p><a href="' . htmlspecialchars($unsubscribe_link) . '" id="unsubscribe-button">Unsubscribe</a></p>';
        if (!mail($email, $subject, $message, $headers)) {
            error_log("Failed to send GitHub update email to: " . $email);
        }
    }
}

// Session management for storing verification codes
function setVerificationCode(string $email, string $code, string $type = 'register'): void {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION[$type . '_code_' . md5($email)] = $code;
    $_SESSION[$type . '_code_time_' . md5($email)] = time();
}

function getVerificationCode(string $email, string $type = 'register'): ?string {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $code_key = $type . '_code_' . md5($email);
    $time_key = $type . '_code_time_' . md5($email);

    if (isset($_SESSION[$code_key]) && isset($_SESSION[$time_key])) {
        // Optional: Add a timeout for the code, e.g., 15 minutes
        if (time() - $_SESSION[$time_key] < 900) { // 900 seconds = 15 minutes
            return $_SESSION[$code_key];
        } else {
            unset($_SESSION[$code_key]);
            unset($_SESSION[$time_key]);
        }
    }
    return null;
}

function clearVerificationCode(string $email, string $type = 'register'): void {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION[$type . '_code_' . md5($email)]);
    unset($_SESSION[$type . '_code_time_' . md5($email)]);
}