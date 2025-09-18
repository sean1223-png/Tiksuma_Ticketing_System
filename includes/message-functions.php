<?php
require_once 'db_connect.php';

/**
 * Send a message for a ticket
 * @param int $ticket_id
 * @param string $sender
 * @param string $message
 * @return bool
 */
function send_message($ticket_id, $sender, $message) {
    global $conn;

    $ticket_id = intval($ticket_id);
    $sender = mysqli_real_escape_string($conn, $sender);
    $message = mysqli_real_escape_string($conn, $message);

    $sql = "INSERT INTO messages (ticket_id, sender, message) VALUES ($ticket_id, '$sender', '$message')";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("Failed to send message: " . mysqli_error($conn));
    }
    return $result;
}

/**
 * Get messages for a ticket
 * @param int $ticket_id
 * @return array
 */
function get_messages($ticket_id) {
    global $conn;

    $ticket_id = intval($ticket_id);

    $sql = "SELECT id, sender, message, timestamp FROM messages WHERE ticket_id = $ticket_id ORDER BY timestamp ASC";

    $result = mysqli_query($conn, $sql);

    $messages = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }

    return $messages;
}

/**
 * Delete a message
 * @param int $message_id
 * @param string $sender
 * @return bool
 */
function delete_message($message_id, $sender) {
    global $conn;

    $message_id = intval($message_id);
    $sender = mysqli_real_escape_string($conn, $sender);

    $sql = "DELETE FROM messages WHERE id = $message_id AND sender = '$sender'";

    return mysqli_query($conn, $sql);
}
?>
