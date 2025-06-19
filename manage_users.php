<?php
session_start();

// Keamanan: hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query untuk mendapatkan semua pengguna
$sql = "SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Set judul halaman
$page_title = "Manajemen Pengguna";
include 'templates/header.php';
?>

<main class="container">
    <div class="management-container">
        <h2>Manajemen Pengguna</h2>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Bergabung</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Tentukan class untuk badge role
                        $role_class = strtolower($row['role']) === 'admin' ? 'admin' : 'user';
                ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <span class="role-badge <?php echo $role_class; ?>">
                                <?php echo htmlspecialchars(ucfirst($row['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo date("d F Y", strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="action-btn edit" title="Edit User">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="action-btn delete" title="Delete User" onclick="return confirm('Anda yakin ingin menghapus pengguna ini?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;'>Tidak ada pengguna yang ditemukan.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
include 'templates/footer.php';
?>