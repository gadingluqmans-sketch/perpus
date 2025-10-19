<?php
session_start();
include('../includes/db.php');

// Check login
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

// Query summary stats
$total_books_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total, COALESCE(SUM(stock),0) AS stok_total FROM books"));
$total_books = (int)($total_books_row['total'] ?? 0);
$total_available = (int)($total_books_row['stok_total'] ?? 0);
$total_borrowed = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM borrowings WHERE returned = 0"))['total'];

// Additional data to make dashboard lebih berisi
$recent_books_res = mysqli_query($conn, "SELECT id, title, author, year, stock FROM books ORDER BY id DESC LIMIT 5");
$recent_borrows_res = mysqli_query($conn, "SELECT b.id, b.borrower_name, bk.title, b.borrow_date, b.return_date, b.returned FROM borrowings b JOIN books bk ON b.book_id = bk.id ORDER BY b.borrow_date DESC LIMIT 5");
$low_stock_res = mysqli_query($conn, "SELECT id, title, stock FROM books WHERE stock <= 2 ORDER BY stock ASC, id DESC LIMIT 5");

// Simple ratios (avoid division by zero)
$borrow_ratio = $total_books ? round(($total_borrowed / $total_books) * 100, 1) : 0;
$availability_ratio = $total_books ? round(($total_available / ($total_books ?: 1)) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - Sistem Perpustakaan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .card { border-radius: 12px; }
    .small-muted { font-size: .9rem; color: #6c757d; }
    .stat-number { font-size: 2.2rem; font-weight:700; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="index.php">📚 Admin Perpustakaan</a>
    <div class="d-flex">
      <span class="navbar-text text-white me-3">
        <?= 'Halo, ' . htmlspecialchars($_SESSION['admin_username']); ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h2 class="mb-1">📊 Ringkasan Dashboard</h2>
      <p class="small-muted mb-0">Ikhtisar cepat data perpustakaan - buku, peminjaman, dan peringatan stok.</p>
      <p class="small-muted">Gunakan tombol aksi cepat untuk mengelola koleksi dan peminjaman.</p>
    </div>
    <div>
      <a href="books.php" class="btn btn-primary me-2">📘 Kelola Buku</a>
      <a href="borrows.php" class="btn btn-secondary">📋 Kelola Peminjaman</a>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="card shadow p-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="small-muted">Total Buku</div>
            <div class="stat-number"><?= $total_books; ?></div>
            <div class="small-muted">Stok total: <?= $total_available; ?></div>
          </div>
          <div class="text-end">
            <div class="small-muted">Buku dipinjam</div>
            <div class="h3 text-warning"><?= $total_borrowed; ?></div>
          </div>
        </div>
        <div class="mt-3">
          <div class="small-muted">Rasio pinjam: <?= $borrow_ratio; ?>% — Ketersediaan rata-rata: <?= $availability_ratio; ?>%</div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow p-3">
        <h5 class="mb-2">Peringatan Stok Rendah</h5>
        <?php if (mysqli_num_rows($low_stock_res) > 0): ?>
          <ul class="list-group list-group-flush">
            <?php while ($l = mysqli_fetch_assoc($low_stock_res)): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($l['title']); ?>
                <span class="badge bg-danger"><?= (int)$l['stock']; ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p class="mb-0 small-muted">Tidak ada buku dengan stok rendah.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow p-3">
        <h5 class="mb-2">Aktivitas Terbaru</h5>
        <div class="small-muted">5 pinjaman/buku terbaru</div>
        <div class="mt-2">
          <ul class="list-unstyled mb-0">
            <?php while ($rb = mysqli_fetch_assoc($recent_books_res)): ?>
              <li class="mb-2">
                <strong><?= htmlspecialchars($rb['title']); ?></strong>
                <div class="small-muted">oleh <?= htmlspecialchars($rb['author']); ?> — <?= (int)$rb['year']; ?> — Stok: <?= (int)$rb['stock']; ?></div>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body">
      <h5 class="card-title">Peminjaman Terbaru</h5>
      <p class="small-muted">Daftar lima peminjaman terakhir, cek status pengembalian dan atur denda jika perlu.</p>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Peminjam</th>
              <th>Buku</th>
              <th>Tanggal Pinjam</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $i = 1;
            while ($rb = mysqli_fetch_assoc($recent_borrows_res)):
            ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($rb['borrower_name']); ?></td>
              <td><?= htmlspecialchars($rb['title']); ?></td>
              <td><?= date('Y-m-d H:i', strtotime($rb['borrow_date'])); ?></td>
              <td>
                <?php if ($rb['returned']): ?>
                  <span class="badge bg-success">Sudah kembali</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Belum kembali</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>

            <?php if ($i === 1): ?>
              <tr><td colspan="5" class="small-muted">Belum ada peminjaman tercatat.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-body">
      <h5 class="card-title">Panduan Singkat</h5>
      <ul class="mb-0">
        <li>Gunakan "Kelola Buku" untuk menambah/ubah/hapus buku.</li>
        <li>Gunakan "Kelola Peminjaman" untuk menandai pengembalian dan mengatur denda.</li>
        <li>Periksa bagian "Peringatan Stok Rendah" untuk merestock buku yang hampir habis.</li>
      </ul>
    </div>
  </div>

</div>

<footer class="text-center text-muted py-3">
  &copy; <?= date('Y'); ?> Sistem Informasi Perpustakaan — Dikembangkan untuk manajemen perpustakaan sederhana
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
