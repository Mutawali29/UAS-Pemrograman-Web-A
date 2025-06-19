# KyubiNote - Web Artikel Dinamis

KyubiNote adalah platform web artikel dinamis yang dibangun dengan PHP dan MySQL. Platform ini menyediakan antarmuka yang interaktif dan user-friendly untuk membaca, menulis, dan mengelola artikel dengan sistem multi-user yang lengkap.

## 🌟 Fitur Utama

### 📝 Manajemen Artikel
- **Tambah Artikel**: User dapat membuat artikel baru dengan editor yang mudah digunakan
- **Edit Artikel**: Pemilik artikel dapat mengedit artikel mereka sendiri
- **Hapus Artikel**: Pemilik artikel dapat menghapus artikel mereka sendiri
- **Gambar Unggulan**: Dukungan untuk menambahkan gambar featured pada artikel
- **Kategori Artikel**: Sistem kategorisasi artikel untuk organisasi yang lebih baik
- **Sistem Tag**: Pengelompokan artikel berdasarkan tag untuk pencarian yang lebih spesifik
- **Status Artikel**: Draft, Published, dan Archived untuk kontrol publikasi

### 🔍 Pencarian Dinamis
- **Pencarian Real-time**: Cari artikel berdasarkan judul, ringkasan, atau konten
- **Hasil Pencarian Interaktif**: Tampilan hasil pencarian yang informatif
- **Filter Pencarian**: Pencarian yang akurat dengan multiple parameter

### 👥 Sistem Multi-User
- **Registrasi & Login**: Sistem autentikasi yang aman
- **Role-based Access**: Pembedaan akses antara Admin dan User biasa
- **Profile Management**: Pengelolaan profil pengguna
- **Author Management**: Sistem penulis dengan bio dan informasi lengkap

### 🔐 Kontrol Akses Admin
- **Full Article Management**: Admin dapat menambah, edit, dan hapus artikel siapa saja
- **User Monitoring**: Pemantauan aktivitas dan manajemen user
- **Dashboard Admin**: Interface khusus untuk admin
- **Author Management**: Pengelolaan data penulis artikel

### 🎨 UI/UX Interaktif
- **Responsive Design**: Tampilan yang optimal di berbagai perangkat
- **Modern Interface**: Desain yang clean dan user-friendly
- **Interactive Elements**: Elemen interaktif untuk pengalaman pengguna yang lebih baik

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.6+
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Custom CSS dengan responsive design
- **Session Management**: PHP Sessions untuk autentikasi
- **File Upload**: Sistem upload gambar dengan validasi

## 📁 Struktur Project

```
KyubiNote/
├── .git/                   # Git repository
├── templates/              # Template files
│   ├── footer.php         # Footer template
│   └── header.php         # Header template
├── styles/                 # Folder CSS
│   ├── edit_article.css   # Style untuk edit artikel
│   ├── index.css          # Style utama
│   ├── login.css          # Style halaman login
│   ├── manage_users.css   # Style manajemen user
│   └── profile.css        # Style halaman profil
├── uploads/               # Folder upload gambar
├── about.php              # Halaman tentang
├── add_article.php        # Form tambah artikel
├── article.php            # Halaman detail artikel
├── authors_table_update.php # Update tabel authors
├── categories.php         # Halaman kategori
├── category.php           # Halaman kategori spesifik
├── db_config.php          # Konfigurasi database
├── delete_article.php     # Script hapus artikel
├── edit_article.php       # Form edit artikel
├── index.php              # Halaman utama dengan daftar artikel
├── login.php              # Halaman login
├── logout.php             # Script logout
├── manage_users.php       # Panel admin untuk manage users
├── my_article.php         # Daftar artikel user
├── profile.php            # Halaman profil user
├── register.php           # Halaman registrasi
├── search.php             # Halaman pencarian
└── README.md              # File dokumentasi ini
```

## 🚀 Instalasi dan Setup

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.6+
- Web server (Apache/Nginx)
- Browser modern

### Langkah Instalasi

1. **Clone atau Download Project**
   ```bash
   git clone [repository-url]
   cd KyubiNote
   ```

2. **Setup Database**
   - Buat database MySQL/MariaDB baru dengan nama `blog_artikel`
   - Import struktur database dari file SQL yang disediakan

3. **Konfigurasi Database**
   - Edit file `db_config.php`
   ```php
   <?php
   $db_host = 'localhost';
   $db_user = 'your_username';
   $db_password = 'your_password';
   $db_name = 'blog_artikel';
   ?>
   ```

4. **Setup Direktori Upload**
   - Pastikan folder `uploads/` memiliki permission write (755 atau 777)
   - Folder ini digunakan untuk menyimpan gambar featured artikel

5. **Setup Web Server**
   - Upload semua file ke direktori web server
   - Pastikan PHP dan MySQL/MariaDB berjalan dengan baik

6. **Akses Website**
   - Buka browser dan akses `http://localhost/KyubiNote`
   - Registrasi akun baru atau login dengan akun yang sudah ada

## 📋 Struktur Database

Database `blog_artikel` terdiri dari tabel-tabel berikut:

### Tabel Utama:

#### `users`
- `user_id` (Primary Key, AUTO_INCREMENT)
- `username` (VARCHAR(50), UNIQUE)
- `email` (VARCHAR(100), UNIQUE)
- `password` (VARCHAR(255), hashed)
- `role` (ENUM: 'user', 'admin')
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

