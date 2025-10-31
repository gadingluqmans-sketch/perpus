<?php
// Mulai session untuk pengelolaan login admin
session_start();
// Include file koneksi database 
include('../includes/db.php');

// Cek apakah admin sudah login, jika belum redirect ke login
if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

// Proses pengembalian buku ketika menerima parameter return_id
if (isset($_GET['return_id'])) {
    $id = (int)$_GET['return_id'];

    // Ambil data peminjaman untuk cek tanggal jatuh tempo
    $borrow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT book_id, due_date FROM borrowings WHERE id = $id"));

    if ($borrow) {
        $today = date('Y-m-d');
        $due_date = $borrow['due_date'];

        // Hitung jumlah hari keterlambatan
        $overdue_days = (strtotime($today) - strtotime($due_date)) / (60 * 60 * 24);
        $fine = 0;

        // Jika terlambat, hitung denda (Rp 10.000 per hari)
        if ($overdue_days > 0) {
            $fine = $overdue_days * 10000;
        }

        // Update status peminjaman: tandai dikembalikan, set denda, catat tanggal kembali
        mysqli_query($conn, "
            UPDATE borrowings 
            SET returned = 1, fine = $fine, return_date = '$today'
            WHERE id = $id
        ");

        // Tambah stok buku yang dikembalikan
        $book_id = $borrow['book_id'];
        mysqli_query($conn, "UPDATE books SET stock = stock + 1 WHERE id = $book_id");
    }

    header("Location: borrows.php");
    exit;
}

// Proses update denda manual oleh admin
if (isset($_POST['set_fine'])) {
    $id = (int)$_POST['borrow_id'];
    $fine = (int)$_POST['fine'];
    mysqli_query($conn, "UPDATE borrowings SET fine = $fine WHERE id = $id");
    header("Location: borrows.php"); 
    exit;
}

// Query untuk mengambil semua data peminjaman join dengan tabel books
$query = "
  SELECT b.id, b.borrower_name, b.borrower_email,
         bk.title, b.borrow_date, b.return_date, b.returned, b.fine
  FROM borrowings b
  JOIN books bk ON b.book_id = bk.id
  ORDER BY b.borrow_date DESC
";
$result = mysqli_query($conn, $query);

// Cek apakah query berhasil
if (!$result) {
    die("Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Peminjaman - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar untuk navigasi admin -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">📚 Admin Perpustakaan</a>
        <div class="d-flex">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<!-- Container utama -->
<div class="container my-5">
    <h2 class="mb-4">📋 Kelola Peminjaman</h2>
    
    <!-- Tabel daftar peminjaman -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Peminjam</th> 
                <th>Email</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Denda</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['borrower_name']); ?></td>
                <td><?= htmlspecialchars($row['borrower_email']); ?></td>
                <td><?= htmlspecialchars($row['title']); ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['borrow_date'])); ?></td>
                <td><?= $row['return_date'] ? date('Y-m-d H:i', strtotime($row['return_date'])) : '-'; ?></td>
                <td>
                    <?php if ($row['returned']): ?>
                        <span class="badge bg-success">Sudah Kembali</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Belum Kembali</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= $row['fine'] ? "Rp " . number_format($row['fine'],0,',','.') : '-'; ?>
                </td>
                <td>
                    <?php if (!$row['returned']): ?>
                        <button onclick="markReturned(<?= $row['id']; ?>)" class="btn btn-sm btn-success">Tandai Kembali</button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#fineModal<?= $row['id']; ?>">Atur Denda</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<footer class="text-center text-muted py-3">
    &copy; <?= date('Y'); ?> Sistem Perpustakaan
</footer>

<!-- Script untuk Bootstrap dan fungsi markReturned -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fungsi async untuk menandai buku dikembalikan via API
async function markReturned(borrowId) {
    if (!confirm('Tandai buku ini sudah dikembalikan?')) return;
    
    try {
        const res = await fetch(`../api.php?resource=borrows&id=${borrowId}`, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'return' })
        });
        
        const data = await res.json();
        if (res.ok && data.success) {
            alert('Berhasil menandai pengembalian. Denda: Rp ' + (data.fine || 0));
            location.reload();
        } else {
            alert('Gagal: ' + (data.error || 'Server error'));
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan');
    }
}
</script>

</body>
</html>
