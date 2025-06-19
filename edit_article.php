<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "blog_artikel");
// $conn = new mysqli("sql202.byethost9.com", "b9_39239718", "mutawali29#", "b9_39239718_blog_artikel");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}
$article_id = $_GET['id'];

$errors = [];
$success_message = '';

// Ambil data artikel dan validasi hak akses
$sql_article = "SELECT * FROM articles WHERE article_id = ?";
$stmt_article = $conn->prepare($sql_article);
$stmt_article->bind_param("i", $article_id);
$stmt_article->execute();
$result_article = $stmt_article->get_result();

if ($result_article->num_rows === 0) {
    http_response_code(404);
    die("Artikel tidak ditemukan.");
}
$article = $result_article->fetch_assoc();
$stmt_article->close();

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
$is_owner = $_SESSION['user_id'] == $article['author_id'];

if (!$is_admin && !$is_owner) {
    http_response_code(403);
    die("Akses Ditolak. Anda tidak memiliki izin untuk mengedit artikel ini.");
}

// Ambil KATEGORI & TAG yang terhubung
$selected_categories = array_column($conn->query("SELECT category_id FROM article_category WHERE article_id = $article_id")->fetch_all(), 0);
$selected_tags = array_column($conn->query("SELECT tag_id FROM article_tag WHERE article_id = $article_id")->fetch_all(), 0);

// Ambil SEMUA Kategori & Tag
$all_categories = $conn->query("SELECT * FROM categories ORDER BY name");
$all_tags = $conn->query("SELECT * FROM tags ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $excerpt = trim($_POST['excerpt']);
    $new_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $new_tags = isset($_POST['tags']) ? $_POST['tags'] : [];

    if (empty($title) || empty($content)) {
        $errors[] = "Judul dan Konten tidak boleh kosong.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $featured_image_path = $article['featured_image'];
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
                if (!empty($featured_image_path) && file_exists($featured_image_path)) {
                    unlink($featured_image_path);
                }
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $new_filename = uniqid(rand(), true) . '-' . basename($_FILES['featured_image']['name']);
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                    $featured_image_path = $upload_path;
                } else {
                    throw new Exception("Gagal mengunggah gambar baru.");
                }
            }
            
            $sql_update = "UPDATE articles SET title = ?, content = ?, excerpt = ?, featured_image = ? WHERE article_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssssi", $title, $content, $excerpt, $featured_image_path, $article_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Update Kategori
            $conn->query("DELETE FROM article_category WHERE article_id = $article_id");
            if (!empty($new_categories)) {
                $sql_cat_insert = "INSERT INTO article_category (article_id, category_id) VALUES (?, ?)";
                $stmt_cat_insert = $conn->prepare($sql_cat_insert);
                foreach ($new_categories as $cat_id) {
                    $stmt_cat_insert->bind_param("ii", $article_id, $cat_id);
                    $stmt_cat_insert->execute();
                }
                $stmt_cat_insert->close();
            }

            // Update Tag
            $conn->query("DELETE FROM article_tag WHERE article_id = $article_id");
            if (!empty($new_tags)) {
                $sql_tag_insert = "INSERT INTO article_tag (article_id, tag_id) VALUES (?, ?)";
                $stmt_tag_insert = $conn->prepare($sql_tag_insert);
                foreach ($new_tags as $tag_id) {
                    $stmt_tag_insert->bind_param("ii", $article_id, $tag_id);
                    $stmt_tag_insert->execute();
                }
                $stmt_tag_insert->close();
            }

            $conn->commit();
            header("Location: edit_article.php?id=$article_id&status=updated");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] == 'updated') {
    $success_message = "Artikel berhasil diperbarui!";
}

$page_title = "Edit Artikel";
include 'templates/header.php';
?>

<main class="container">
    <div class="form-container">
        <h2>Edit Artikel</h2>

        <?php if ($success_message): ?>
            <div class="message success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="message error-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="edit_article.php?id=<?php echo $article_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Judul</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="excerpt">Ringkasan (Excerpt)</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($article['excerpt']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="content">Konten</label>
                <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($article['content']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Gambar Utama Saat Ini</label>
                <div class="current-image-preview">
                    <?php if (!empty($article['featured_image']) && file_exists($article['featured_image'])): ?>
                        <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="Current Image">
                    <?php else: ?>
                        <p>Tidak ada gambar.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="featured_image">Ganti Gambar Utama</label>
                <input type="file" id="featured_image" name="featured_image" accept="image/*">
                <small>Kosongkan jika tidak ingin mengubah gambar.</small>
            </div>

            <div class="form-group">
                <label for="categories">Kategori</label>
                <select id="categories" name="categories[]" multiple>
                    <?php $all_categories->data_seek(0);
                    while($cat = $all_categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo in_array($cat['category_id'], $selected_categories) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tags">Tag</label>
                <select id="tags" name="tags[]" multiple>
                    <?php $all_tags->data_seek(0);
                    while($tag = $all_tags->fetch_assoc()): ?>
                        <option value="<?php echo $tag['tag_id']; ?>" <?php echo in_array($tag['tag_id'], $selected_tags) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Artikel</button>
        </form>
    </div>
</main>

<?php
include 'templates/footer.php';
?>