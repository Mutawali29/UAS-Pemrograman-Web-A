<?php
session_start();

// Redirect jika sudah login
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Koneksi database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($username)) {
        $errors[] = "Username harus diisi.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username hanya boleh mengandung huruf, angka, dan underscore.";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }
    
    if (empty($errors)) {
        $check_sql = "SELECT username, email FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $existing_user = $check_result->fetch_assoc();
            if ($existing_user['username'] === $username) {
                $errors[] = "Username sudah terdaftar.";
            }
            if ($existing_user['email'] === $email) {
                $errors[] = "Email sudah terdaftar.";
            }
        }
        $check_stmt->close();
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($insert_stmt->execute()) {
            $_SESSION['registration_success'] = "Registrasi berhasil! Silakan login.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registrasi gagal: " . $conn->error;
        }
        $insert_stmt->close();
    }
}

// Set judul halaman
$page_title = "Register";
include 'templates/header.php';
?>

<main class="container">
    <div class="auth-container-wrapper">
        <div class="auth-container">
            <h2>Register</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <div class="auth-switch-link">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
include 'templates/footer.php';
?>