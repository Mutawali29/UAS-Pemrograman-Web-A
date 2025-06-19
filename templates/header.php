<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'QuotientNote'; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles/index.css">
    
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);

    if ($current_page == 'add_article.php') {
        echo '<link rel="stylesheet" href="styles/add_article.css">';
    } 
    elseif ($current_page == 'profile.php') {
        echo '<link rel="stylesheet" href="styles/profile.css">';
    }
    elseif ($current_page == 'manage_users.php') {
        echo '<link rel="stylesheet" href="styles/manage_users.css">';
    }
    elseif ($current_page == 'login.php' || $current_page == 'register.php') {
        echo '<link rel="stylesheet" href="styles/login.css">';
    }
    // ================== KONDISI BARU DI SINI ==================
    elseif ($current_page == 'edit_article.php') {
        echo '<link rel="stylesheet" href="styles/edit_article.css">';
    }
    // ==========================================================
    ?>
</head>
<body class="<?php echo ($current_page == 'login.php' || $current_page == 'register.php') ? 'auth-page' : ''; ?>">
    <header>
        <div class="container">
            <div class="header-left">
                <h1><a href="index.php">KyubiNote</a></h1>
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="add_article.php">Tambah Artikel</a></li>
                        <li><a href="categories.php">Kategori</a></li>
                        <li><a href="about.php">Tentang</a></li>
                    </ul>
                </nav>
            </div>
            <div class="header-right">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="welcome-message">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                    <a href="add_article.php" class="btn btn-primary">Tambah Artikel</a>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                    <div class="user-menu">
                        <button class="user-menu-btn">▼</button>
                        <div class="user-menu-content">
                            <div class="user-menu-header">
                                <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                                <small><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'User'; ?></small>
                            </div>
                            <a href="profile.php" class="user-menu-link profile-link">Profile</a>
                            <a href="my_articles.php" class="user-menu-link articles-link">Artikel Saya</a>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <hr class="user-menu-divider">
                                <a href="manage_users.php" class="user-menu-link manage-users-link">Manage Users</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>