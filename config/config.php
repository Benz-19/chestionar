<?php
// config/config.php - Ensure this file exists and is correctly configured

$host = "localhost";
$username = "root";
$password = "";
$dbname = "chestionar";

// Establish connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the connection error
    error_log("Database Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

/**
 * Executes a SQL query, optionally with parameters.
 *
 * @param mysqli $conn The database connection object.
 * @param string $query The SQL query to execute (use '?' for placeholders).
 * @param array $params An array where the first element is a string of parameter types (e.g., 's', 'i', 'd', 'b'),
 * followed by the actual parameter values. E.g., ['s', 'value', 'i', 123].
 * @return bool True on success, false on failure.
 */
function execute($conn, $query, $params = [])
{
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            // Log the specific error if statement preparation fails
            error_log("Failed to prepare statement for query: " . $query . " Error: " . $conn->error);
            return false;
        }

        if (!empty($params) && is_array($params)) {
            // The first element of $params is the type string, the rest are values
            $types = array_shift($params); // Get the type string ('s', 'ss', 'is', etc.)

            // Ensure $params values are passed by reference for bind_param
            $bind_refs = [];
            foreach ($params as $key => $value) {
                $bind_refs[$key] = &$params[$key];
            }
            array_unshift($bind_refs, $types); // Add types string to the beginning of the array

            // Call bind_param dynamically
            // Check if bind_param was successful
            if (!call_user_func_array([$stmt, 'bind_param'], $bind_refs)) {
                error_log("Failed to bind parameters for query: " . $query . " Error: " . $stmt->error);
                $stmt->close();
                return false;
            }
        }

        $result = $stmt->execute();
        if ($result === false) {
            // Log the specific error if statement execution fails
            error_log("Failed to execute statement for query: " . $query . " Error: " . $stmt->error);
        }
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Database execute error: " . $e->getMessage() . " Query: " . $query);
        return false;
    }
}

/**
 * Fetches a single row from the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $query The SQL query to execute.
 * @param array $params An array where the first element is a string of parameter types (e.g., 's', 'i', 'd', 'b'),
 * followed by the actual parameter values. E.g., ['s', 'username_value'].
 * @return array|null An associative array representing the row, or null if no row is found or an error occurs.
 */
function fetchSingleData($conn, $query, $params = [])
{
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Failed to prepare statement for single fetch: " . $conn->error . " Query: " . $query);
            return null;
        }

        if (!empty($params) && is_array($params)) {
            $types = array_shift($params);
            $bind_refs = [];
            foreach ($params as $key => $value) {
                $bind_refs[$key] = &$params[$key];
            }
            array_unshift($bind_refs, $types);
            if (!call_user_func_array([$stmt, 'bind_param'], $bind_refs)) {
                error_log("Failed to bind parameters for single fetch: " . $stmt->error . " Query: " . $query);
                $stmt->close();
                return null;
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            error_log("Failed to get result set for single fetch: " . $stmt->error . " Query: " . $query);
            $stmt->close();
            return null;
        }

        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    } catch (Exception $e) {
        error_log("Database fetchSingleData error: " . $e->getMessage() . " Query: " . $query);
        return null;
    }
}

// fetchAllData is not strictly needed for this task, but good to have if user needs it later.
function fetchAllData($conn, $query, $params = [])
{
    $data = [];
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Failed to prepare statement for all fetch: " . $conn->error . " Query: " . $query);
            return $data;
        }

        if (!empty($params) && is_array($params)) {
            $types = array_shift($params);
            $bind_refs = [];
            foreach ($params as $key => $value) {
                $bind_refs[$key] = &$params[$key];
            }
            array_unshift($bind_refs, $types);
            if (!call_user_func_array([$stmt, 'bind_param'], $bind_refs)) {
                error_log("Failed to bind parameters for all fetch: " . $stmt->error . " Query: " . $query);
                $stmt->close();
                return $data;
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            error_log("Failed to get result set for all fetch: " . $stmt->error . " Query: " . $query);
            $stmt->close();
            return $data;
        }

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        error_log("Database fetchAllData error: " . $e->getMessage() . " Query: " . $query);
        return $data;
    }
}
