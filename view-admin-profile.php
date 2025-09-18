<?php 
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiksumadb');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION['username'];
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$updated = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $profilePicPath = $user['profile_picture'];

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['profile_picture']['tmp_name']);

        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $fileName = $username . '_profile_' . time() . '.' . $ext;
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $profilePicPath = $targetFile;
            }
        }
    }

    $update = $conn->prepare("UPDATE users SET email = ?, profile_picture = ? WHERE username = ?");
    $update->bind_param("sss", $email, $profilePicPath, $username);
    $update->execute();
    $update->close();

    $updated = true;

    // Reload updated user info
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View/Edit Profile - TIKSUMA</title>
  <link rel="stylesheet" href="./src/view-profile.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
   
  </style>
</head>
<body>

  <div class="profile-container">
    <h2>My Profile</h2>
    <form method="POST" enctype="multipart/form-data" class="profile-form">
      <div class="profile-card">
        <img src="<?= htmlspecialchars($user['profile_picture'] ?: 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#ccc"/><circle cx="50" cy="35" r="15" fill="#999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80" fill="#999"/></svg>')) ?>"
             alt="Profile Picture" class="profile-pic" id="previewImg">
        <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(event)" />
      </div>

      <div class="profile-info">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />

        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
    <a href="admin-dashboards.php" class="back-btn">Back to Dashboard</a>
  </div>

<?php if ($updated): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Profile Updated!',
    text: 'Your profile changes have been saved successfully.',
    timer: 2000,
    showConfirmButton: false
  });
</script>
<?php endif; ?>

<script>
  function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
      const output = document.getElementById('previewImg');
      output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
  }
</script>

</body>
</html>