#### `authors`
- `author_id` (Primary Key, AUTO_INCREMENT)
- `name` (VARCHAR(100))
- `email` (VARCHAR(100), UNIQUE)
- `bio` (TEXT)
- `profile_image` (VARCHAR(255))
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

#### `articles`
- `article_id` (Primary Key, AUTO_INCREMENT)
- `title` (VARCHAR(200))
- `slug` (VARCHAR(200), UNIQUE)
- `content` (TEXT)
- `author_id` (INT, Foreign Key ke authors)
- `excerpt` (TEXT)
- `featured_image` (VARCHAR(255))
- `status` (ENUM: 'draft', 'published', 'archived')
- `views` (INT, default 0)
- `published_at` (DATETIME)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

#### `categories`
- `category_id` (Primary Key, AUTO_INCREMENT)
- `name` (VARCHAR(50))
- `description` (TEXT)
- `slug` (VARCHAR(50), UNIQUE)
- `created_at` (TIMESTAMP)

#### `tags`
- `tag_id` (Primary Key, AUTO_INCREMENT)
- `name` (VARCHAR(50))
- `slug` (VARCHAR(50), UNIQUE)
- `created_at` (TIMESTAMP)

### Tabel Relasi:

#### `article_author`
- `article_id` (Foreign Key)
- `author_id` (Foreign Key)
- `is_main_author` (TINYINT(1))

#### `article_category`
- `article_id` (Foreign Key)
- `category_id` (Foreign Key)

#### `article_tag`
- `article_id` (Foreign Key)
- `tag_id` (Foreign Key)

## 🎯 Cara Penggunaan

### Untuk User Biasa:
1. **Registrasi/Login** - Daftar akun baru atau login dengan akun existing
2. **Baca Artikel** - Browse dan baca artikel yang tersedia
3. **Cari Artikel** - Gunakan fitur pencarian untuk menemukan artikel spesifik
4. **Tulis Artikel** - Buat artikel baru melalui menu "Tambah Artikel"
5. **Kelola Artikel** - Edit atau hapus artikel yang Anda buat melalui "My Articles"
6. **Upload Gambar** - Tambahkan gambar featured untuk artikel Anda

### Untuk Admin:
1. **Login sebagai Admin** - Login dengan akun admin
2. **Manajemen Penuh** - Kelola semua artikel dari semua user
3. **Monitor User** - Pantau aktivitas dan kelola user melalui "Manage Users"
4. **Kontrol Konten** - Moderasi dan kelola konten website
5. **Manajemen Author** - Kelola data penulis artikel

## 🔧 Fitur Keamanan

- **Password Hashing**: Password user di-hash menggunakan `password_hash()` dengan algoritma bcrypt
- **SQL Injection Protection**: Menggunakan prepared statements untuk semua query database
- **XSS Protection**: Input sanitization dan output escaping
- **Session Security**: Manajemen session yang aman dengan regenerasi session ID
- **Role-based Access Control**: Pembatasan akses berdasarkan role user/admin
- **File Upload Security**: Validasi file type dan size untuk upload gambar

## 🎨 Kustomisasi

### Mengubah Tampilan:
- Edit file CSS di folder `styles/` untuk mengubah styling
- Modifikasi file template di folder `templates/` untuk header dan footer
- Edit file PHP individual untuk mengubah struktur halaman

### Menambah Fitur:
- Tambahkan tabel database baru jika diperlukan
- Buat file PHP baru untuk fitur tambahan
- Update navigasi di `templates/header.php` untuk akses fitur baru
- Tambahkan CSS baru di folder `styles/`

### Konfigurasi Upload:
- Sesuaikan pengaturan upload di file yang menggunakan upload gambar
- Ubah direktori upload dengan memodifikasi path `uploads/`

## 📊 Data Sample

Database sudah berisi data sample:
- **Users**: 5 pengguna (4 user biasa, 1 admin)
- **Authors**: 5 penulis dengan bio lengkap
- **Articles**: 3 artikel dengan status published
- **Categories**: 4 kategori (Teknologi, Produktivitas, Bisnis, Kesehatan)
- **Tags**: 5 tag populer

## 🤝 Kontribusi

Jika Anda ingin berkontribusi pada project ini:
1. Fork repository ini
2. Buat branch baru untuk fitur Anda
3. Commit perubahan Anda
4. Push ke branch
5. Buat Pull Request

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:
- Buka issue di repository ini
- Atau hubungi pengembang melalui email

## 📝 License

Project ini dilisensikan di bawah [MIT License]. Silakan lihat file LICENSE untuk detail lebih lanjut.

## 🚀 Roadmap

### Fitur yang Akan Datang:
- [ ] Sistem komentar pada artikel
- [ ] Rating dan review artikel
- [ ] Newsletter subscription
- [ ] Social media integration
- [ ] Advanced search filters
- [ ] Article bookmarking
- [ ] Rich text editor (WYSIWYG)
- [ ] Image gallery management
- [ ] SEO optimization tools
- [ ] Analytics dashboard
- [ ] Email notifications
- [ ] Multi-language support

---

**KyubiNote** - Platform artikel dinamis yang powerful dan user-friendly untuk semua kebutuhan content management Anda.

*Dibuat dengan ❤️ menggunakan PHP dan MySQL/MariaDB*
