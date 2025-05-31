<?php
session_start();

require_once __DIR__ . '/../../../config/config.php'; // Adjust path as needed

// --- Security Check: Ensure user is logged in and is an admin ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.php');
    exit;
}

$user_id_to_update = null;
$user_data = null;
$questionnaire_data = null;
$error_message = '';
$success_message = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id_to_update = (int)$_GET['id'];

    // Fetch user data
    $user_query = "SELECT id, username, user_type FROM users WHERE id = ?";
    $user_data = fetchSingleData($conn, $user_query, ['i', $user_id_to_update]);

    if (!$user_data) {
        $error_message = "User not found.";
    } else {
        // Fetch questionnaire data for the user
        $questionnaire_query = "SELECT * FROM questionnaire_responses WHERE user_id = ?";
        $questionnaire_data = fetchSingleData($conn, $questionnaire_query, ['i', $user_id_to_update]);
    }
} else if (isset($_POST['submit_update'])) {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_username = trim($_POST['username']);
    $new_user_type = trim($_POST['user_type']);

    // Basic validation
    if (empty($new_username) || empty($new_user_type)) {
        $error_message = "Username and User Type cannot be empty.";
    } else {
        // Start a transaction for atomicity
        $conn->begin_transaction();
        try {
            $update_user_query = "UPDATE users SET username = ?, user_type = ? WHERE id = ?";
            $user_updated = execute($conn, $update_user_query, ['ssi', $new_username, $new_user_type, $user_id_to_update]);

            if (!$user_updated) {
                throw new Exception("Failed to update user details.");
            }

            $answers = [];
            $all_questions_answered = true;
            for ($i = 1; $i <= 10; $i++) {
                $question_name = 'q' . $i . '_answer';
                if (isset($_POST[$question_name]) && $_POST[$question_name] !== '') {
                    $answers[$question_name] = (int)$_POST[$question_name];
                } else {

                    $answers[$question_name] = null; // Set to null if not answered
                }
            }

            // Construct columns and values for INSERT ... ON DUPLICATE KEY UPDATE
            $columns = "user_id";
            $values_placeholders = "?";
            $update_set = "";
            $param_types = 'i';
            $param_values = [$user_id_to_update];

            for ($i = 1; $i <= 10; $i++) {
                $col = 'q' . $i . '_answer';
                $columns .= ", " . $col;
                $values_placeholders .= ", ?";
                $update_set .= ($update_set ? ", " : "") . $col . " = VALUES(" . $col . ")";
                $param_types .= 'i';
                $param_values[] = $answers[$col];
            }

            $upsert_questionnaire_query = "INSERT INTO questionnaire_responses (" . $columns . ") VALUES (" . $values_placeholders . ") ON DUPLICATE KEY UPDATE " . $update_set;

            $upsert_params = array_merge([$param_types], $param_values);

            $questionnaire_upserted = execute($conn, $upsert_questionnaire_query, $upsert_params);

            if (!$questionnaire_upserted) {
                throw new Exception("Failed to update/insert questionnaire responses.");
            }

            $conn->commit();
            $_SESSION['success_message'] = "User and questionnaire data updated successfully!";
            header('Location: dashboard.php'); // Redirect back to dashboard
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating data: " . $e->getMessage();
            error_log("Update User Error: " . $e->getMessage());
            // Re-fetch data to display current state if an error occurred during POST
            $user_data = fetchSingleData($conn, $user_query, ['i', $user_id_to_update]);
            $questionnaire_data = fetchSingleData($conn, $questionnaire_query, ['i', $user_id_to_update]);
        }
    }
} else {
    $error_message = "No user ID provided for update.";
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 700px;
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }

        .form-group input[type="text"],
        .form-group select {
            width: calc(100% - 20px);
            padding: 12px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .question-group {
            margin-top: 30px;
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
        }

        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
            margin-top: 20px;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .back-button {
            padding: 10px 20px;
            background-color: #6c757d;
            /* Gray */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
            text-decoration: none;
            /* For anchor tag */
            display: inline-block;
        }

        .back-button:hover {
            background-color: #5a6268;
        }

        .php-message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
            display: block;
            /* Always show if message exists */
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .form-group input[type="text"],
            .form-group select {
                padding: 10px;
                font-size: 15px;
            }

            .question-group {
                padding: 12px;
            }

            .question-group p {
                font-size: 16px;
            }

            .radio-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .radio-option {
                width: 100%;
                justify-content: flex-start;
            }

            button[type="submit"],
            .back-button {
                padding: 12px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
                border-radius: 10px;
            }

            h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }

            .form-group label {
                font-size: 14px;
            }

            .form-group input[type="text"],
            .form-group select {
                font-size: 14px;
            }

            .question-group p {
                font-size: 15px;
            }

            .radio-option {
                padding: 6px 10px;
                font-size: 14px;
            }

            button[type="submit"],
            .back-button {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Update User Details</h2>

        <?php if (!empty($error_message)): ?>
            <div class="php-message error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="php-message success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($user_data): ?>
            <form action="" method="POST">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['id']); ?>">

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="user_type">User Type:</label>
                    <select id="user_type" name="user_type" required>
                        <option value="employee" <?php echo ($user_data['user_type'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                        <option value="admin" <?php echo ($user_data['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <h3>Questionnaire Responses:</h3>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <div class="question-group">
                        <p>Question <?php echo $i; ?>: I feel valued as an employee at this company.</p>
                        <div class="radio-options">
                            <?php
                            // Determine the selected value for the current question
                            $current_answer = $questionnaire_data ? htmlspecialchars($questionnaire_data['q' . $i . '_answer']) : '';
                            ?>
                            <label class="radio-option"><input type="radio" name="q<?php echo $i; ?>_answer" value="1" <?php echo ($current_answer == '1') ? 'checked' : ''; ?>> 1 (Strongly Disagree)</label>
                            <label class="radio-option"><input type="radio" name="q<?php echo $i; ?>_answer" value="2" <?php echo ($current_answer == '2') ? 'checked' : ''; ?>> 2 (Disagree)</label>
                            <label class="radio-option"><input type="radio" name="q<?php echo $i; ?>_answer" value="3" <?php echo ($current_answer == '3') ? 'checked' : ''; ?>> 3 (Neutral)</label>
                            <label class="radio-option"><input type="radio" name="q<?php echo $i; ?>_answer" value="4" <?php echo ($current_answer == '4') ? 'checked' : ''; ?>> 4 (Agree)</label>
                            <label class="radio-option"><input type="radio" name="q<?php echo $i; ?>_answer" value="5" <?php echo ($current_answer == '5') ? 'checked' : ''; ?>> 5 (Strongly Agree)</label>
                        </div>
                    </div>
                <?php endfor; ?>

                <button type="submit" name="submit_update">Update User and Responses</button>
            </form>
        <?php else: ?>
            <p>User data could not be loaded.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="back-button">Back to Dashboard</a>
    </div>
</body>

</html>
