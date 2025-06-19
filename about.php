<?php
// Koneksi ke database (opsional, bisa diperlukan jika ada data dinamis di masa depan)
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set judul halaman sebelum memanggil header
$page_title = "Tentang KyubiNote";
include 'templates/header.php';
?>

<main class="container">
    <div class="about-page">
        <section class="page-header" style="text-align: center;">
            <h1 class="page-title" style="border-bottom: none;">Tentang KyubiNote</h1>
            <p class="page-description" style="font-size: 1.2em; max-width: 700px; margin: 0 auto;">
                Membagi Wawasan, Menggali Inspirasi.
            </p>
        </section>

        <section class="vision-mission">
            <div class="vision-mission-content">
                <h2>Platform Anda untuk Ide Berkualitas</h2>
                <p>QuotientNote lahir dari keyakinan bahwa setiap orang memiliki cerita, pengetahuan, atau gagasan berharga yang layak untuk dibagikan. Di tengah lautan informasi yang bising, kami hadir untuk menjadi oase bagi tulisan-tulisan yang mendalam, informatif, dan menginspirasi. Nama "QuotientNote" sendiri melambangkan 'catatan hasil pemikiran'—sebuah ruang untuk menuangkan buah pikiran yang telah diproses.</p>
                <p>Misi kami adalah memberdayakan para penulis untuk menjangkau audiens yang lebih luas dan menyediakan platform yang bersih dan nyaman bagi para pembaca untuk menemukan konten berkualitas tanpa distraksi.</p>
            </div>
            <div class="vision-mission-image">
                <img src="https://images.unsplash.com/photo-1455390582262-044cdead277a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1073&q=80" alt="Menulis inspirasi">
            </div>
        </section>

        <section class="team-section">
            <h2>Tim di Balik KyubiNote</h2>
            <div class="team-grid">
                <div class="team-member-card">
                    <img src="https://i.pravatar.cc/150?img=11" alt="Foto Anggota Tim 1" class="team-member-photo">
                    <h3 class="team-member-name">Andi Purnomo</h3>
                    <p class="team-member-title">Founder & Chief Editor</p>
                    <p class="team-member-bio">Penggagas QuotientNote yang percaya pada kekuatan tulisan untuk mengubah dunia, satu artikel pada satu waktu.</p>
                </div>

                <div class="team-member-card">
                    <img src="https://i.pravatar.cc/150?img=5" alt="Foto Anggota Tim 2" class="team-member-photo">
                    <h3 class="team-member-name">Citra Lestari</h3>
                    <p class="team-member-title">Community Manager</p>
                    <p class="team-member-bio">Menjaga kehangatan komunitas penulis dan pembaca, memastikan setiap orang merasa diterima dan didengar.</p>
                </div>

                <div class="team-member-card">
                    <img src="https://i.pravatar.cc/150?img=32" alt="Foto Anggota Tim 3" class="team-member-photo">
                    <h3 class="team-member-name">Bayu Santoso</h3>
                    <p class="team-member-title">Lead Developer</p>
                    <p class="team-member-bio">Otak di balik fungsionalitas dan kenyamanan platform, selalu mencari cara untuk membuat QuotientNote lebih baik.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <h2>Bergabunglah dengan Kami</h2>
            <p>Apakah Anda seorang pembaca yang haus akan pengetahuan atau seorang penulis yang siap berbagi ide? QuotientNote adalah tempat yang tepat untuk Anda.</p>
            <div class="btn-group">
                <a href="add_article.php" class="btn btn-primary">Mulai Menulis</a>
                <a href="index.php" class="btn btn-secondary">Jelajahi Artikel</a>
            </div>
        </section>
    </div>
</main>

<?php
include 'templates/footer.php';
?>