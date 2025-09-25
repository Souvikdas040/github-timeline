# 📌 GitHub Timeline Email Updates

This project is a simple PHP-based system that allows users to subscribe to and receive periodic email updates of the latest public events from the GitHub timeline. It includes a user-friendly web interface for registration and unsubscription, and a cron job to automate the process of fetching and sending updates.

## 🚀 Features

* **Email Registration:** Users can enter their email on the `index.php` page to register.
* **Email Verification:** A 6-digit verification code is sent to the provided email to confirm ownership and prevent spam.
* **Unsubscribe Functionality:** Users can easily unsubscribe via a dedicated `unsubscribe.php` page, also requiring a verification code.
* **Automated Updates:** A cron job runs a PHP script (`cron.php`) at a set interval (every 5 minutes by default) to fetch the latest GitHub events and send them to all registered emails.
* **Secure Code Storage:** Verification codes are stored temporarily in the user's session.
* **File-Based Storage:** Registered emails are stored in a simple text file (`registered_emails.txt`).

## 📂 Project Structure

```kotlin
github-timeline
├── README.md
└── src/
    ├── `cron_errors.log`: A log file specifically for PHP errors from the `cron.php` script.
    ├── `cron.php`: The script executed by the cron job to send updates to subscribers.
    ├── `functions.php`: Contains all the core logic, including functions for sending emails, generating codes, registering/unsubscribing emails, and fetching/formatting GitHub data.
    ├── `index.php`: The main user interface for new registrations.
    ├── `registered_emails.txt`: A plain text file that stores the list of registered emails, with one email per line.
    ├── `setup_cron.sh`: A bash script to easily set up the cron job on a Linux-based server.
    ├── `unsubscribe.php`: The interface for users to unsubscribe from the service.
    └── `cron.log`: A log file for the cron job's output and errors.
```

## ⚡ Setup and Installation

### Prerequisites

* A web server with PHP support (e.g., Apache, Nginx).
* PHP with `mail()` function configured.
* Command-line access to the server to set up the cron job.

### Steps

1.  **Clone the repository:**
    ```bash
    git clone [repository_url]
    cd [repository_name]
    ```

2.  **Ensure file permissions:**
    Make sure the web server has write permissions to create and modify `registered_emails.txt`, `cron.log`, and `cron_errors.log`.

    ```bash
    chmod 775 registered_emails.txt cron.log cron_errors.log
    ```

3.  **Configure the cron job:**
    The `setup_cron.sh` script automates this process. Navigate to the project directory and run the script:

    ```bash
    bash setup_cron.sh
    ```
    This script will add the following line to your crontab, which runs `cron.php` every 5 minutes:
    ```
    */5 * * * * php /path/to/your/project/cron.php >> /path/to/your/project/cron.log 2>&1
    ```

    > **Note:** You can adjust the frequency of the updates by editing the cron entry in `setup_cron.sh`.

4.  **Access the application:**
    Open your web browser and navigate to the URL where the project is hosted (e.g., `http://localhost/` or `http://your-domain.com/`).

## 👨‍💻 How It Works

### Registration

1.  A user enters their email on `index.php`.
2.  The form sends a POST request with the email and `action=send_code`.
3.  `index.php` validates the email and checks if it's already in `registered_emails.txt`.
4.  If the email is new, a random 6-digit code is generated and sent via `sendVerificationEmail()` from `functions.php`.
5.  The code is stored in the user's session using `setVerificationCode()`.
6.  The verification form is displayed, prompting the user to enter the code.
7.  Upon entering the code and submitting, the system verifies it against the code stored in the session using `getVerificationCode()`.
8.  If the codes match, the `registerEmail()` function adds the email to `registered_emails.txt`. The session data for the code is then cleared.

### Updates

1.  The cron job runs `cron.php` every 5 minutes.
2.  `cron.php` calls `sendGitHubUpdatesToSubscribers()`.
3.  This function reads all emails from `registered_emails.txt`.
4.  It fetches the latest GitHub public events using `fetchGitHubTimeline()` and formats them into an HTML table with `formatGitHubData()`.
5.  It then loops through all registered emails and sends the formatted HTML content using the `mail()` function, including an unsubscribe link.

### Unsubscription

1.  A user clicks the unsubscribe link in the email or visits `unsubscribe.php`.
2.  The `unsubscribe.php` page prompts the user for their email and sends a verification code, similar to the registration process.
3.  The user enters the code and submits the form.
4.  The system verifies the code.
5.  If the codes match, the `unsubscribeEmail()` function is called, which removes the email from `registered_emails.txt`. The session data is then cleared.

## 🛠 Tech Stack

* PHP (core logic)
* Shell Script (cron setup)
* Text File Storage (for simplicity)

## 🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss your ideas.

## 📜 License

This project is licensed under the [MIT License](/LICENSE).
