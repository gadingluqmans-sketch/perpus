<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['add'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $stock = (int)$_POST['stock'];

    $query = "INSERT INTO books (title, author, year, stock) VALUES ('$title', '$author', $year, $stock)";
    if (mysqli_query($conn, $query)) {
        header("Location: books.php");
        exit;
    } else {
        $error = "Failed to add book.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tambah Buku - Perpustakaan Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-5">
  <h2 class="mb-4">➕ Tambah Buku Baru</h2>

  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Nama Buku</label>
      <input type="text" name="title" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Author</label>
      <input type="text" name="author" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Tahun</label>
      <input type="number" name="year" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Stok Buku</label>
      <input type="number" name="stock" class="form-control" required min="1">
    </div>

    <button type="submit" name="add" class="btn btn-success">Tambah Buku</button>
    <a href="books.php" class="btn btn-secondary">Batalkan</a>
  </form>
</div>

</body>
</html>
