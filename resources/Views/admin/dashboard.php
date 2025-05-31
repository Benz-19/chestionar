<?php
session_start(); // Start the session at the very beginning

require_once __DIR__ . '/../../../config/config.php'; // Adjust path as needed

// --- Security Check: Ensure user is logged in and is an admin ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.php');
    exit;
}

$admin_username = $_SESSION['username'];
$users_data = [];
$error_message = '';

// Fetch all users and their questionnaire responses
try {
    // Fetch all users
    $users_query = "SELECT id, username, user_type FROM users WHERE user_type='employee' ORDER BY username ASC";
    $all_users = fetchAllData($conn, $users_query, []);

    if ($all_users) {
        foreach ($all_users as $user) {
            $user_id = $user['id'];
            $questionnaire_data = null;

            // Fetch questionnaire response for the current user
            $response_query = "SELECT * FROM questionnaire_responses WHERE user_id = ?";
            $questionnaire_data = fetchSingleData($conn, $response_query, ['i', $user_id]);

            $users_data[] = [
                'user' => $user,
                'questionnaire' => $questionnaire_data
            ];
        }
    } else {
        $error_message = "No users found in the database.";
    }
} catch (Exception $e) {
    error_log("Admin Dashboard Data Fetch Error: " . $e->getMessage());
    $error_message = "Error fetching data from the database. Please try again later.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            max-width: 1000px;
            /* Wider for dashboard */
            margin-bottom: 20px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .welcome-message {
            margin-bottom: 30px;
            font-size: 1.1em;
            color: #555;
        }

        .error-message {
            color: red;
            background-color: #ffebe8;
            border: 1px solid #ff0000;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 600;
        }

        .user-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            /* Ensures rounded corners apply to table */
        }

        .user-list th,
        .user-list td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .user-list th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .user-row {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .user-row.white {
            background-color: #ffffff;
        }

        .user-row.grey {
            background-color: #f8f8f8;
        }

        .user-row:hover {
            background-color: #e9f5ff;
        }

        .accordion-content {
            padding: 15px;
            background-color: #f2f2f2;
            border-top: 1px solid #ddd;
            display: none;
            /* Hidden by default */
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            max-height: 0;
            /* For smooth accordion animation */
        }

        .accordion-content.show {
            max-height: 500px;
            /* Adjust as needed for content height */
            transition: max-height 0.5s ease-in;
        }

        .accordion-content p {
            margin-bottom: 10px;
            color: #333;
        }

        .accordion-content strong {
            color: #007bff;
        }

        .action-buttons button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .action-buttons .update-btn {
            background-color: #ffc107;
            /* Yellow */
            color: #333;
        }

        .action-buttons .update-btn:hover {
            background-color: #e0a800;
        }

        .action-buttons .delete-btn {
            background-color: #dc3545;
            /* Red */
            color: white;
        }

        .action-buttons .delete-btn:hover {
            background-color: #c82333;
        }

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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .user-list th,
            .user-list td {
                padding: 10px;
                font-size: 0.9em;
            }

            .action-buttons button {
                padding: 6px 10px;
                font-size: 0.8em;
                margin-left: 5px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .user-list {
                font-size: 0.8em;
            }

            .user-list th,
            .user-list td {
                padding: 8px;
            }

            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .action-buttons button {
                width: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <p class="welcome-message">Welcome, <?php echo htmlspecialchars($admin_username); ?>! Manage users and questionnaire responses.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($users_data)): ?>
            <table class="user-list">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>User Type</th>
                        <th>Questionnaire Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $row_index = 0;
                    foreach ($users_data as $data):
                        $user = $data['user'];
                        $questionnaire = $data['questionnaire'];
                        $row_class = ($row_index % 2 == 0) ? 'white' : 'grey';
                    ?>
                        <tr class="user-row <?php echo $row_class; ?>">
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></td>
                            <td>
                                <?php if ($questionnaire): ?>
                                    <span style="color: green;">Completed</span>
                                <?php else: ?>
                                    <span style="color: orange;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <button class="update-btn" onclick="location.href='update_user.php?id=<?php echo $user['id']; ?>'">Update</button>
                                <button class="delete-btn" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Delete</button>
                            </td>
                        </tr>
                        <tr class="accordion-toggle <?php echo $row_class; ?>">
                            <td colspan="5">
                                <div class="accordion-content" id="accordion-<?php echo $user['id']; ?>">
                                    <h3>User Details:</h3>
                                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['id']); ?></p>
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                    <p><strong>User Type:</strong> <?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></p>

                                    <?php if ($questionnaire): ?>
                                        <h3>Questionnaire Responses:</h3>
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <p><strong>Question <?php echo $i; ?>:</strong> <?php echo htmlspecialchars($questionnaire['q' . $i . '_answer']); ?></p>
                                        <?php endfor; ?>
                                        <p><strong>Submitted At:</strong> <?php echo htmlspecialchars($questionnaire['submitted_at']); ?></p>
                                    <?php else: ?>
                                        <p>No questionnaire responses submitted by this user yet.</p>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php
                        $row_index++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No user data to display.</p>
        <?php endif; ?>
    </div>

    <button class="logout-button" id="logoutButton">Logout</button>

    <div class="message-box" id="confirmBox" style="display: none;">
        <p id="confirmText"></p>
        <button id="confirmYes" class="update-btn">Yes</button>
        <button id="confirmNo" class="delete-btn">No</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const logoutButton = document.getElementById('logoutButton');
            const userRows = document.querySelectorAll('.user-row');
            const confirmBox = document.getElementById('confirmBox');
            const confirmText = document.getElementById('confirmText');
            const confirmYes = document.getElementById('confirmYes');
            const confirmNo = document.getElementById('confirmNo');

            let currentDeleteUserId = null;

            // --- Accordion Logic ---
            userRows.forEach(row => {
                row.addEventListener('click', () => {
                    // Get the user ID from the row's first cell
                    const userId = row.children[0].textContent;
                    const accordionContent = document.getElementById(`accordion-${userId}`);

                    if (accordionContent) {
                        accordionContent.classList.toggle('show');
                        // Adjust display property for initial state and smooth transition
                        if (accordionContent.classList.contains('show')) {
                            accordionContent.style.display = 'block';
                        } else {
                            // Set display to none after transition completes
                            accordionContent.addEventListener('transitionend', function handler() {
                                if (!accordionContent.classList.contains('show')) {
                                    accordionContent.style.display = 'none';
                                }
                                accordionContent.removeEventListener('transitionend', handler);
                            });
                        }
                    }
                });
            });

            // --- Logout Button ---
            logoutButton.addEventListener('click', () => {
                window.location.href = '../logout.php';
            });

            // --- Custom Confirmation Box Logic ---
            window.confirmDelete = function(userId, username) {
                currentDeleteUserId = userId;
                confirmText.textContent = `Are you sure you want to delete user "${username}" (ID: ${userId}) and their questionnaire data?`;
                confirmBox.style.display = 'block';
                // Add 'show' class for fade-in effect
                setTimeout(() => confirmBox.classList.add('show'), 10);
            };

            confirmYes.addEventListener('click', () => {
                if (currentDeleteUserId !== null) {

                    window.location.href = `delete_user.php?id=${currentDeleteUserId}`;
                }
                confirmBox.classList.remove('show');
                setTimeout(() => confirmBox.style.display = 'none', 300);
            });

            confirmNo.addEventListener('click', () => {
                confirmBox.classList.remove('show');
                setTimeout(() => confirmBox.style.display = 'none', 300);
                currentDeleteUserId = null;
            });
        });
    </script>
</body>

</html>
