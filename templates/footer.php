<footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> KyubiNote. All rights reserved. </p>
        </div>
    </footer>
</body>
</html>
<?php
// Jika variabel koneksi ada, tutup koneksi di sini
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>