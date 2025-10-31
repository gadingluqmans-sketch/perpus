<?php
session_start();
include('../includes/db.php');

// Check login
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Atur Buku - Perpustakaan Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">📚 Perpustakaan Admin</a>
    <div class="d-flex">
      <a href="index.php" class="btn btn-outline-light btn-sm me-2">Beranda</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4">📘 Atur Buku</h2>

  <a href="add.php" class="btn btn-primary mb-3">+ Tambah Buku Baru</a>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>No</th>
        <th>Judul Buku</th>
        <th>Penulis</th>
        <th>Tahun</th>
        <th>Stok</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $result = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC");
      $no = 1;
      while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
          <td>{$no}</td>
          <td>{$row['title']}</td>
          <td>{$row['author']}</td>
          <td>{$row['year']}</td>
          <td>{$row['stock']}</td>
          <td>
            <a href='edit.php?id={$row['id']}' class='btn btn-sm btn-warning'>Ubah</a>
            <a href='delete.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Yakin ingin menghapus buku ini?');\">Hapus</a>
          </td>
        </tr>";
        $no++;
      }
      ?>
    </tbody>
  </table>
</div>

<footer class="text-center text-muted py-3">
  &copy; <?= date('Y'); ?> Sistem Informasi Perpustakaan
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
