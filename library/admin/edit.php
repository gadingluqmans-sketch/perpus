<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: books.php");
    exit;
}

$id = $_GET['id'];
$book = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM books WHERE id=$id"));

if (isset($_POST['update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $year = (int)$_POST['year'];
    $stock = (int)$_POST['stock'];

    $query = "UPDATE books SET title='$title', author='$author', year=$year, stock=$stock WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        header("Location: books.php");
        exit;
    } else {
        $error = "Gagal memperbarui data buku.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Buku - Admin Perpustakaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container my-5">
  <h2 class="mb-4">✏️ Edit Buku</h2>

  <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

  <form method="POST">
    <div class="mb-3">
      <label>Judul</label>
      <input type="text" name="title" value="<?= htmlspecialchars($book['title']); ?>" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Penulis</label>
      <input type="text" name="author" value="<?= htmlspecialchars($book['author']); ?>" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Tahun</label>
      <input type="number" name="year" value="<?= $book['year']; ?>" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Stok</label>
      <input type="number" name="stock" value="<?= $book['stock']; ?>" class="form-control" required>
    </div>

    <button type="submit" name="update" class="btn btn-warning">Perbarui Buku</button>
    <a href="books.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

</body>
</html>
