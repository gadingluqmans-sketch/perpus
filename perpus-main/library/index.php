<?php 
// Include file koneksi database dan header template
include('includes/db.php'); 
include('includes/header.php'); 

// Statistik ringkas untuk dashboard peminjaman
// Hitung total judul buku yang tersedia
$total_books = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM books"))['total'] ?? 0);

// Hitung total stok buku yang tersedia (jumlah fisik)
$total_stock = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stock),0) AS total_stock FROM books"))['total_stock'] ?? 0);

// Hitung jumlah buku yang sedang dipinjam (belum dikembalikan)
$total_borrowed = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM borrowings WHERE returned = 0"))['total'] ?? 0);

// Set batas waktu untuk peringatan jatuh tempo (+3 hari dari sekarang)
$due_soon_cutoff = date('Y-m-d', strtotime('+3 days'));

// Hitung jumlah peminjaman yang akan jatuh tempo dalam 3 hari
$due_soon = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM borrowings WHERE returned = 0 AND due_date <= '$due_soon_cutoff'"))['total'] ?? 0);

// Fitur pencarian buku
$search = '';
$where = '';
if (!empty($_GET['q'])) {
    // Escape string pencarian untuk mencegah SQL injection
    $search = trim($_GET['q']);
    $esc = mysqli_real_escape_string($conn, $search);
    // Buat klausa WHERE untuk mencari di judul dan pengarang
    $where = "WHERE title LIKE '%$esc%' OR author LIKE '%$esc%'";
}

// Query untuk mendapatkan buku-buku populer
// Berdasarkan frekuensi peminjaman
$popular_res = mysqli_query($conn, "
    SELECT bk.id, bk.title, bk.author, COUNT(b.id) AS times
    FROM books bk
    LEFT JOIN borrowings b ON b.book_id = bk.id 
    GROUP BY bk.id
    ORDER BY times DESC
    LIMIT 5
");

// Ambil daftar semua buku (dengan filter pencarian jika ada)
$result = mysqli_query($conn, "SELECT * FROM books $where ORDER BY title ASC");
?>

<!-- Container utama -->
<div class="container my-5">
  <div class="row mb-4">
    <div class="col-md-8">
      <h2 class="mb-3">📚 Buku Yang Tersedia</h2>
      <p class="text-muted">Cari dan pinjam buku secara online. Lihat status stok sebelum meminjam.</p>
    </div>
    <div class="col-md-4 text-md-end">
      <form class="d-flex" method="GET" role="search" aria-label="Cari buku">
        <input name="q" class="form-control me-2" type="search" placeholder="Cari judul atau penulis..." value="<?= htmlspecialchars($search); ?>">
        <button class="btn btn-outline-primary" type="submit">Cari</button>
      </form>
    </div>
  </div>

  <div class="row gy-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-body">
          <div class="row text-center">
            <div class="col-4">
              <div class="small text-muted">Total Buku</div>
              <div class="h4 fw-bold"><?= $total_books; ?></div>
            </div>
            <div class="col-4">
              <div class="small text-muted">Stok Tersedia</div>
              <div class="h4 text-success"><?= $total_stock; ?></div>
            </div>
            <div class="col-4">
              <div class="small text-muted">Sedang Dipinjam</div>
              <div class="h4 text-warning"><?= $total_borrowed; ?></div>
            </div>
          </div>

          <?php if ($due_soon > 0): ?>
            <div class="alert alert-warning mt-3 mb-0" role="alert">
              <strong>Perhatian:</strong> Ada <?= $due_soon; ?> peminjaman yang mendekati jatuh tempo (3 hari ke depan). Silakan kembalikan tepat waktu.
            </div>
          <?php else: ?>
            <div class="small text-muted mt-3">Tidak ada peminjaman yang mendekati jatuh tempo.</div>
          <?php endif; ?>
        </div>
      </div>

      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th style="width:56px">No</th>
            <th>Judul</th>
            <th>Penulis</th>
            <th style="width:100px">Tahun</th>
            <th style="width:120px">Stok</th>
            <th style="width:150px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          $hasRows = false;
          while ($row = mysqli_fetch_assoc($result)):
              $hasRows = true;
          ?>
          <tr>
            <td><?= $no++; ?></td>
            <td>
              <strong><?= htmlspecialchars($row['title']); ?></strong>
              <div class="small text-muted">ID: <?= (int)$row['id']; ?></div>
            </td>
            <td><?= htmlspecialchars($row['author']); ?></td>
            <td><?= htmlspecialchars($row['year']); ?></td>
            <td>
              <?php if ((int)$row['stock'] <= 0): ?>
                <span class="badge bg-secondary">Habis</span>
              <?php elseif ((int)$row['stock'] <= 2): ?>
                <span class="badge bg-danger">Tersisa <?= (int)$row['stock']; ?></span>
              <?php else: ?>
                <span class="badge bg-success">Tersedia <?= (int)$row['stock']; ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['stock'] > 0): ?>
                <button
                  class="btn btn-success btn-sm borrow-btn"
                  data-id="<?= (int)$row['id']; ?>"
                  data-title="<?= htmlspecialchars($row['title']); ?>"
                >Pinjam Buku</button>
              <?php else: ?>
                <button class="btn btn-outline-secondary btn-sm" disabled>Kehabisan Stok</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>

          <?php if (!$hasRows): ?>
            <tr>
              <td colspan="6" class="text-muted">Tidak ditemukan buku. Coba kata kunci lain.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="mt-3">
        <h6>Panduan singkat</h6>
        <ul class="small text-muted mb-0">
          <li>Isi form pinjam dengan data yang benar. Batas pinjam standar: 3 hari.</li>
          <li>Denda keterlambatan akan ditentukan oleh admin jika terlambat mengembalikan.</li>
          <li>Jika stok menunjukkan sedikit (badge merah), hubungi perpustakaan untuk info restock.</li>
        </ul>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">Buku Populer</h5>
          <p class="small text-muted">Buku yang paling sering dipinjam.</p>
          <ul class="list-group list-group-flush">
            <?php while ($p = mysqli_fetch_assoc($popular_res)): ?>
              <li class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($p['title']); ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($p['author']); ?></div>
                </div>
                <span class="badge bg-primary rounded-pill"><?= (int)$p['times']; ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h6 class="card-title">Informasi Pinjam</h6>
          <p class="small text-muted mb-2">Aturan singkat:</p>
          <ul class="small text-muted mb-0">
            <li>Durasi pinjam standar: 3 hari sejak tanggal peminjaman.</li>
            <li>Perpanjangan dapat diajukan ke petugas sebelum jatuh tempo.</li>
            <li>Jaga kondisi buku; kehilangan atau kerusakan akan dikenakan ganti rugi.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Borrow Form Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="borrowForm">
        <div class="modal-header">
          <h5 class="modal-title">Pinjam Buku</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="book_id" id="book_id">
          <div class="mb-3">
            <label class="form-label">Buku</label>
            <input type="text" id="book_title" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Anda</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Anda</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="small text-muted">Catatan: batas pinjam standar adalah 3 hari. Harap periksa tanggal pengembalian di konfirmasi setelah meminjam.</div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="borrow" class="btn btn-primary">Pinjam</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// buka modal & isi data buku
