<?php
// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil slug kategori dari URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// Query untuk mendapatkan info kategori
$cat_sql = "SELECT category_id, name, description FROM categories WHERE slug = ?";
$cat_stmt = $conn->prepare($cat_sql);
$cat_stmt->bind_param("s", $slug);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();

// Jika kategori tidak ditemukan, alihkan ke halaman utama
if ($cat_result->num_rows == 0) {
    header("Location: index.php"); // Atau ke halaman 404
    exit;
}

$category = $cat_result->fetch_assoc();

// Query untuk artikel dalam kategori ini
$sql = "SELECT a.article_id, a.title, a.slug, a.excerpt, a.featured_image, a.published_at, u.username as author_name
        FROM articles a
        JOIN article_category ac ON a.article_id = ac.article_id
        JOIN users u ON a.author_id = u.user_id
        WHERE ac.category_id = ? AND a.status = 'published'
        GROUP BY a.article_id
        ORDER BY a.published_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $category['category_id']);
$stmt->execute();
$result = $stmt->get_result();

// Set judul halaman sebelum memanggil header
$page_title = "Kategori: " . htmlspecialchars($category["name"]);
include 'templates/header.php';
?>

<main class="container page-layout">
    <div class="main-content">
        <section class="page-header">
            <h1>Kategori: <?php echo htmlspecialchars($category["name"]); ?></h1>
            <?php if(!empty($category["description"])): ?>
                <p class="page-description"><?php echo htmlspecialchars($category["description"]); ?></p>
            <?php endif; ?>
        </section>
        
        <section class="articles-in-category">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
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
                            <small>Oleh <?php echo htmlspecialchars($row['author_name']); ?> pada <?php echo $formatted_date; ?></small>
                            <p class="excerpt"><?php echo htmlspecialchars($row["excerpt"]); ?></p>
                            <a href="article.php?slug=<?php echo htmlspecialchars($row["slug"]); ?>" class="read-more">Baca selengkapnya</a>
                        </div>
                    </article>
            <?php
                }
            } else {
                echo "<p>Belum ada artikel dalam kategori ini.</p>";
            }
            ?>
        </section>
    </div>
    
    <aside class="sidebar">
        <div class="widget">
            <h3>Semua Kategori</h3>
            <?php
            $all_cat_sql = "SELECT c.name, c.slug, COUNT(a.article_id) as article_count 
                            FROM categories c
                            LEFT JOIN article_category ac ON c.category_id = ac.category_id
                            LEFT JOIN articles a ON ac.article_id = a.article_id AND a.status = 'published'
                            GROUP BY c.category_id
                            ORDER BY c.name";
            $all_cat_result = $conn->query($all_cat_sql);
            
            if ($all_cat_result && $all_cat_result->num_rows > 0) {
                echo "<ul>";
                while($cat_row = $all_cat_result->fetch_assoc()) {
                    $active = ($cat_row["slug"] == $slug) ? ' class="active"' : '';
                    echo "<li{$active}><a href='category.php?slug=" . htmlspecialchars($cat_row["slug"]) . "'>" . htmlspecialchars($cat_row["name"]) . " <span>(" . $cat_row["article_count"] . ")</span></a></li>";
                }
                echo "</ul>";
            }
            ?>
        </div>
    </aside>
</main>

<?php
include 'templates/footer.php';
?>