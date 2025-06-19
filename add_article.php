<!-- add_article.php  -->
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Logika untuk mendapatkan atau membuat author_id
$user_id = $_SESSION['user_id'];
$author_query = "SELECT author_id FROM authors WHERE email = (SELECT email FROM users WHERE user_id = ?)";
$author_stmt = $conn->prepare($author_query);
$author_stmt->bind_param("i", $user_id);
$author_stmt->execute();
$author_result = $author_stmt->get_result();

$author_id = null;
if ($author_result->num_rows > 0) {
    $author_row = $author_result->fetch_assoc();
    $author_id = $author_row['author_id'];
} else {
    $create_author_sql = "INSERT INTO authors (name, email, bio) SELECT username, email, CONCAT('Bio for ', username) FROM users WHERE user_id = ?";
    $create_author_stmt = $conn->prepare($create_author_sql);
    $create_author_stmt->bind_param("i", $user_id);
    $create_author_stmt->execute();
    $author_id = $conn->insert_id;
    $create_author_stmt->close();
}
$author_stmt->close();

$categories_sql = "SELECT category_id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$tags_sql = "SELECT tag_id, name FROM tags ORDER BY name";
$tags_result = $conn->query($tags_sql);
$title = $content = $excerpt = '';
$selected_categories = $selected_tags = [];
$error_message = '';
$success_message = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    if (empty($title) || empty($content)) {
        $error_message = "Judul dan konten artikel harus diisi.";
    } else {
        $slug = create_slug($title);
        $check_slug_sql = "SELECT article_id FROM articles WHERE slug = ?";
        $check_stmt = $conn->prepare($check_slug_sql);
        $check_stmt->bind_param("s", $slug);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        $check_stmt->close();
        
        $featured_image = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = array("jpeg", "jpg", "png", "gif");
            if (in_array($file_ext, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $featured_image = $upload_path;
                } else {
                    $error_message = "Gagal mengunggah gambar.";
                }
            } else {
                $error_message = "Format gambar tidak didukung.";
            }
        }
        
        if (empty($error_message)) {
            $conn->begin_transaction();
            try {
                $article_sql = "INSERT INTO articles (title, slug, content, author_id, excerpt, featured_image, status, published_at) VALUES (?, ?, ?, ?, ?, ?, 'published', NOW())";
                $article_stmt = $conn->prepare($article_sql);
                $article_stmt->bind_param("sssiss", $title, $slug, $content, $author_id, $excerpt, $featured_image);
                $article_stmt->execute();
                $article_id = $conn->insert_id;
                $article_stmt->close();
                
                if (!empty($selected_categories)) {
                    $category_sql = "INSERT INTO article_category (article_id, category_id) VALUES (?, ?)";
                    $category_stmt = $conn->prepare($category_sql);
                    foreach ($selected_categories as $category_id) {
                        $category_stmt->bind_param("ii", $article_id, $category_id);
                        $category_stmt->execute();
                    }
                    $category_stmt->close();
                }
                
                if (!empty($selected_tags)) {
                    $tag_sql = "INSERT INTO article_tag (article_id, tag_id) VALUES (?, ?)";
                    $tag_stmt = $conn->prepare($tag_sql);
                    foreach ($selected_tags as $tag_id) {
                        $tag_stmt->bind_param("ii", $article_id, $tag_id);
                        $tag_stmt->execute();
                    }
                    $tag_stmt->close();
                }
                
                $conn->commit();
                $success_message = "Artikel berhasil ditambahkan!";
                $title = $content = $excerpt = '';
                $selected_categories = $selected_tags = [];
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}

function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', ' ', $string);
    $string = preg_replace('/\s/', '-', $string);
    return $string;
}

// Set judul halaman sebelum memanggil header
$page_title = "Tambah Artikel Baru";
include 'templates/header.php';
?>

<main class="container">
    <div class="form-container">
        <h2>Tambah Artikel Baru</h2>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success-message">
                <?php echo $success_message; ?>
                <p><a href="index.php">Kembali ke Beranda</a> atau <a href="add_article.php">Tambah Artikel Lagi</a></p>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Judul Artikel *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Konten Artikel *</label>
                <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="excerpt">Ringkasan (Excerpt)</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($excerpt); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="featured_image">Gambar Utama</label>
                <input type="file" id="featured_image" name="featured_image" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="categories">Kategori</label>
                <select id="categories" name="categories[]" multiple>
                    <?php 
                    if ($categories_result && $categories_result->num_rows > 0) {
                        $categories_result->data_seek(0);
                        while($cat_row = $categories_result->fetch_assoc()) {
                            $selected = in_array($cat_row['category_id'], $selected_categories) ? 'selected' : '';
                            echo "<option value='" . $cat_row['category_id'] . "' $selected>" . htmlspecialchars($cat_row['name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tags">Tags</label>
                <select id="tags" name="tags[]" multiple>
                    <?php 
                    if ($tags_result && $tags_result->num_rows > 0) {
                        $tags_result->data_seek(0);
                        while($tag_row = $tags_result->fetch_assoc()) {
                            $selected = in_array($tag_row['tag_id'], $selected_tags) ? 'selected' : '';
                            echo "<option value='" . $tag_row['tag_id'] . "' $selected>" . htmlspecialchars($tag_row['name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Publikasikan Artikel</button>
        </form>
    </div>
</main>

<?php
include 'templates/footer.php';
?>