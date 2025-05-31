<?php

session_start();


require_once __DIR__ . '/../../config/config.php';

if (isset($_POST['submit'])) {
    echo "yes";
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $_SESSION['error'] = "Ensure all fields are filled!";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $query = "SELECT username, password, user_type FROM users WHERE username = ?";

        $params = ['s', $username];

        $result = fetchSingleData($conn, $query, $params);


        if ($result && $result['password'] === $password) {

            $_SESSION['username'] = $result['username'];
            $_SESSION['user_type'] = $result['user_type'];
            $query = "SELECT id FROM users WHERE username = ?";

            $params = ['s', $username];

            $id = fetchSingleData($conn, $query, $params);
            $_SESSION['user_id'] = $id['id'];
            $_SESSION['data'] = $result;

            // Redirect based on user type
            if ($result['user_type'] === 'employee') {
                header('Location: employee/chestionar.php');
                exit;
            } else if ($result['user_type'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = "Unknown user type. Please contact support.";
            }
        } else {
            // If no result or password doesn't match
            $_SESSION['error'] = "Invalid username or password.";
        }
    }

    header('Location: ../../index.php');
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset & Font */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Login Container Styling */
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Login Form Styling (Initial State for Transition) */
        .login-form {
            transform: translateX(-100%);
            opacity: 0;
            transition: transform 0.8s ease-out, opacity 0.6s ease-out;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Login Form Styling (Active State for Transition) */
        .login-form.show {
            transform: translateX(0);
            opacity: 1;
        }

        h2 {
            color: #333;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .input-group {
            margin-bottom: 20px;
            width: 100%;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        .input-group input {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        button {
            width: 100%;
            padding: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        /* Message Box Styling (for JS-triggered alerts) */
        .message-box {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            text-align: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .message-box.show {
            display: block;
            opacity: 1;
        }

        .message-box p {
            margin-bottom: 20px;
            font-size: 18px;
            color: #333;
        }

        .message-box button {
            background-color: #28a745;
            width: auto;
            padding: 10px 20px;
            font-size: 16px;
        }

        .message-box button:hover {
            background-color: #218838;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .login-container {
                padding: 30px;
            }

            h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }

            .input-group input {
                padding: 10px;
                font-size: 15px;
            }

            button {
                padding: 12px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px;
                border-radius: 10px;
            }

            h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }

            .input-group label {
                font-size: 14px;
            }

            .input-group input {
                font-size: 14px;
            }

            button {
                font-size: 15px;
            }
        }

        /* Styling for PHP Error Message */
        .error {
            color: red;
            background-color: #ffebe8;
            border: 1px solid #ff0000;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            display: none;
            /* Hidden by default in CSS */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Show error when 'show-error' class is present */
        .error.show-error {
            display: block;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <form class="login-form" id="loginForm" method="POST">
            <h2>Employee Login</h2>

            <p class="error <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])) echo 'show-error'; ?>">
                <?php
                if (isset($_SESSION['error'])) {
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                }
                ?>
            </p>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your employee ID" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" name="submit" id="loginButton">Login</button>
        </form>
    </div>

    <div class="message-box" id="messageBox">
        <p id="messageText"></p>
        <button id="messageBoxOk">OK</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username'); // Get input fields
            const passwordInput = document.getElementById('password');
            const phpErrorMessage = document.querySelector('.error'); // Select the PHP error message element

            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            const messageBoxOk = document.getElementById('messageBoxOk');

            // --- Initial Page Load Animation ---
            setTimeout(() => {
                loginForm.classList.add('show');
            }, 100);

            // --- Custom Message Box Functions (for client-side alerts) ---
            function showMessageBox(message) {
                messageText.textContent = message;
                messageBox.classList.add('show');
            }

            function hideMessageBox() {
                messageBox.classList.remove('show');
            }

            messageBoxOk.addEventListener('click', hideMessageBox);


            // --- Client-side form validation (BEFORE PHP submission) ---
            loginForm.addEventListener('submit', (event) => {
                const username = usernameInput.value.trim();
                const password = passwordInput.value.trim();

                // Clear any previous PHP error message when attempting a new submission
                if (phpErrorMessage) {
                    phpErrorMessage.classList.remove('show-error');
                    phpErrorMessage.textContent = '';
                }

                // If client-side validation fails, prevent form submission
                if (username === '' || password === '') {
                    event.preventDefault(); // Stop the form from submitting to PHP
                    showMessageBox('Please enter both username and password.');
                }

            });


            // --- Handle PHP errors displayed on page load ---
            if (phpErrorMessage && phpErrorMessage.classList.contains('show-error')) {
                setTimeout(() => {
                    phpErrorMessage.classList.remove('show-error');
                    // Optionally clear text content after hiding
                    setTimeout(() => {
                        phpErrorMessage.textContent = '';
                    }, 300);
                }, 5000);
            }
        });
    </script>
</body>

</html>
