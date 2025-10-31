<?php include('includes/db.php'); ?>
<?php include('includes/header.php'); ?>

<?php
// proses aksi kembalikan (dengan verifikasi email yang sama dengan pencarian)
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_borrow_id'])) {
    $borrow_id = (int)$_POST['return_borrow_id'];
    $email_confirm = mysqli_real_escape_string($conn, trim($_POST['email_confirm'] ?? ''));

    // ambil catatan peminjaman
    $qr = mysqli_query($conn, "SELECT borrower_email, returned, due_date, book_id FROM borrowings WHERE id = $borrow_id");
    if (!$qr || mysqli_num_rows($qr) === 0) {
        $flash = "<div class='alert alert-danger'>Catatan peminjaman tidak ditemukan.</div>";
    } else {
        $br = mysqli_fetch_assoc($qr);
        if (strcasecmp($br['borrower_email'], $email_confirm) !== 0) {
            $flash = "<div class='alert alert-danger'>Email tidak cocok. Aksi dibatalkan.</div>";
        } elseif ((int)$br['returned'] === 1) {
            $flash = "<div class='alert alert-info'>Buku sudah ditandai dikembalikan sebelumnya.</div>";
        } else {
            // hitung denda jika terlambat
            $today = date('Y-m-d');
            $fine = 0;
            if (!empty($br['due_date']) && strtotime($today) > strtotime($br['due_date'])) {
                $overdue_days = floor((strtotime($today) - strtotime($br['due_date'])) / (60*60*24));
                $fine = $overdue_days * 10000; // tarif default
            }

            // transaksi: update borrowings + update stok buku
            mysqli_begin_transaction($conn);
            $ok1 = mysqli_query($conn, "UPDATE borrowings SET returned = 1, return_date = NOW(), fine = $fine WHERE id = $borrow_id");
            $book_id = (int)$br['book_id'];
            $ok2 = mysqli_query($conn, "UPDATE books SET stock = stock + 1 WHERE id = $book_id");

            if ($ok1 && $ok2) {
                mysqli_commit($conn);
                $flash = "<div class='alert alert-success'>Berhasil menandai pengembalian. Denda: Rp " . number_format($fine,0,',','.') . ".</div>";
            } else {
                mysqli_rollback($conn);
                $flash = "<div class='alert alert-danger'>Gagal memproses pengembalian. Silakan mencoba lagi.</div>";
            }
        }
    }
}
?>

<h2 class="mb-4">📖 Riwayat Peminjaman Saya</h2>
<p class="text-muted mb-4">Masukkan email yang Anda gunakan saat meminjam untuk melihat daftar buku yang sedang Anda pinjam atau pernah Anda pinjam — termasuk status, tanggal kembali, dan denda jika ada.</p>

<form method="GET" class="mb-4">
  <label for="email" class="form-label">Masukkan Email Anda:</label>
  <div class="input-group">
    <input type="email" name="email" id="email" class="form-control" placeholder="nama@contoh.com" required value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
    <button type="submit" class="btn btn-primary">Cari</button>
  </div>
</form>

<?php
if ($flash) echo $flash;

if (isset($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, trim($_GET['email']));

    $query = mysqli_query($conn, "
        SELECT br.id, br.book_id, b.title, br.borrow_date, br.due_date, br.return_date, br.returned, br.fine, br.borrower_email
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        WHERE br.borrower_email = '$email'
        ORDER BY br.borrow_date DESC
    ");

    if ($query && mysqli_num_rows($query) > 0) {
        $total = mysqli_num_rows($query);
        $today = date('Y-m-d');
        $total_overdue = 0;
        $stored_fine_sum = 0;
        $suggested_outstanding = 0; // denda terhitung untuk peminjaman yang belum dikembalikan

        // Pre-scan to compute totals
        $rows = [];
        while ($r = mysqli_fetch_assoc($query)) {
            $rows[] = $r;
            $stored_fine_sum += (int)$r['fine'];
            if (!$r['returned']) {
                if (!empty($r['due_date']) && strtotime($r['due_date']) < strtotime($today)) {
                    $total_overdue++;
                    $overdue_days = floor((strtotime($today) - strtotime($r['due_date'])) / (60*60*24));
                    // contoh tarif denda: Rp 10.000 per hari (hanya sebagai informasi)
                    $suggested_outstanding += $overdue_days * 10000;
                }
            }
        }

        // Ringkasan singkat
        echo "<div class='row mb-3'>
                <div class='col-md-4'><div class='card p-3'><div class='small text-muted'>Total Peminjaman</div><div class='h4 fw-bold'>$total</div></div></div>
                <div class='col-md-4'><div class='card p-3'><div class='small text-muted'>Peminjaman Terlambat</div><div class='h4 text-danger fw-bold'>$total_overdue</div></div></div>
                <div class='col-md-4'><div class='card p-3'><div class='small text-muted'>Total Denda Tercatat</div><div class='h4 text-warning fw-bold'>Rp " . number_format($stored_fine_sum,0,',','.') . "</div></div></div>
              </div>";

        echo "<div class='alert alert-info small'>Catatan: denda terhitung untuk peminjaman belum kembali (perkiraan): <strong>Rp " . number_format($suggested_outstanding,0,',','.') . "</strong>. Denda akhir ditetapkan oleh admin.</div>";

        // Tabel detail
        echo "<table class='table table-bordered table-striped'>
                <thead class='table-dark'>
                  <tr>
                    <th>Nama Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Jatuh Tempo</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Denda (Rp)</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>";

        foreach ($rows as $row) {
            $borrow_date = date('Y-m-d', strtotime($row['borrow_date']));
            $due_date = $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : '-';
            $return_date = $row['return_date'] ? date('Y-m-d', strtotime($row['return_date'])) : '-';

            if ($row['returned']) {
                $status = "<span class='badge bg-success'>Sudah Dikembalikan</span>";
            } else {
                // cek keterlambatan
                if (!empty($row['due_date']) && strtotime($row['due_date']) < strtotime($today)) {
                    $overdue_days = floor((strtotime($today) - strtotime($row['due_date'])) / (60*60*24));
                    $status = "<span class='badge bg-danger'>Terlambat {$overdue_days} hari</span>";
                } else {
                    $status = "<span class='badge bg-warning text-dark'>Sedang Dipinjam</span>";
                }
            }

            $fine_display = $row['fine'] ? number_format((int)$row['fine'],0,',','.') : '-';

            echo "<tr>
                    <td>" . htmlspecialchars($row['title']) . "</td>
                    <td>$borrow_date</td>
                    <td>$due_date</td>
                    <td>$return_date</td>
                    <td>$status</td>
                    <td class='text-end'>$fine_display</td>
                    <td class='text-center'>";
            // tombol aksi kembalikan hanya jika belum dikembalikan
            if (!$row['returned']) {
                // form kecil untuk kembalikan, memverifikasi email pencarian
                echo "<form method='POST' style='display:inline;' onsubmit=\"return confirm('Konfirmasi: kembalikan buku ini?');\">
                        <input type='hidden' name='return_borrow_id' value='" . (int)$row['id'] . "'>
                        <input type='hidden' name='email_confirm' value='" . htmlspecialchars($email) . "'>
                        <button type='submit' class='btn btn-sm btn-success'>Kembalikan</button>
                      </form>";
            } else {
                echo "-";
            }

            echo    "</td>
                  </tr>";
        }

        echo "</tbody></table>";

    } else {
        echo "<div class='alert alert-info'>Tidak ditemukan peminjaman untuk email: <strong>" . htmlspecialchars($email) . "</strong>.</div>";
    }
}
?>

<?php include('includes/footer.php'); ?>
