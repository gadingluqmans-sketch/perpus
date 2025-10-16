<?php
session_start();
include('../includes/db.php');

// Check login
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

// Query summary stats
$total_books = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM books"))['total'];
$total_borrowed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM borrowings WHERE returned = 0"))['total'];
$total_available = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) AS total FROM books"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - Library System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 12px; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">📚 Library Admin</a>
    <div class="d-flex">
      <span class="navbar-text text-white me-3">
        Welcome, <?= htmlspecialchars($_SESSION['admin_username']); ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4">📊 Dashboard Overview</h2>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card shadow text-center">
        <div class="card-body">
          <h5 class="card-title text-secondary">Total Books</h5>
          <h2 class="fw-bold"><?= $total_books ?: 0; ?></h2>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow text-center">
        <div class="card-body">
          <h5 class="card-title text-secondary">Borrowed Books</h5>
          <h2 class="fw-bold text-warning"><?= $total_borrowed ?: 0; ?></h2>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow text-center">
        <div class="card-body">
          <h5 class="card-title text-secondary">Books in Stock</h5>
          <h2 class="fw-bold text-success"><?= $total_available ?: 0; ?></h2>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-5">

  <div class="text-center">
    <h4 class="mb-4">Quick Actions</h4>
    <a href="books.php" class="btn btn-primary btn-lg me-2">📘 Manage Books</a>
    <a href="borrows.php" class="btn btn-secondary btn-lg">📋 Manage Borrowings</a>
  </div>
</div>

<footer class="text-center text-muted py-3">
  &copy; <?= date('Y'); ?> Library Management System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
