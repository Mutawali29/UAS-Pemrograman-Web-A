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

// Query untuk daftar kategori dan jumlah artikel yang sudah 'published'
if (!empty($search_query)) {
    // Query pencarian kategori
    $sql = "SELECT c.category_id, c.name, c.slug, c.description, COUNT(a.article_id) as article_count 
            FROM categories c
            LEFT JOIN article_category ac ON c.category_id = ac.category_id
            LEFT JOIN articles a ON ac.article_id = a.article_id AND a.status = 'published'
            WHERE c.name LIKE ? OR c.description LIKE ?
            GROUP BY c.category_id
            ORDER BY c.name";
    
    $search_param = "%{$search_query}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Query normal tanpa pencarian
    $sql = "SELECT c.category_id, c.name, c.slug, c.description, COUNT(a.article_id) as article_count 
            FROM categories c
            LEFT JOIN article_category ac ON c.category_id = ac.category_id
            LEFT JOIN articles a ON ac.article_id = a.article_id AND a.status = 'published'
            GROUP BY c.category_id
            ORDER BY c.name";
    
    $result = $conn->query($sql);
}

// Set judul halaman sebelum memanggil header
$page_title = !empty($search_query) ? "Pencarian Kategori: " . htmlspecialchars($search_query) : "Daftar Kategori";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - KyubiNote</title>
    <link rel="stylesheet" href="styles/index.css">
    <style>
        /* CSS untuk fitur pencarian - sama dengan index.php */
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
        
        /* CSS untuk layout kategori */
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
        
        .page-title {
            color: #333;
            margin-bottom: 20px;
            font-size: 2.2em;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .category-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .category-card h2 {
            margin: 0 0 10px 0;
            font-size: 1.4em;
        }
        
        .category-card h2 a {
            color: #007bff;
            text-decoration: none;
        }
        
        .category-card h2 a:hover {
            text-decoration: underline;
        }
        
        .category-card .description {
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .card-footer {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .article-count {
            color: #007bff;
            font-weight: bold;
            font-size: 0.9em;
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
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .widget li:last-child {
            border-bottom: none;
        }
        
        .widget a {
            color: #007bff;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .widget a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .page-layout {
                flex-direction: column;
                gap: 20px;
            }
            
            .sidebar {
                max-width: none;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
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
                        <li><a href="categories.php" class="active">Kategori</a></li>
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

    <main class="container page-layout">
        <div class="main-content">
            <section class="categories-list">
                <h1 class="page-title">Daftar Kategori</h1>
                
                <!-- Fitur Pencarian -->
                <div class="search-section">
                    <form method="GET" action="categories.php" class="search-form">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Cari kategori berdasarkan nama atau deskripsi..." 
                            value="<?php echo htmlspecialchars($search_query); ?>"
                        >
                        <button type="submit" class="search-btn">🔍 Cari</button>
                        <?php if (!empty($search_query)): ?>
                            <a href="categories.php" class="clear-search">✕ Bersihkan</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Informasi Hasil Pencarian -->
                <?php if (!empty($search_query)): ?>
                    <div class="search-results-info">
                        <strong>Hasil pencarian untuk:</strong> "<?php echo htmlspecialchars($search_query); ?>"
                        <?php if ($result && $result->num_rows > 0): ?>
                            - Ditemukan <?php echo $result->num_rows; ?> kategori
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="categories-grid">
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                    ?>
                            <div class="category-card">
                                <div class="card-content">
                                    <h2><a href="category.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>"><?php echo htmlspecialchars($row["name"]); ?></a></h2>
                                    <?php if($row["description"]): ?>
                                        <p class="description"><?php echo htmlspecialchars($row["description"]); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <span class="article-count"><?php echo $row["article_count"]; ?> artikel</span>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        if (!empty($search_query)) {
                            // Pesan khusus untuk hasil pencarian kosong
                    ?>
                            <div class="no-results">
                                <h3>Tidak ada kategori yang ditemukan</h3>
                                <p>Tidak ada kategori yang cocok dengan pencarian "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                                <p>Coba gunakan kata kunci yang berbeda atau <a href="categories.php">lihat semua kategori</a></p>
                            </div>
                    <?php
                        } else {
                            echo "<p>Belum ada kategori yang ditambahkan.</p>";
                        }
                    }
                    ?>
                </div>
            </section>
        </div>
        
        <aside class="sidebar">
            <div class="widget">
                <h3>Artikel Terbaru</h3>
                <?php
                // Query untuk artikel terbaru
                $recent_sql = "SELECT title, slug FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 5";
                $recent_result = $conn->query($recent_sql);
                
                if ($recent_result && $recent_result->num_rows > 0) {
                    echo "<ul>";
                    while($recent_row = $recent_result->fetch_assoc()) {
                        echo "<li><a href='article.php?slug=" . htmlspecialchars($recent_row["slug"]) . "'>" . htmlspecialchars($recent_row["title"]) . "</a></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Tidak ada artikel terbaru.</p>";
                }
                ?>
            </div>
            
            <div class="widget">
                <h3>Kategori Populer</h3>
                <?php
                // Query untuk kategori dengan artikel terbanyak
                $popular_sql = "SELECT c.name, c.slug, COUNT(a.article_id) as article_count 
                               FROM categories c
                               LEFT JOIN article_category ac ON c.category_id = ac.category_id
                               LEFT JOIN articles a ON ac.article_id = a.article_id AND a.status = 'published'
                               GROUP BY c.category_id
                               HAVING article_count > 0
                               ORDER BY article_count DESC
                               LIMIT 5";
                $popular_result = $conn->query($popular_sql);
                
                if ($popular_result && $popular_result->num_rows > 0) {
                    echo "<ul>";
                    while($popular_row = $popular_result->fetch_assoc()) {
                        echo "<li><a href='category.php?slug=" . htmlspecialchars($popular_row["slug"]) . "'>" . htmlspecialchars($popular_row["name"]) . " <small>(" . $popular_row["article_count"] . ")</small></a></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Tidak ada kategori populer.</p>";
                }
                ?>
            </div>
        </aside>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> KyubiNote. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // JavaScript untuk meningkatkan user experience - sama dengan index.php
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