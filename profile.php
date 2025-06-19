<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Process form submission for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username)) {
        $error_message = "Username harus diisi.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $error_message = "Username hanya boleh mengandung huruf, angka, dan underscore.";
    } elseif (empty($email)) {
        $error_message = "Email harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        // Check if username or email already exists (excluding current user)
        $check_sql = "SELECT username, email FROM users WHERE (username = ? OR email = ?) AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $username, $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            if ($existing_user['username'] === $username) {
                $error_message = "Username sudah digunakan.";
            } else if ($existing_user['email'] === $email) {
                $error_message = "Email sudah digunakan.";
            }
        }
        $check_stmt->close();
    }
    
    // If no errors and user wants to change password
    if (empty($error_message) && !empty($current_password)) {
        // Get current password hash
        $pass_sql = "SELECT password FROM users WHERE user_id = ?";
        $pass_stmt = $conn->prepare($pass_sql);
        $pass_stmt->bind_param("i", $user_id);
        $pass_stmt->execute();
        $user_data = $pass_stmt->get_result()->fetch_assoc();
        $pass_stmt->close();
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error_message = "Password saat ini salah.";
        } elseif (empty($new_password)) {
            $error_message = "Password baru harus diisi jika Anda ingin mengubahnya.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password baru minimal 6 karakter.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Konfirmasi password baru tidak cocok.";
        }
    }
    
    // If no errors, update profile
    if (empty($error_message)) {
        if (!empty($current_password) && !empty($new_password)) {
            // Update profile with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $username, $email, $hashed_password, $user_id);
        } else {
            // Update profile without changing password
            $update_sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $username, $email, $user_id);
        }
        
        if ($update_stmt->execute()) {
            $success_message = "Profil berhasil diperbarui.";
            $_SESSION['username'] = $username;
        } else {
            $error_message = "Gagal memperbarui profil: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Get current user data for display
$user_sql = "SELECT username, email, created_at FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Set judul halaman
$page_title = "Profil Pengguna";
include 'templates/header.php';
?>

<main class="container">
    <div class="profile-container">
        <div class="tab-container">
            <div class="tab">
                <button class="tablinks active" onclick="openTab(event, 'ProfileInfo')">Informasi Profil</button>
                <button class="tablinks" onclick="openTab(event, 'EditProfile')">Edit Profil</button>
            </div>
            
            <div id="ProfileInfo" class="tabcontent" style="display: block;">
                <div class="user-info">
                    <h3>Informasi Akun</h3>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Bergabung Sejak:</strong> <?php echo (new DateTime($user['created_at']))->format('d F Y'); ?></p>
                </div>
            </div>
            
            <div id="EditProfile" class="tabcontent">
                <h3>Edit Informasi Profil</h3>
                <?php if (!empty($success_message)): ?>
                    <div class="message success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="message error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                
                <form action="profile.php" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="password-section">
                        <h3>Ubah Password</h3>
                        <p>Kosongkan jika Anda tidak ingin mengubah password.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">Perbarui Profil</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Jika ada error atau success message, buka tab Edit Profile secara default
<?php if(!empty($error_message) || !empty($success_message)): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('button[onclick="openTab(event, \'EditProfile\')"]').click();
});
<?php endif; ?>
</script>

<?php
include 'templates/footer.php';
?>