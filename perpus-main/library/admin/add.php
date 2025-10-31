<?php
// Mulai session untuk pengelolaan login admin
session_start();
// Include file koneksi database
include('../includes/db.php');

// Cek apakah admin sudah login, jika belum redirect ke halaman login
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

// Proses form ketika tombol "add" ditekan
if (isset($_POST['add'])) {
    // Escape string untuk mencegah SQL injection
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    // Konversi input string menjadi integer
    $year = (int)$_POST['year'];
    $stock = (int)$_POST['stock'];

    // Query untuk menambahkan buku baru ke database
    $query = "INSERT INTO books (title, author, year, stock) VALUES ('$title', '$author', $year, $stock)";
    if (mysqli_query($conn, $query)) {
        // Jika berhasil, kembali ke halaman daftar buku
        header("Location: books.php");
        exit;
    } else {
        // Jika gagal, tampilkan pesan error
        $error = "Failed to add book.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku - Perpustakaan Admin</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-5">
    <!-- Judul halaman -->
    <h2 class="mb-4">➕ Tambah Buku Baru</h2>

    <!-- Tampilkan pesan error jika ada -->
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <!-- Form tambah buku -->
    <form method="POST">
        <!-- Input judul buku -->
        <div class="mb-3">
            <label>Nama Buku</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <!-- Input nama pengarang -->
        <div class="mb-3">
            <label>Author</label>
            <input type="text" name="author" class="form-control" required>
        </div>

        <!-- Input tahun terbit -->
        <div class="mb-3">
            <label>Tahun</label>
            <input type="number" name="year" class="form-control" required>
        </div>

        <!-- Input jumlah stok -->
        <div class="mb-3">
            <label>Stok Buku</label>
            <!-- NOTE: Input field untuk stok tidak ada, perlu ditambahkan -->
            <input type="number" name="stock" class="form-control" required min="0">
        </div>

        <!-- Tombol submit dan cancel -->
        <button type="submit" name="add" class="btn btn-success">Tambah Buku</button>
        <a href="books.php" class="btn btn-secondary">Batalkan</a>
    </form>
</div>

</body>
</html>
