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
$errors = [];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $full_name = trim($_POST['full_name']);
    $contact_number = preg_replace('/[^0-9+]/', '', $_POST['contact_number']);
    $profilePicPath = $user['profile_picture'];

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (!empty($contact_number) && strlen($contact_number) < 10) {
        $errors[] = "Contact number must be at least 10 digits.";
    }

    // Handle profile picture upload
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
                
                // Remove old profile picture if it's not the default
                if ($user['profile_picture'] && $user['profile_picture'] !== 'default-profile.png' && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        } else {
            $errors[] = "Invalid file type. Please upload JPEG, PNG, GIF, or WEBP images.";
        }
    }

    // Update database if no errors
    if (empty($errors)) {
        $update = $conn->prepare("UPDATE users SET email = ?, full_name = ?, contact_number = ?, department = ?, position = ?, bio = ?, profile_picture = ? WHERE username = ?");
        $update->bind_param("ssssssss", $email, $full_name, $contact_number, $department, $position, $bio, $profilePicPath, $username);
        
        if ($update->execute()) {
            $updated = true;
            
            // Reload updated user info
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $errors[] = "Failed to update profile. Please try again.";
        }
        $update->close();
    }
}

$conn->close();

// Prepare profile picture URL with cache-busting
$profilePicUrl = $user['profile_picture'];
if ($profilePicUrl && !str_starts_with($profilePicUrl, 'data:')) {
    $profilePicUrl .= '?t=' . time();
}
$defaultPic = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#ccc"/><circle cx="50" cy="35" r="15" fill="#999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80" fill="#999"/></svg>');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Complete Your Profile - TIKSUMA</title>
  <link rel="stylesheet" href="./src/view-profile.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/x-icon" href="./png/logo-favicon.ico" />

  <style>
    .profile-form {
      max-width: 800px;
      margin: 0 auto;
    }
    
    .form-section {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #9aa3a9ff;
    }
    
    .form-section h3 {
      margin-top: 0;
      color: #2c3e50;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }
    
    .form-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 15px;
    }
    
    .form-group {
      flex: 1;
      min-width: 250px;
    }
    
    .form-group label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
      color: #34495e;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      box-sizing: border-box;
    }
    
    .form-group textarea {
      height: 100px;
      resize: vertical;
    }
    
    .error-message {
      color: #e74c3c;
      font-size: 12px;
      margin-top: 5px;
    }
    
    .success-message {
      background: #d4edda;
      color: #155724;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 20px;
      border: 1px solid #c3e6cb;
    }
  </style>
</head>
<body>

  <div class="profile-container">
    <h2>My Profile</h2>
    
    <?php if (!empty($errors)): ?>
      <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
        <strong>Please fix the following errors:</strong>
        <ul style="margin: 5px 0 0 20px;">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
    
    <?php if ($updated): ?>
      <div class="success-message">
        <strong>Success!</strong> Your profile has been updated successfully.
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="profile-form">
      
      <!-- Personal Information Section -->
      <div class="form-section">
        <h3>Personal Information</h3>
        
        <div class="profile-card">
          <img src="<?= htmlspecialchars($profilePicUrl ?: $defaultPic) ?>"
               alt="Profile Picture" class="profile-pic" id="previewImg">
          <input type="file" name="profile_picture" accept="image/*" onchange="previewImage(event)" />
          <div class="error-message" id="profilePicError"></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" name="full_name" id="full_name" 
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" name="email" id="email" 
                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="tel" name="contact_number" id="contact_number" 
                   value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" 
                   placeholder="+1234567890">
          </div>
          
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
            <small style="color: #666;">Username cannot be changed</small>
          </div>
        </div>
      </div>

      
      <button type="submit" class="save-btn">Save Profile</button>
    </form>
    
    <a href="it-tickets.php" class="back-btn">Back to Dashboard</a>
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
      const file = event.target.files[0];
      const errorDiv = document.getElementById('profilePicError');
      
      if (file) {
        // Check file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
          errorDiv.textContent = 'File size must be less than 2MB';
          event.target.value = '';
          return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
          errorDiv.textContent = 'Please select a valid image file (JPEG, PNG, GIF, WEBP)';
          event.target.value = '';
          return;
        }
        
        errorDiv.textContent = '';
        
        const reader = new FileReader();
        reader.onload = function(){
          const output = document.getElementById('previewImg');
          output.src = reader.result;
        };
        reader.readAsDataURL(file);
      }
    }
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
      const email = document.getElementById('email').value;
      const fullName = document.getElementById('full_name').value;
      
      if (!fullName.trim()) {
        e.preventDefault();
        alert('Please enter your full name');
        return;
      }
      
      if (!email.includes('@')) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return;
      }
    });
  </script>

</body>
</html>
