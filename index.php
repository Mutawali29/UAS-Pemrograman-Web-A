<!-- index.php  -->
<?php
session_start();

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil parameter pencarian
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mengambil artikel yang sudah di-publish
if (!empty($search_query)) {
    // Query pencarian
    $sql = "SELECT a.article_id, a.title, a.excerpt, a.slug, a.featured_image, a.published_at, a.author_id,
                   GROUP_CONCAT(DISTINCT c.name) AS categories
            FROM articles a
            LEFT JOIN article_category ac ON a.article_id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.category_id
            WHERE a.status = 'published' 
            AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ?)
            GROUP BY a.article_id
            ORDER BY a.published_at DESC";
    
    $search_param = "%{$search_query}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Query normal tanpa pencarian
    $sql = "SELECT a.article_id, a.title, a.excerpt, a.slug, a.featured_image, a.published_at, a.author_id,
                   GROUP_CONCAT(DISTINCT c.name) AS categories
            FROM articles a
            LEFT JOIN article_category ac ON a.article_id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.category_id
            WHERE a.status = 'published'
            GROUP BY a.article_id
            ORDER BY a.published_at DESC";
    
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KyubiNote - Beranda</title>
    <link rel="stylesheet" href="styles/index.css">
    <style>
        /* CSS untuk fitur pencarian */
        .search-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            max-width: 1500px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .search-btn {
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        .search-btn:hover {
            background: #0056b3;
        }
        
        .search-results-info {
            margin: 15px 0;
            padding: 10px;
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        
        .search-results-info strong {
            color: #0056b3;
        }
        
        .clear-search {
            margin-left: 10px;
            padding: 8px 16px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .clear-search:hover {
            background: #5a6268;
            text-decoration: none;
        }
        
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-results h3 {
            margin-bottom: 10px;
            color: #495057;
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
                    <li><a href="index.php" class="active">Beranda</a></li>
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

    <main class="container">
        <section class="articles">
            <h2>Artikel Terbaru</h2>
            
            <!-- Fitur Pencarian -->
            <div class="search-section">
                <form method="GET" action="index.php" class="search-form">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Cari artikel berdasarkan judul, ringkasan, atau konten..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                    >
                    <button type="submit" class="search-btn">🔍 Cari</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="index.php" class="clear-search">✕ Bersihkan</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Informasi Hasil Pencarian -->
            <?php if (!empty($search_query)): ?>
                <div class="search-results-info">
                    <strong>Hasil pencarian untuk:</strong> "<?php echo htmlspecialchars($search_query); ?>"
                    <?php if ($result && $result->num_rows > 0): ?>
                        - Ditemukan <?php echo $result->num_rows; ?> artikel
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $categories = !empty($row["categories"]) ? explode(',', $row["categories"]) : [];
                    $date = new DateTime($row["published_at"]);
                    $formatted_date = $date->format('d F Y');
            ?>
                    <article class="article-card">
                        <?php if($row["featured_image"]): ?>
                        <div class="article-image">
                            <img src="<?php echo htmlspecialchars($row["featured_image"]); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="article-content">
                            <h3><a href="article.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>"><?php echo htmlspecialchars($row["title"]); ?></a></h3>
                            <p class="excerpt"><?php echo htmlspecialchars($row["excerpt"]); ?></p>
                            <small>Dipublikasikan pada <?php echo $formatted_date; ?></small>
                            <br>
                            <a href="article.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>" class="read-more">Baca selengkapnya</a>

                            <?php
                            // Tombol aksi untuk admin atau pemilik artikel
                            if (isset($_SESSION['user_id']) && ( (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') || $_SESSION['user_id'] == $row['author_id']) ) {
                            ?>
                                <div class="article-actions">
                                    <a href="edit_article.php?id=<?php echo $row['article_id']; ?>">Edit</a>
                                    <a href="delete_article.php?id=<?php echo $row['article_id']; ?>" onclick="return confirm('Anda yakin ingin menghapus artikel ini?');" style="color: red;">Delete</a>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </article>
            <?php
                }
            } else {
                if (!empty($search_query)) {
                    // Pesan khusus untuk hasil pencarian kosong
            ?>
                    <div class="no-results">
                        <h3>Tidak ada artikel yang ditemukan</h3>
                        <p>Tidak ada artikel yang cocok dengan pencarian "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                        <p>Coba gunakan kata kunci yang berbeda atau <a href="index.php">lihat semua artikel</a></p>
                    </div>
            <?php
                } else {
                    echo "<p>Tidak ada artikel.</p>";
                }
            }
            ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> KyubiNote. All rights reserved. </p>
        </div>
    </footer>

    <script>
        // JavaScript untuk meningkatkan user experience
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const searchForm = document.querySelector('.search-form');
            
            // Auto-focus pada input pencarian jika ada parameter search
            if (searchInput.value.trim() !== '') {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
            
            // Prevent empty search
            searchForm.addEventListener('submit', function(e) {
                if (searchInput.value.trim() === '') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.placeholder = 'Masukkan kata kunci pencarian...';
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
$conn->close(); 
?>