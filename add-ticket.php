<?php 
session_start();

// Debug Mode Toggle (set to true for testing)
$debug = false;

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

// DB connection
$host = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'tiksumadb';

$conn = new mysqli($host, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    if ($debug) die("Connection failed: " . $conn->connect_error);
    $_SESSION['ticket_failed'] = true;
    header("Location: add-ticket.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['username'];
    $issueType = $_POST['issue_type'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];
    $filename = '';

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = time() . '_' . basename($_FILES['attachment']['name']);
        $targetFile = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
            $filename = ''; // Upload failed
        }
    }

    // Get priority ID from DB (must match table entries exactly!)
    $priorityQuery = $conn->prepare("SELECT id FROM priority_levels WHERE level_name = ?");
    if (!$priorityQuery) {
        if ($debug) die("Priority query error: " . $conn->error);
        $_SESSION['ticket_failed'] = true;
        header("Location: add-ticket.php");
        exit;
    }

    $priorityQuery->bind_param("s", $priority);
    $priorityQuery->execute();
    $priorityResult = $priorityQuery->get_result();
    $priorityRow = $priorityResult->fetch_assoc();
    $priorityId = $priorityRow ? $priorityRow['id'] : null;
    $priorityQuery->close();

    if ($priorityId === null) {
        if ($debug) die("Priority ID not found for: $priority");
        $_SESSION['ticket_failed'] = true;
        header("Location: add-ticket.php");
        exit;
    }

    // Set default status to "Open" (status_id = 1)
    $statusId = 1;

    // Prepare insert
    $stmt = $conn->prepare("INSERT INTO tickets (username, subject, priority_id, status_id, description) 
                            VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        if ($debug) die("Prepare failed: " . $conn->error);
        $_SESSION['ticket_failed'] = true;
        header("Location: add-ticket.php");
        exit;
    }

    $stmt->bind_param("ssiis", $username, $issueType, $priorityId, $statusId, $description);

    if ($stmt->execute()) {
        $_SESSION['ticket_success'] = true;

        // Include notification functions and create notifications for new ticket
        include_once 'includes/notification-functions.php';
        $new_ticket_id = $stmt->insert_id;
        autoCreateTicketNotifications($new_ticket_id, $username, $issueType, $priority);

    } else {
        if ($debug) die("Execute failed: " . $stmt->error);
        $_SESSION['ticket_failed'] = true;
    }

    $stmt->close();
    $conn->close();

    header("Location: add-ticket.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Submit a Ticket - TIKSUMA</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="./src/add-ticket-modal.css" />
  <link rel="icon" type= "image/x-icon" href="./png/logo-favicon.ico" />

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .description-limit {
      border: 2px solid red !important;
      background-color: #ffe6e6 !important;
    }
  </style>

</head>
<body>
  <div class="form-wrapper">
    <div class="ticket-form-box">
      <a href="clarc-my-ticket.php" class="close-btn"><i class="fas fa-times"></i></a>

      <div class="form-header">
        <h2><span class="logo">TIKSUMA</span></h2>
        <p>Submit A Ticket</p>
      </div>

      <form method="POST" action="add-ticket.php" enctype="multipart/form-data">
        <input type="text" name="issue_type" placeholder="Issue Type" required />

        <div class="select-wrapper">
          <select name="priority" required>
            <option value="" disabled selected>Priority</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>
        <textarea name="description" placeholder="Description" required maxlength="50"></textarea>
       

        <div class="upload-wrapper">
          <input type="file" name="attachment" />
          <i class="fas fa-upload upload-icon"></i>
        </div>

        <button type="submit" class="submit-button">Submit</button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const textarea = document.querySelector('textarea[name="description"]');
      textarea.addEventListener('input', function() {
        if (this.value.length >= 50) {
          this.classList.add('description-limit');
          if (!document.getElementById('desc-limit-msg')) {
            const msg = document.createElement('div');
            msg.id = 'desc-limit-msg';
            msg.style.color = 'red';
            msg.style.marginTop = '5px';
            msg.textContent = 'You have reached the maximum limit of 50 letters.';
            this.parentNode.insertBefore(msg, this.nextSibling);
          }
        } else {
          this.classList.remove('description-limit');
          const msg = document.getElementById('desc-limit-msg');
          if (msg) {
            msg.remove();
          }
        }
      });
    });
  </script>

  <?php
  if (isset($_SESSION['ticket_success'])) {
      echo '<script>
        Swal.fire({
          title: "Ticket Submitted!",
          text: "We have received your issue and notified our IT support team.",
          icon: "success",
          confirmButtonText: "OK"
        }).then(() => {
          window.location.href = "clarc-my-ticket.php";
        });
      </script>';
      unset($_SESSION['ticket_success']);
  }
  ?>
</body>
</html>
