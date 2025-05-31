<?php
session_start();

require_once __DIR__ . '/../../../config/config.php';

// --- Security Check: Ensure user is logged in and is an employee ---
if ($_SESSION['user_type'] !== 'employee') {
    header('Location: ../../../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error_message = '';
$success_message = '';

// Check if the user has already submitted the questionnaire
$check_query = "SELECT id FROM questionnaire_responses WHERE user_id = ?";
$has_responded = fetchSingleData($conn, $check_query, ['i', $user_id]);

if ($has_responded) {
    $error_message = "You have already completed this questionnaire. Thank you!";
}

// --- Handle Form Submission ---
if (isset($_POST['submit_questionnaire']) && !$has_responded) {
    $answers = [];
    $all_questions_answered = true;

    // Collect answers for all 10 questions
    for ($i = 1; $i <= 10; $i++) {
        $question_name = 'q' . $i . '_answer';
        if (isset($_POST[$question_name]) && $_POST[$question_name] !== '') {
            $answers[$question_name] = (int)$_POST[$question_name];
        } else {
            $all_questions_answered = false;
            break;
        }
    }

    if (!$all_questions_answered) {
        $error_message = "Please answer all questions before submitting.";
    } else {
        // Prepare the INSERT query
        $insert_query = "INSERT INTO questionnaire_responses (user_id, q1_answer, q2_answer, q3_answer, q4_answer, q5_answer, q6_answer, q7_answer, q8_answer, q9_answer, q10_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // --- MODIFIED SECTION: Construct parameters directly ---
        $insert_params = [];
        $insert_params[] = 'i' . str_repeat('i', 10);
        $insert_params[] = $user_id;
        for ($i = 1; $i <= 10; $i++) {
            $insert_params[] = $answers['q' . $i . '_answer']; // Add each answer value
        }

        // Execute the insert
        $insertion_successful = execute($conn, $insert_query, $insert_params);

        if ($insertion_successful) {
            $_SESSION['success_message'] = "Questionnaire successfully completed!";
            // Redirect to prevent form re-submission on refresh
            header('Location: chestionar.php');
            exit;
        } else {
            $error_message = "Failed to submit questionnaire. Please try again.";
        }
    }
}

// Retrieve success message from session if set
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear it after displaying
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Questionnaire</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            /* Allow content to stack */
            align-items: center;
            min-height: 100vh;
            padding-top: 20px;
            /* Space from top */
            padding-bottom: 40px;
            /* Space for logout button */
            box-sizing: border-box;
            /* Include padding in element's total width and height */
        }

        .container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 800px;
            /* Max width for desktop */
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .question-group {
            margin-bottom: 25px;
            text-align: left;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .question-group p {
            font-weight: 600;
            color: #444;
            margin-bottom: 15px;
            font-size: 17px;
        }

        .radio-options {
            display: flex;
            flex-wrap: wrap;
            /* Allow options to wrap on smaller screens */
            justify-content: space-around;
            gap: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .radio-option:hover {
            background-color: #e9f5ff;
            border-color: #007bff;
        }

        .radio-option input[type="radio"] {
            margin-right: 8px;
            accent-color: #007bff;
            /* Custom color for radio button */
        }

        /* Styling for the submit button */
        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            /* Green submit button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
            margin-top: 20px;
        }

        button[type="submit"]:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        /* Logout Button Styling */
        .logout-button {
            padding: 12px 25px;
            background-color: #dc3545;
            /* Red logout button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
            margin-top: 20px;
        }

        .logout-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .logout-button:active {
            transform: translateY(0);
        }

        /* Message Box Styling (for JS-triggered alerts) */
        .message-box {
            display: none;
            /* Hidden by default */
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
            max-width: 90%;
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
            background-color: #007bff;
            /* Blue for OK button */
            width: auto;
            padding: 10px 20px;
            font-size: 16px;
            box-shadow: none;
            /* Remove extra shadow for inner button */
        }

        .message-box button:hover {
            background-color: #0056b3;
            transform: none;
            /* No lift effect for inner button */
        }

        /* PHP Error/Success Message Styling */
        .php-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
            display: none;
            /* Hidden by default */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .php-message.error {
            background-color: #ffebe8;
            color: #cc0000;
            border: 1px solid #ff0000;
        }

        .php-message.success {
            background-color: #e6ffe6;
            color: #008000;
            border: 1px solid #00cc00;
        }

        .php-message.show {
            display: block;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 20px 25px;
            }

            h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }

            .question-group {
                padding: 12px;
            }

            .question-group p {
                font-size: 16px;
            }

            .radio-options {
                flex-direction: column;
                /* Stack options vertically on small screens */
                align-items: flex-start;
                gap: 8px;
            }

            .radio-option {
                width: 100%;
                /* Full width for options */
                justify-content: flex-start;
            }

            button[type="submit"],
            .logout-button {
                padding: 12px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px 20px;
                border-radius: 10px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            .question-group p {
                font-size: 15px;
            }

            .radio-option {
                padding: 6px 10px;
                font-size: 14px;
            }

            button[type="submit"],
            .logout-button {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Employee Satisfaction Questionnaire</h2>
        <p>Welcome, <?php echo htmlspecialchars($username); ?>! Please provide your honest feedback.</p>

        <?php if (!empty($error_message)): ?>
            <div class="php-message error show">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="php-message success show" id="phpSuccessMessage">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_responded): ?>
            <form action="" method="POST">
                <div class="question-group">
                    <p>1. I feel valued as an employee at this company.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q1_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q1_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q1_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q1_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q1_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>2. My workload is manageable.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q2_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q2_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q2_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q2_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q2_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>3. I have the necessary resources to perform my job effectively.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q3_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q3_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q3_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q3_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q3_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>4. Communication within my team is clear and effective.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q4_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q4_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q4_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q4_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q4_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>5. I receive constructive feedback on my performance.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q5_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q5_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q5_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q5_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q5_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>6. Opportunities for professional growth are available to me.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q6_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q6_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q6_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q6_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q6_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>7. The company promotes a healthy work-life balance.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q7_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q7_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q7_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q7_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q7_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>8. I understand how my work contributes to the company's overall goals.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q8_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q8_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q8_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q8_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q8_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>9. I feel comfortable expressing my opinions and ideas.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q9_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q9_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q9_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q9_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q9_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <div class="question-group">
                    <p>10. I would recommend this company as a great place to work.</p>
                    <div class="radio-options">
                        <label class="radio-option"><input type="radio" name="q10_answer" value="1" required> 1 (Strongly Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q10_answer" value="2"> 2 (Disagree)</label>
                        <label class="radio-option"><input type="radio" name="q10_answer" value="3"> 3 (Neutral)</label>
                        <label class="radio-option"><input type="radio" name="q10_answer" value="4"> 4 (Agree)</label>
                        <label class="radio-option"><input type="radio" name="q10_answer" value="5"> 5 (Strongly Agree)</label>
                    </div>
                </div>

                <button type="submit" name="submit_questionnaire">Submit Questionnaire</button>
            </form>
        <?php endif; ?>
    </div>

    <button class="logout-button" id="logoutButton">Logout</button>

    <div class="message-box" id="messageBox">
        <p id="messageText"></p>
        <button id="messageBoxOk">OK</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const phpSuccessMessage = document.getElementById('phpSuccessMessage');
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            const messageBoxOk = document.getElementById('messageBoxOk');
            const logoutButton = document.getElementById('logoutButton');

            // Function to show custom message box
            function showMessageBox(message) {
                messageText.textContent = message;
                messageBox.classList.add('show');
            }

            // Function to hide custom message box
            function hideMessageBox() {
                messageBox.classList.remove('show');
            }

            // Event listener for the OK button in the message box
            messageBoxOk.addEventListener('click', hideMessageBox);

            if (phpSuccessMessage && phpSuccessMessage.classList.contains('show')) {
                showMessageBox(phpSuccessMessage.textContent);
                phpSuccessMessage.style.display = 'none';
            }

            // Event listener for the logout button
            logoutButton.addEventListener('click', () => {
                window.location.href = '../logout.php';
            });
        });
    </script>
</body>

</html>
