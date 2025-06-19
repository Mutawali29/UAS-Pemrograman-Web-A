<!-- article.php  -->
<?php
session_start();

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil slug dari URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$error_message = '';

if (empty($slug)) {
    $error_message = "Artikel tidak ditemukan.";
} else {
    // Query untuk mengambil artikel, kategori, dan nama penulis
    $sql = "SELECT a.*, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as categories, u.username as author_name
            FROM articles a
            LEFT JOIN article_category ac ON a.article_id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.category_id
            LEFT JOIN users u ON a.author_id = u.user_id
            WHERE a.slug = ? AND a.status = 'published'
            GROUP BY a.article_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $article = $result->fetch_assoc();
    } else {
        $error_message = "Artikel tidak ditemukan atau belum dipublikasikan.";
    }
}

// Set judul halaman sebelum memanggil header
$page_title = isset($article) ? htmlspecialchars($article["title"]) : "Artikel Tidak Ditemukan";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - KyubiNote</title>
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Ganti bagian <style> yang ada di article.php dengan ini -->
<style>
    /* CSS untuk fitur pencarian */
    .search-widget {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .search-widget h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 1.2em;
    }
    
    .search-form {
        display: flex;
        gap: 8px;
    }
    
    .search-input {
        flex: 1;
        padding: 10px 12px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }
    
    .search-input:focus {
        outline: none;
        border-color: #007bff;
    }
    
    .search-btn {
        padding: 10px 16px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s ease;
    }
    
    .search-btn:hover {
        background: #0056b3;
    }
    
    .quick-search-links {
        margin-top: 12px;
        font-size: 12px;
    }
    
    .quick-search-links span {
        color: #6c757d;
        margin-right: 8px;
    }
    
    .quick-search-links a {
        color: #007bff;
        text-decoration: none;
        margin-right: 8px;
        padding: 2px 6px;
        border-radius: 3px;
        background: #f8f9fa;
        font-size: 11px;
    }
    
    .quick-search-links a:hover {
        background: #e9ecef;
        text-decoration: none;
    }
    
    /* CSS untuk layout artikel */
    .page-layout {
        display: flex;
        gap: 30px;
        margin: 20px auto;
        max-width: 1200px;
    }
    
    .main-content {
        flex: 2;
    }
    
    .sidebar {
        flex: 1;
        max-width: 300px;
    }
    
    .article-full-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .article-header {
        position: relative;
    }
    
    .article-header-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
    }
    
    .article-header-content {
        padding: 30px;
    }
    
    .article-header-no-image .article-header-content {
        padding: 30px 30px 0 30px;
    }
    
    .category-badges {
        margin-bottom: 15px;
    }
    
    .category-badge {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        margin-right: 8px;
        margin-bottom: 8px;
    }
    
    .article-title {
        font-size: 2.5em;
        color: #333;
        margin: 0;
        line-height: 1.2;
    }
    
    .article-details {
        padding: 0 30px 30px 30px;
    }
    
    .article-meta {
        padding: 20px 0;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 30px;
    }
    
    .meta-item {
        color: #6c757d;
        margin-right: 20px;
        font-size: 14px;
    }
    
    .meta-item i {
        margin-right: 5px;
    }
    
    .article-body {
        font-size: 16px;
        line-height: 1.8;
        color: #333;
        margin-bottom: 30px;
    }
    
    .article-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-top: 1px solid #e9ecef;
    }
    
    .action-buttons button {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .action-buttons button:hover {
        background: #e9ecef;
    }
    
    .share-links {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .share-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        background: #f8f9fa;
        color: #6c757d;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .share-links a:hover {
        background: #007bff;
        color: white;
    }
    
    .widget {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .widget h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 1.2em;
    }
    
    .widget ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .widget li {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .widget li:last-child {
        border-bottom: none;
    }
    
    .widget a {
        color: #007bff;
        text-decoration: none;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .widget a:hover {
        text-decoration: underline;
    }
    
    .widget span {
        color: #6c757d;
        font-size: 12px;
    }
    
    .back-to-top-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        transition: all 0.3s ease;
        z-index: 1000;
    }
    
    .back-to-top-btn:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }
    
    .error-message {
        text-align: center;
        padding: 50px;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    
    /* Header Button Styles - sama seperti di index.php */
    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .welcome-message {
        color: #333;
        font-weight: 500;
        margin-right: 10px;
    }

    .admin-badge {
        background: #dc3545;
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        margin-right: 10px;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 24px;
        font-size: 16px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        min-width: 47px;
    }

    .btn-primary {
        background: #7B2CBF;
        color: white;
    }

    .btn-primary:hover {
        background: #7d3c98;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(142, 68, 173, 0.3);
    }

    .btn-secondary {
        background: #F72585;
        color: white;
        /* border: 1px solid rgba(255, 255, 255, 0.3); */
    }

    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 255, 255, 0.1);
    }

    /* User Menu Styles */
    .user-menu {
        position: relative;
        display: inline-block;
    }

    .user-menu-btn {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        color: #495057;
        transition: all 0.3s ease;
    }

    .user-menu-btn:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }

    .user-menu-content {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: white;
        min-width: 200px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        border-radius: 8px;
        border: 1px solid #e9ecef;
        z-index: 1000;
        margin-top: 5px;
    }

    .user-menu:hover .user-menu-content {
        display: block;
    }

    .user-menu-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }

    .user-menu-header strong {
        display: block;
        color: #333;
        font-size: 16px;
        margin-bottom: 4px;
    }

    .user-menu-header small {
        color: #6c757d;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .user-menu-link {
        display: block;
        padding: 12px 20px;
        color: #495057;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .user-menu-link:hover {
        background: #f8f9fa;
        color: #007bff;
        text-decoration: none;
    }

    .user-menu-divider {
        margin: 0;
        border: none;
        border-top: 1px solid #e9ecef;
    }

    .profile-link:hover {
        background: #e3f2fd;
        color: #1976d2;
    }

    .articles-link:hover {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .manage-users-link:hover {
        background: #fff3e0;
        color: #ef6c00;
    }
    
    @media (max-width: 768px) {
        .page-layout {
            flex-direction: column;
            gap: 20px;
        }
        
        .sidebar {
            max-width: none;
        }
        
        .article-title {
            font-size: 2em;
        }
        
        .article-header-content,
        .article-details {
            padding: 20px;
        }
        
        .article-footer {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        /* Responsive untuk header buttons */
        .header-right {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .welcome-message {
            text-align: center;
            margin-right: 0;
            margin-bottom: 5px;
        }
        
        .btn {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .user-menu-content {
            right: 0;
            left: 0;
            min-width: auto;
        }
    }
</style>
</head>
<body>
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

<?php if (isset($article)): ?>
<main class="container page-layout">
    <div class="main-content">
        <article class="article-full-content">
            <header class="article-header <?php echo empty($article["featured_image"]) ? 'article-header-no-image' : ''; ?>">
                <?php if(!empty($article["featured_image"])): ?>
                    <img src="<?php echo htmlspecialchars($article["featured_image"]); ?>" alt="<?php echo htmlspecialchars($article["title"]); ?>" class="article-header-image">
                <?php endif; ?>
                <div class="article-header-content">
                    <div class="category-badges">
                        <?php 
                        if(!empty($article["categories"])) {
                            foreach(explode(',', $article["categories"]) as $category) {
                                echo '<span class="category-badge">' . htmlspecialchars(trim($category)) . '</span>';
                            }
                        }
                        ?>
                    </div>
                    <h1 class="article-title"><?php echo htmlspecialchars($article["title"]); ?></h1>
                </div>
            </header>
            
            <div class="article-details">
                <div class="article-meta">
                    <span class="meta-item"><i class="far fa-calendar-alt"></i> <?php echo (new DateTime($article["published_at"]))->format('d F Y'); ?></span>
                    <span class="meta-item"><i class="far fa-user"></i> <?php echo !empty($article["author_name"]) ? htmlspecialchars($article["author_name"]) : "Penulis"; ?></span>
                </div>
                
                <div class="article-body">
                    <?php echo nl2br($article["content"]); ?>
                </div>
                
                <div class="article-footer">
                    <div class="action-buttons">
                        <button><i class="far fa-heart"></i> Suka</button>
                    </div>
                    <div class="share-links">
                        <span>Bagikan:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </article>
    </div>
    
    <aside class="sidebar">
        <!-- Widget Pencarian -->
        <div class="search-widget">
            <h3><i class="fas fa-search"></i> Cari Artikel</h3>
            <form method="GET" action="search.php" class="search-form">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Cari artikel..." 
                    required
                >
                <button type="submit" class="search-btn">🔍</button>
            </form>
            <div class="quick-search-links">
                <span>Cepat:</span>
                <?php
                // Ambil beberapa kata kunci populer dari artikel
                $popular_sql = "SELECT title FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 3";
                $popular_result = $conn->query($popular_sql);
                if ($popular_result && $popular_result->num_rows > 0) {
                    while($popular_row = $popular_result->fetch_assoc()) {
                        // Ambil kata pertama dari judul sebagai quick search
                        $first_word = explode(' ', $popular_row['title'])[0];
                        if (strlen($first_word) > 3) {
                            echo '<a href="search.php?search=' . urlencode($first_word) . '">' . htmlspecialchars($first_word) . '</a>';
                        }
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="widget">
            <h3><i class="fas fa-folder"></i> Kategori</h3>
            <?php
            $cat_sql = "SELECT c.name, c.slug, COUNT(ac.article_id) as article_count 
                        FROM categories c
                        LEFT JOIN article_category ac ON c.category_id = ac.category_id
                        LEFT JOIN articles a ON ac.article_id = a.article_id AND a.status = 'published'
                        GROUP BY c.category_id 
                        HAVING article_count > 0
                        ORDER BY article_count DESC, c.name";
            $cat_result = $conn->query($cat_sql);
            if ($cat_result && $cat_result->num_rows > 0) {
                echo "<ul>";
                while($cat_row = $cat_result->fetch_assoc()) {
                    echo "<li><a href='category.php?slug=" . htmlspecialchars($cat_row["slug"]) . "'>" . htmlspecialchars($cat_row["name"]) . " <span>(" . $cat_row["article_count"] . ")</span></a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Tidak ada kategori.</p>";
            }
            ?>
        </div>
        
        <div class="widget">
            <h3><i class="fas fa-clock"></i> Artikel Terbaru</h3>
            <?php
            $recent_sql = "SELECT title, slug FROM articles WHERE status = 'published' AND slug != ? ORDER BY published_at DESC LIMIT 5";
            $recent_stmt = $conn->prepare($recent_sql);
            $recent_stmt->bind_param("s", $slug);
            $recent_stmt->execute();
            $recent_result = $recent_stmt->get_result();
            if ($recent_result && $recent_result->num_rows > 0) {
                echo "<ul>";
                while($recent_row = $recent_result->fetch_assoc()) {
                    echo "<li><a href='article.php?slug=" . htmlspecialchars($recent_row["slug"]) . "'>" . htmlspecialchars($recent_row["title"]) . "</a></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Tidak ada artikel lain.</p>";
            }
            ?>
        </div>
        
        <div class="widget">
            <h3><i class="fas fa-tags"></i> Artikel Terkait</h3>
            <?php
            // Ambil artikel terkait berdasarkan kategori yang sama
            if (!empty($article["categories"])) {
                $related_sql = "SELECT DISTINCT a.title, a.slug 
                               FROM articles a
                               JOIN article_category ac ON a.article_id = ac.article_id
                               JOIN categories c ON ac.category_id = c.category_id
                               WHERE c.name IN ('" . str_replace(',', "','", str_replace(' ', '', $article["categories"])) . "')
                               AND a.status = 'published' 
                               AND a.slug != ?
                               ORDER BY a.published_at DESC 
                               LIMIT 4";
                $related_stmt = $conn->prepare($related_sql);
                $related_stmt->bind_param("s", $slug);
                $related_stmt->execute();
                $related_result = $related_stmt->get_result();
                
                if ($related_result && $related_result->num_rows > 0) {
                    echo "<ul>";
                    while($related_row = $related_result->fetch_assoc()) {
                        echo "<li><a href='article.php?slug=" . htmlspecialchars($related_row["slug"]) . "'>" . htmlspecialchars($related_row["title"]) . "</a></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Tidak ada artikel terkait.</p>";
                }
            } else {
                echo "<p>Tidak ada artikel terkait.</p>";
            }
            ?>
        </div>
    </aside>
</main>

<button class="back-to-top-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
</button>

<?php else: ?>
<main class="container">
    <div class="message error-message">
        <h1>Oops! 😟</h1>
        <p style="font-size: 1.2em;"><?php echo htmlspecialchars($error_message); ?></p>
        <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Kembali ke Beranda</a>
    </div>
</main>
<?php endif; ?>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> KyubiNote. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // JavaScript untuk meningkatkan user experience
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const searchForm = document.querySelector('.search-form');
            
            // Prevent empty search
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    if (searchInput.value.trim() === '') {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.placeholder = 'Masukkan kata kunci...';
                    }
                });
            }
            
            // Show/hide back to top button
            const backToTopBtn = document.querySelector('.back-to-top-btn');
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopBtn.style.display = 'block';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

<?php 
if (isset($stmt)) {
    $stmt->close();
}
if (isset($recent_stmt)) {
    $recent_stmt->close();
}
if (isset($related_stmt)) {
    $related_stmt->close();
}
$conn->close(); 
?>