<!-- search.php  -->
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
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';
// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$results = [];
$total_results = 0;
$search_performed = false;
if (!empty($search_query)) {
    $search_performed = true;
    
    // Query untuk menghitung total hasil
    $count_sql = "SELECT COUNT(DISTINCT a.article_id) as total
                  FROM articles a
                  LEFT JOIN article_category ac ON a.article_id = ac.article_id
                  LEFT JOIN categories c ON ac.category_id = c.category_id
                  LEFT JOIN users u ON a.author_id = u.user_id
                  WHERE a.status = 'published' 
                  AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ? OR u.username LIKE ?)";
    
    $params = ["%{$search_query}%", "%{$search_query}%", "%{$search_query}%", "%{$search_query}%"];
    $types = "ssss";
    
    // Tambahkan filter kategori jika ada
    if (!empty($category_filter)) {
        $count_sql .= " AND c.slug = ?";
        $params[] = $category_filter;
        $types .= "s";
    }
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_results = $count_result->fetch_assoc()['total'];
    
    // Query untuk mengambil hasil pencarian
    $search_sql = "SELECT a.article_id, a.title, a.excerpt, a.slug, a.featured_image, a.published_at, a.author_id,
                          GROUP_CONCAT(DISTINCT c.name) AS categories, u.username as author_name
                   FROM articles a
                   LEFT JOIN article_category ac ON a.article_id = ac.article_id
                   LEFT JOIN categories c ON ac.category_id = c.category_id
                   LEFT JOIN users u ON a.author_id = u.user_id
                   WHERE a.status = 'published' 
                   AND (a.title LIKE ? OR a.excerpt LIKE ? OR a.content LIKE ? OR u.username LIKE ?)";
    
    // Tambahkan filter kategori jika ada
    if (!empty($category_filter)) {
        $search_sql .= " AND c.slug = ?";
    }
    
    $search_sql .= " GROUP BY a.article_id";
    
    // Tambahkan sorting
    switch ($sort_by) {
        case 'title':
            $search_sql .= " ORDER BY a.title ASC";
            break;
        case 'author':
            $search_sql .= " ORDER BY u.username ASC";
            break;
        default:
            $search_sql .= " ORDER BY a.published_at DESC";
    }
    
    $search_sql .= " LIMIT ? OFFSET ?";
    
    $search_params = $params;
    $search_params[] = $per_page;
    $search_params[] = $offset;
    $search_types = $types . "ii";
    
    $search_stmt = $conn->prepare($search_sql);
    $search_stmt->bind_param($search_types, ...$search_params);
    $search_stmt->execute();
    $results = $search_stmt->get_result();
}
// Hitung pagination
$total_pages = ceil($total_results / $per_page);
$page_title = !empty($search_query) ? "Pencarian: " . htmlspecialchars($search_query) : "Pencarian Artikel";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - KyubiNote</title>
    <link rel="stylesheet" href="styles/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS untuk halaman pencarian */
        .search-page {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .search-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .search-header h1 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 2em;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-input-group {
            flex: 1;
            min-width: 250px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .search-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-select, .sort-select {
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            background: white;
            font-size: 14px;
        }
        
        .search-btn {
            padding: 12px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .search-btn:hover {
            background: #0056b3;
        }
        
        .search-results {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .results-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        
        .results-info {
            color: #6c757d;
            font-size: 14px;
        }
        
        .results-stats {
            font-weight: 600;
            color: #333;
        }
        
        .search-result-item {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .result-content {
            display: flex;
            gap: 20px;
        }
        
        .result-image {
            flex-shrink: 0;
            width: 120px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .result-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .result-image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 24px;
        }
        
        .result-info {
            flex: 1;
        }
        
        .result-title {
            font-size: 1.4em;
            font-weight: 600;
            margin: 0 0 10px 0;
        }
        
        .result-title a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .result-title a:hover {
            color: #007bff;
        }
        
        .result-excerpt {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .result-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #6c757d;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .result-categories {
            margin: 10px 0;
        }
        
        .category-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-right: 5px;
            margin-bottom: 3px;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 30px;
            color: #6c757d;
        }
        
        .no-results i {
            font-size: 4em;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .no-results h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .search-suggestions {
            margin-top: 20px;
            text-align: left;
        }
        
        .search-suggestions h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .suggestion-list {
            list-style: none;
            padding: 0;
        }
        
        .suggestion-list li {
            margin-bottom: 8px;
        }
        
        .suggestion-list a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        
        .suggestion-list a:hover {
            text-decoration: underline;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-btn:hover {
            color: #0056b3;
        }
        
        .search-tips {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .search-tips h4 {
            margin: 0 0 15px 0;
            color: #333;
        }
        
        .tips-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .tips-list li {
            margin-bottom: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .tips-list i {
            color: #28a745;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .search-page {
                padding: 0 15px;
            }
            
            .search-header {
                padding: 20px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input-group {
                min-width: auto;
            }
            
            .search-filters {
                justify-content: space-between;
            }
            
            .result-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .result-image {
                width: 100%;
                height: 150px;
            }
            
            .search-result-item {
                padding: 20px;
            }
            
            .pagination {
                gap: 5px;
            }
            
            .pagination a, .pagination span {
                padding: 6px 10px;
                font-size: 14px;
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

    <main class="search-page">
        <a href="javascript:history.back()" class="back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        
        <div class="search-header">
            <h1><i class="fas fa-search"></i> Pencarian Artikel</h1>
            <form method="GET" action="search.php" class="search-form">
                <div class="search-input-group">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Cari artikel, penulis, atau konten..." 
                        value="<?php echo htmlspecialchars($search_query); ?>"
                        required
                    >
                </div>
                
                <div class="search-filters">
                    <select name="category" class="filter-select">
                        <option value="">Semua Kategori</option>
                        <?php
                        $cat_sql = "SELECT name, slug FROM categories ORDER BY name";
                        $cat_result = $conn->query($cat_sql);
                        if ($cat_result && $cat_result->num_rows > 0) {
                            while($cat_row = $cat_result->fetch_assoc()) {
                                $selected = ($category_filter == $cat_row['slug']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($cat_row['slug']) . "' $selected>" . htmlspecialchars($cat_row['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    
                    <select name="sort" class="sort-select">
                        <option value="date" <?php echo ($sort_by == 'date') ? 'selected' : ''; ?>>Terbaru</option>
                        <option value="title" <?php echo ($sort_by == 'title') ? 'selected' : ''; ?>>Judul A-Z</option>
                        <option value="author" <?php echo ($sort_by == 'author') ? 'selected' : ''; ?>>Penulis A-Z</option>
                    </select>
                </div>
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Cari
                </button>
            </form>
        </div>

        <?php if ($search_performed): ?>
            <div class="search-results">
                <div class="results-header">
                    <div class="results-info">
                        <span class="results-stats"><?php echo $total_results; ?> hasil</span> 
                        ditemukan untuk "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                        <?php if (!empty($category_filter)): ?>
                            dalam kategori "<strong><?php
                            $cat_name_sql = "SELECT name FROM categories WHERE slug = ?";
                            $cat_name_stmt = $conn->prepare($cat_name_sql);
                            $cat_name_stmt->bind_param("s", $category_filter);
                            $cat_name_stmt->execute();
                            $cat_name_result = $cat_name_stmt->get_result();
                            if ($cat_name_result->num_rows > 0) {
                                echo htmlspecialchars($cat_name_result->fetch_assoc()['name']);
                            }
                            ?></strong>"
                        <?php endif; ?>
                        
                        <?php if ($total_results > $per_page): ?>
                            <span style="margin-left: 15px; font-size: 13px;">
                                Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($results->num_rows > 0): ?>
                    <?php while($row = $results->fetch_assoc()): ?>
                        <div class="search-result-item">
                            <div class="result-content">
                                <div class="result-image">
                                    <?php if (!empty($row['featured_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($row['featured_image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                                    <?php else: ?>
                                        <div class="result-image-placeholder">
                                            <i class="far fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="result-info">
                                    <h3 class="result-title">
                                        <a href="article.php?slug=<?php echo htmlspecialchars($row['slug']); ?>">
                                            <?php echo htmlspecialchars($row['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($row['categories'])): ?>
                                        <div class="result-categories">
                                            <?php foreach(explode(',', $row['categories']) as $category): ?>
                                                <span class="category-tag"><?php echo htmlspecialchars(trim($category)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="result-excerpt">
                                        <?php 
                                        $excerpt = !empty($row['excerpt']) ? $row['excerpt'] : substr(strip_tags($row['title']), 0, 150);
                                        echo htmlspecialchars($excerpt);
                                        if (strlen($excerpt) > 150) echo '...';
                                        ?>
                                    </p>
                                    
                                    <div class="result-meta">
                                        <span class="meta-item">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo (new DateTime($row['published_at']))->format('d M Y'); ?>
                                        </span>
                                        <span class="meta-item">
                                            <i class="far fa-user"></i>
                                            <?php echo !empty($row['author_name']) ? htmlspecialchars($row['author_name']) : 'Penulis'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?search=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo ($page-1); ?>">
                                    <i class="fas fa-chevron-left"></i> Sebelumnya
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?search=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?search=<?php echo urlencode($search_query); ?>&category=<?php echo urlencode($category_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&page=<?php echo ($page+1); ?>">
                                    Berikutnya <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Tidak ada hasil ditemukan</h3>
                        <p>Maaf, tidak ada artikel yang cocok dengan pencarian "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
                        
                        <div class="search-suggestions">
                            <h4>Saran pencarian:</h4>
                            <ul class="suggestion-list">
                                <li>• Periksa ejaan kata kunci</li>
                                <li>• Gunakan kata kunci yang lebih umum</li>
                                <li>• Coba kata kunci yang berbeda</li>
                                <li>• Gunakan lebih sedikit kata kunci</li>
                            </ul>
                            
                            <h4 style="margin-top: 20px;">Artikel populer:</h4>
                            <ul class="suggestion-list">
                                <?php
                                $popular_sql = "SELECT title, slug FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 5";
                                $popular_result = $conn->query($popular_sql);
                                if ($popular_result && $popular_result->num_rows > 0) {
                                    while($popular_row = $popular_result->fetch_assoc()) {
                                        echo "<li><a href='article.php?slug=" . htmlspecialchars($popular_row['slug']) . "'>" . htmlspecialchars($popular_row['title']) . "</a></li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="search-tips">
                <h4><i class="fas fa-lightbulb"></i> Tips Pencarian</h4>
                <ul class="tips-list">
                    <li><i class="fas fa-check"></i> Gunakan kata kunci yang spesifik untuk hasil yang lebih akurat</li>
                    <li><i class="fas fa-check"></i> Cari berdasarkan judul artikel, konten, atau nama penulis</li>
                    <li><i class="fas fa-check"></i> Filter berdasarkan kategori untuk mempersempit hasil</li>
                    <li><i class="fas fa-check"></i> Urutkan hasil berdasarkan tanggal, judul, atau penulis</li>
                </ul>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> KyubiNote. All rights reserved.ff</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            const searchForm = document.querySelector('.search-form');
            
            // Focus pada input search saat halaman dimuat
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
            
            // Prevent empty search
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    if (searchInput.value.trim() === '') {
                        e.preventDefault();
                        searchInput.focus();
                        searchInput.style.borderColor = '#dc3545';
                        setTimeout(() => {
                            searchInput.style.borderColor = '#ddd';
                        }, 2000);
                    }
                });
            }
            
            // Highlight search terms in results
            const searchTerm = '<?php echo htmlspecialchars($search_query); ?>';
            if (searchTerm) {
                highlightSearchTerm(searchTerm);
            }
        });
        
        function highlightSearchTerm(term) {
            const resultItems = document.querySelectorAll('.search-result-item');
            const regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            
            resultItems.forEach(item => {
                const title = item.querySelector('.result-title a');
                const excerpt = item.querySelector('.result-excerpt');
                
                if (title) {
                    title.innerHTML = title.innerHTML.replace(regex, '<mark style="background: #fff3cd; padding: 1px 2px;">$1</mark>');
                }
                if (excerpt) {
                    excerpt.innerHTML = excerpt.innerHTML.replace(regex, '<mark style="background: #fff3cd; padding: 1px 2px;">$1</mark>');
                }
            });
        }
    </script>
</body>
</html>

<?php 
// Cleanup prepared statements
if (isset($count_stmt)) $count_stmt->close();
if (isset($search_stmt)) $search_stmt->close();
if (isset($cat_name_stmt)) $cat_name_stmt->close();
$conn->close(); 
?>