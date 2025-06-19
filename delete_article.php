<?php
session_start();

// Cek jika user belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Cek apakah ID artikel ada di URL dan valid
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}

// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "blog_artikel");
// $conn = new mysqli("sql202.byethost9.com", "b9_39239718", "mutawali29#", "b9_39239718_blog_artikel");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$article_id = intval($_GET['id']);

// Mulai transaksi untuk memastikan semua query berhasil atau tidak sama sekali
$conn->begin_transaction();

try {
    /**
     * ===================================================================================
     * PERBAIKAN DIMULAI: Mengambil Data dan Memvalidasi Hak Akses
     * ===================================================================================
     */

    // Langkah 1: Ambil data artikel (author_id dan path gambar) berdasarkan ID-nya saja.
    $sql_check = "SELECT author_id, featured_image FROM articles WHERE article_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $article_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $article = $result_check->fetch_assoc();
        $stmt_check->close();

        // Langkah 2: Lakukan pengecekan otorisasi menggunakan PHP.
        $is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
        $is_owner = $_SESSION['user_id'] == $article['author_id'];

        // Lanjutkan proses hapus HANYA JIKA pengguna adalah admin ATAU pemilik.
        if ($is_admin || $is_owner) {
            
            // Hapus file gambar dari server jika ada
            if (!empty($article['featured_image']) && file_exists($article['featured_image'])) {
                unlink($article['featured_image']);
            }

            // Hapus relasi artikel-kategori
            $stmt_cat = $conn->prepare("DELETE FROM article_category WHERE article_id = ?");
            $stmt_cat->bind_param("i", $article_id);
            $stmt_cat->execute();
            $stmt_cat->close();
            
            // Hapus relasi artikel-tag
            $stmt_tag = $conn->prepare("DELETE FROM article_tag WHERE article_id = ?");
            $stmt_tag->bind_param("i", $article_id);
            $stmt_tag->execute();
            $stmt_tag->close();

            // Hapus artikel utama dari tabel 'articles'.
            // Tidak perlu lagi mengecek 'author_id' di sini karena sudah divalidasi di atas.
            $stmt_article = $conn->prepare("DELETE FROM articles WHERE article_id = ?");
            $stmt_article->bind_param("i", $article_id);
            $stmt_article->execute();
            $stmt_article->close();

            // Jika semua query berhasil, commit transaksi
            $conn->commit();

            // Set pesan sukses dan redirect
            $_SESSION['message'] = "Artikel berhasil dihapus.";
            header("Location: index.php"); // Redirect ke beranda atau my_articles.php
            exit();

        } else {
            // Jika bukan admin dan bukan pemilik, tolak aksi.
            $conn->rollback();
            http_response_code(403);
            die("Akses Ditolak. Anda tidak punya izin untuk menghapus artikel ini.");
        }
    } else {
        // Jika artikel dengan ID tersebut tidak ditemukan
        $stmt_check->close();
        $conn->rollback();
        http_response_code(404);
        die("Artikel tidak ditemukan.");
    }
     /**
     * ===================================================================================
     * PERBAIKAN SELESAI
     * ===================================================================================
     */
} catch (Exception $e) {
    // Jika terjadi error pada salah satu query, rollback semua perubahan
    $conn->rollback();
    die("Error saat menghapus data: " . $e->getMessage());
}

$conn->close();
?>