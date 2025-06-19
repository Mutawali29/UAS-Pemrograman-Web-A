<!-- my_article.php  -->
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// First, we need to find the author_id for this user
$author_query = "SELECT author_id FROM authors WHERE email = (SELECT email FROM users WHERE user_id = ?)";
$author_stmt = $conn->prepare($author_query);
$author_stmt->bind_param("i", $user_id);
$author_stmt->execute();
$author_result = $author_stmt->get_result();

if ($author_result->num_rows > 0) {
    $author_row = $author_result->fetch_assoc();
    $author_id = $author_row['author_id'];
    
    // Now query articles by this author
    $sql = "SELECT a.article_id, a.title, a.excerpt, a.slug, a.featured_image, a.status, a.published_at,
                   GROUP_CONCAT(DISTINCT c.name) AS categories
            FROM articles a
            JOIN article_author aa ON a.article_id = aa.article_id
            LEFT JOIN article_category ac ON a.article_id = ac.article_id
            LEFT JOIN categories c ON ac.category_id = c.category_id
            WHERE aa.author_id = ?
            GROUP BY a.article_id
            ORDER BY a.published_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $author_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // User doesn't have an author record yet
    // Let's create one
    $create_author_sql = "INSERT INTO authors (name, email, bio) 
                          SELECT username, email, CONCAT('Bio for ', username) 
                          FROM users WHERE user_id = ?";
    $create_author_stmt = $conn->prepare($create_author_sql);
    $create_author_stmt->bind_param("i", $user_id);
    $create_author_stmt->execute();
    
    $author_id = $conn->insert_id;
    $create_author_stmt->close();
    
    // Set empty result since this is a new author with no articles
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Saya - My Blog</title>
</head>
<body>
    <header>
        <div class="container">
            <h1>My Blog</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="categories.php">Kategori</a></li>
                    <li><a href="about.php">Tentang</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="user-menu">
                            <button class="user-menu-btn">
                                <?php echo htmlspecialchars($_SESSION['username']); ?> ▼
                            </button>
                            <div class="user-menu-content">
                                <a href="profile.php">Profile</a>
                                <a href="my_articles.php">Artikel Saya</a>
                                <a href="add_article.php">Tambah Artikel</a>
                                <a href="logout.php">Logout</a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="auth-buttons">
                            <a href="login.php" class="login-btn">Login</a>
                            <a href="register.php" class="register-btn">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="my-articles">
            <h2>Artikel Saya</h2>
            
            <div class="action-buttons">
                <a href="add_article.php" class="add-article-btn">Tambah Artikel Baru</a>
            </div>
            
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $categories = !empty($row["categories"]) ? explode(',', $row["categories"]) : [];
                    $date = new DateTime($row["published_at"]);
                    $formatted_date = $date->format('d F Y');
                    $status_class = strtolower($row["status"]);
                    ?>
                    
                    <article class="article-item <?php echo $status_class; ?>">
                        <div class="article-content">
                            <h3>
                                <a href="article.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>">
                                    <?php echo htmlspecialchars($row["title"]); ?>
                                </a>
                            </h3>
                            
                            <div class="article-meta">
                                <span class="status <?php echo $status_class; ?>"><?php echo $row["status"]; ?></span>
                                <span class="date"><?php echo $formatted_date; ?></span>
                                <div class="categories">
                                    <?php 
                                    if (!empty($categories)) {
                                        foreach($categories as $category) {
                                            if (!empty(trim($category))) {
                                                echo '<span class="category">' . htmlspecialchars(trim($category)) . '</span>';
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($row["excerpt"])): ?>
                                <p class="excerpt"><?php echo htmlspecialchars($row["excerpt"]); ?></p>
                            <?php endif; ?>
                            
                            <div class="article-actions">
                                <a href="edit_article.php?id=<?php echo $row["article_id"]; ?>" class="edit-btn">Edit</a>
                                <?php if ($row["status"] === 'published'): ?>
                                    <a href="unpublish_article.php?id=<?php echo $row["article_id"]; ?>" class="unpublish-btn">Arsipkan</a>
                                <?php else: ?>
                                    <a href="publish_article.php?id=<?php echo $row["article_id"]; ?>" class="publish-btn">Publikasikan</a>
                                <?php endif; ?>
                                <a href="delete_article.php?id=<?php echo $row["article_id"]; ?>" class="delete-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus artikel ini?');">Hapus</a>
                            </div>
                        </div>
                    </article>
                    
                    <?php
                }
            } else {
                echo "<p>Anda belum memiliki artikel.</p>";
                echo "<p><a href='add_article.php'>Buat artikel pertama Anda sekarang!</a></p>";
            }
            ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> My Blog. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>

<?php
// Close database connections
if (isset($stmt)) $stmt->close();
if (isset($author_stmt)) $author_stmt->close();
$conn->close();
?>