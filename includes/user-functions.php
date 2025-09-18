<?php
/**
 * User Functions
 * Reusable functions for accessing and managing user data
 */

function get_db_connection() {
    $conn = new mysqli('localhost', 'root', '', 'tiksumadb');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    return $conn;
}

/**
 * Get user by ID
 * @param int $id User ID
 * @return array|null User data or null if not found
 * @throws Exception on database error
 */
function get_user_by_id($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, username, email, user_type, full_name, contact_number, department, position, bio, profile_picture FROM users WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user ?: null;
}

/**
 * Get user by username
 * @param string $username Username
 * @return array|null User data or null if not found
 * @throws Exception on database error
 */
function get_user_by_username($username) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, username, email, user_type, full_name, contact_number, department, position, bio, profile_picture FROM users WHERE username = ?");
    if (!$stmt) {
        $conn->close();
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user ?: null;
}

/**
 * Get all users
 * @return array List of users
 * @throws Exception on database error
 */
function get_all_users() {
    $conn = get_db_connection();
    $result = $conn->query("SELECT id, username, email, user_type, full_name, contact_number, department, position, bio, profile_picture FROM users ORDER BY username ASC");
    if (!$result) {
        $conn->close();
        throw new Exception('Query failed: ' . $conn->error);
    }
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $conn->close();
    return $users;
}

/**
 * Get users by user type
 * @param string $user_type User type (e.g., 'Admin', 'User', 'IT')
 * @return array List of users of the given type
 * @throws Exception on database error
 */
function get_users_by_type($user_type) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, username, email, user_type, full_name, contact_number, department, position, bio, profile_picture FROM users WHERE user_type = ? ORDER BY username ASC");
    if (!$stmt) {
        $conn->close();
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("s", $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $users;
}

/**
 * Update user data
 * @param int $id User ID
 * @param array $data Associative array of fields to update (keys must match column names)
 * @return bool True on success, false on failure
 * @throws Exception on database error
 */
function update_user($id, $data) {
    if (empty($data)) {
        throw new Exception('No data provided for update');
    }

    $conn = get_db_connection();

    $fields = [];
    $types = '';
    $values = [];

    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_double($value) || is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
        $values[] = $value;
    }

    $types .= 'i'; // for $id
    $values[] = $id;

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $conn->close();
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$values);

    $result = $stmt->execute();

    $stmt->close();
    $conn->close();

    return $result;
}

/**
 * Delete user by ID
 * @param int $id User ID
 * @return bool True on success, false on failure
 * @throws Exception on database error
 */
function delete_user($id) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt) {
        $conn->close();
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}
?>
