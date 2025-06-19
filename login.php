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
$login_identifier = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_identifier = trim($_POST['login_identifier']);
    $password = $_POST['password'];
    
    if (empty($login_identifier)) {
        $errors[] = "Username atau Email wajib diisi.";
    }
    if (empty($password)) {
        $errors[] = "Password wajib diisi.";
    }
    
    if (empty($errors)) {
        $sql = "SELECT user_id, username, email, password, role FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $login_identifier, $login_identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                session_regenerate_id();
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: index.php");
                exit;
            } else {
                $errors[] = "Kombinasi username/email dan password salah.";
            }
        } else {
            $errors[] = "Kombinasi username/email dan password salah.";
        }
        $stmt->close();
    }
}

// Set judul halaman
$page_title = "Login";
include 'templates/header.php';
?>

<main class="container">
    <div class="auth-container-wrapper">
        <div class="auth-container">
            <h2>Login</h2>
            
            <?php if (isset($_SESSION['registration_success'])): ?>
                <div class="message success-message">
                    <?php echo $_SESSION['registration_success']; ?>
                </div>
                <?php unset($_SESSION['registration_success']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="login_identifier">Username atau Email</label>
                    <input type="text" id="login_identifier" name="login_identifier" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                
                <div class="auth-switch-link">
                    Belum punya akun? <a href="register.php">Daftar di sini</a>
                </div>
            </form>
        </div>
    </div>
    </main>

<?php
// Kita tidak perlu footer di halaman login, jadi kita panggil file footer kosong
// atau kita bisa gunakan style `display:none` dari login.css
include 'templates/footer.php';
?>