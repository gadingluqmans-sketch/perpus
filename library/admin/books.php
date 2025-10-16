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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Books - Library Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">📚 Library Admin</a>
    <div class="d-flex">
      <a href="index.php" class="btn btn-outline-light btn-sm">Home</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4">📘 Manage Books</h2>

  <a href="add.php" class="btn btn-primary mb-3">+ Add New Book</a>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Title</th>
        <th>Author</th>
        <th>Year</th>
        <th>Stock</th>
        <th>Action</th>
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
            <a href='edit.php?id={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
            <a href='delete.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this book?');\">Delete</a>
          </td>
        </tr>";
        $no++;
      }
      ?>
    </tbody>
  </table>
</div>

<footer class="text-center text-muted py-3">
  &copy; <?= date('Y'); ?> Library Management System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