document.querySelectorAll('.borrow-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const bookId = btn.getAttribute('data-id');
    const bookTitle = btn.getAttribute('data-title');
    document.getElementById('book_id').value = bookId;
    document.getElementById('book_title').value = bookTitle;
    const modal = new bootstrap.Modal(document.getElementById('borrowModal'));
    modal.show();
  });
});

document.addEventListener('DOMContentLoaded', function(){
    // Handler form peminjaman
    const form = document.getElementById('borrowForm');
    if (!form) return;

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        // Ambil data form
        const book_id = Number(form.book_id.value);
        const name = form.name.value.trim();
        const email = form.email.value.trim();

        try {
            // Kirim request ke API
            const res = await fetch('api.php?resource=borrows', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ book_id, name, email })
            });
            const data = await res.json();
            
            if (res.ok && data.success) {
                // Tampilkan konfirmasi sukses
                alert('Berhasil meminjam buku! Jatuh tempo: ' + data.due_date);
                location.reload(); // Reload untuk update stok
            } else {
                alert('Gagal: ' + (data.error || 'Server error'));
            }
        } catch (err) {
            console.error(err);
            alert('Terjadi kesalahan jaringan');
        }
    });
});
</script>

<?php
// Handle form submission (tetap di bagian bawah setelah tampilan untuk UX yang konsisten)
if (isset($_POST['borrow'])) {
    $book_id = (int)$_POST['book_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Set due date = 3 days from now
    $due_date = date('Y-m-d', strtotime('+3 days'));

    // Insert record into borrowings table
    $insert = mysqli_query($conn, "
        INSERT INTO borrowings (borrower_name, borrower_email, book_id, borrow_date, due_date)
        VALUES ('$name', '$email', $book_id, NOW(), '$due_date')
    ");

    if ($insert) {
        // Decrease book stock by 1
        mysqli_query($conn, "UPDATE books SET stock = stock - 1 WHERE id = $book_id");

        echo "<div class='container'><div class='alert alert-success mt-3'>Berhasil meminjam buku! ✅<br>Mohon kembalikan sebelum <strong>$due_date</strong>.</div></div>";
    } else {
        echo "<div class='container'><div class='alert alert-danger mt-3'>Gagal meminjam buku. ❌</div></div>";
    }
}
?>

<?php include('includes/footer.php'); ?>
