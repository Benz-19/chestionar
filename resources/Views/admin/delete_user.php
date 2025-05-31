<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../../index.php');
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_to_delete_id = (int)$_GET['id'];


    $conn->begin_transaction();
    try {
        // Delete questionnaire responses first (if any)
        $delete_responses_query = "DELETE FROM questionnaire_responses WHERE user_id = ?";
        execute($conn, $delete_responses_query, ['i', $user_to_delete_id]);

        // Then delete the user
        $delete_user_query = "DELETE FROM users WHERE id = ?";
        $user_deleted = execute($conn, $delete_user_query, ['i', $user_to_delete_id]);

        if ($user_deleted) {
            $conn->commit();
            $_SESSION['success_message'] = "User and their questionnaire data deleted successfully.";
        } else {
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to delete user.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete User Error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred during deletion.";
    }
} else {
    $_SESSION['error_message'] = "Invalid user ID for deletion.";
}
$conn->close();
header('Location: dashboard.php'); // Redirect back to dashboard
exit;
