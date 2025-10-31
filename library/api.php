<?php
// Simple REST-ish API untuk Perpustakaan (Books & Borrows)
// - Gunakan method HTTP: GET/POST/PUT/DELETE
// - Untuk tindakan admin (create/update/delete books, return borrow, set fine) harus login (cek session)

session_start();
header('Content-Type: application/json; charset=utf-8');

include __DIR__ . '/includes/db.php';

function res($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function require_admin() {
    if (!isset($_SESSION['admin_username'])) {
        res(['error' => 'Akses ditolak. Harus login sebagai admin.'], 401);
    }
}

$resource = $_GET['resource'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

// ---------- BOOKS ----------
if ($resource === 'books') {
    if ($method === 'GET') {
        if ($id) {
            $stmt = mysqli_prepare($conn, "SELECT id, title, author, year, stock FROM books WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($r);
            $row ? res($row) : res(['error'=>'Buku tidak ditemukan'],404);
        } else {
            $q = "SELECT id, title, author, year, stock FROM books ORDER BY title ASC";
            $resq = mysqli_query($conn, $q);
            $out = [];
            while ($r = mysqli_fetch_assoc($resq)) $out[] = $r;
            res($out);
        }
    }

    if ($method === 'POST') {
        require_admin();
        $in = input();
        $title = trim($in['title'] ?? '');
        $author = trim($in['author'] ?? '');
        $year = (int)($in['year'] ?? 0);
        $stock = (int)($in['stock'] ?? 0);
        if ($title === '') res(['error'=>'Judul wajib diisi'],422);
        $stmt = mysqli_prepare($conn, "INSERT INTO books (title, author, year, stock) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssii', $title, $author, $year, $stock);
        if (mysqli_stmt_execute($stmt)) res(['success'=>true,'id'=>mysqli_insert_id($conn)],201);
        res(['error'=>'Gagal menambah buku','db'=>mysqli_error($conn)],500);
    }

    if ($method === 'PUT') {
        require_admin();
        if (!$id) res(['error'=>'ID buku diperlukan'],422);
        $in = input();
        $fields = []; $types = ''; $vals = [];
        if (isset($in['title'])) { $fields[]='title=?'; $types.='s'; $vals[] = $in['title']; }
        if (isset($in['author'])){ $fields[]='author=?'; $types.='s'; $vals[] = $in['author']; }
        if (isset($in['year'])){ $fields[]='year=?'; $types.='i'; $vals[] = (int)$in['year']; }
        if (isset($in['stock'])){ $fields[]='stock=?'; $types.='i'; $vals[] = (int)$in['stock']; }
        if (empty($fields)) res(['error'=>'Tidak ada data untuk diupdate'],422);
        $sql = "UPDATE books SET ".implode(',',$fields)." WHERE id=?";
        $types .= 'i'; $vals[] = $id;
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$vals);
        mysqli_stmt_execute($stmt);
        res(['success'=>true]);
    }

    if ($method === 'DELETE') {
        require_admin();
        if (!$id) res(['error'=>'ID buku diperlukan'],422);
        $stmt = mysqli_prepare($conn, "DELETE FROM books WHERE id=?");
        mysqli_stmt_bind_param($stmt,'i',$id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) res(['success'=>true]);
        res(['error'=>'Buku tidak ditemukan'],404);
    }

    res(['error'=>'Method tidak didukung untuk books'],405);
}

// ---------- BORROWS ----------
if ($resource === 'borrows') {
    if ($method === 'GET') {
        if ($id) {
            $stmt = mysqli_prepare($conn, "SELECT br.*, b.title FROM borrowings br JOIN books b ON br.book_id=b.id WHERE br.id=?");
            mysqli_stmt_bind_param($stmt,'i',$id);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($r);
            $row ? res($row) : res(['error'=>'Catatan tidak ditemukan'],404);
        }
        if (!empty($_GET['email'])) {
            $email = $_GET['email'];
            $stmt = mysqli_prepare($conn, "SELECT br.*, b.title FROM borrowings br JOIN books b ON br.book_id=b.id WHERE br.borrower_email=? ORDER BY br.borrow_date DESC");
            mysqli_stmt_bind_param($stmt,'s',$email);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            $out = [];
            while ($row = mysqli_fetch_assoc($r)) $out[] = $row;
            res($out);
        }
        require_admin();
        $resq = mysqli_query($conn, "SELECT br.*, b.title FROM borrowings br JOIN books b ON br.book_id=b.id ORDER BY br.borrow_date DESC");
        $out = [];
        while ($row = mysqli_fetch_assoc($resq)) $out[] = $row;
        res($out);
    }

    if ($method === 'POST') {
        // mahasiswa membuat peminjaman
        $in = input();
        $book_id = (int)($in['book_id'] ?? 0);
        $name = trim($in['name'] ?? '');
        $email = trim($in['email'] ?? '');
        if (!$book_id || $name === '' || $email === '') res(['error'=>'book_id, name, email wajib diisi'],422);

        // transaksi: cek stok, insert borrow, update stock
        mysqli_begin_transaction($conn);
        $stmt = mysqli_prepare($conn, "SELECT stock FROM books WHERE id=? FOR UPDATE");
        mysqli_stmt_bind_param($stmt,'i',$book_id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        $bk = mysqli_fetch_assoc($r);
        if (!$bk) { mysqli_rollback($conn); res(['error'=>'Buku tidak ditemukan'],404); }
        if ((int)$bk['stock'] <= 0) { mysqli_rollback($conn); res(['error'=>'Stok habis'],409); }

        $due = date('Y-m-d', strtotime('+3 days'));
        $stmt2 = mysqli_prepare($conn, "INSERT INTO borrowings (borrower_name,borrower_email,book_id,borrow_date,due_date,returned,fine) VALUES (?,?,?,NOW(),?,0,0)");
        mysqli_stmt_bind_param($stmt2,'ssis',$name,$email,$book_id,$due);
        if (!mysqli_stmt_execute($stmt2)) { mysqli_rollback($conn); res(['error'=>'Gagal mencatat peminjaman','db'=>mysqli_error($conn)],500); }
        $borrow_id = mysqli_insert_id($conn);
        $stmt3 = mysqli_prepare($conn, "UPDATE books SET stock = stock - 1 WHERE id=?");
        mysqli_stmt_bind_param($stmt3,'i',$book_id);
        if (!mysqli_stmt_execute($stmt3)) { mysqli_rollback($conn); res(['error'=>'Gagal mengurangi stok','db'=>mysqli_error($conn)],500); }
        mysqli_commit($conn);
        res(['success'=>true,'borrow_id'=>$borrow_id,'due_date'=>$due],201);
    }

    if ($method === 'PUT') {
        require_admin();
        if (!$id) res(['error'=>'ID peminjaman diperlukan'],422);
        $in = input();
        // return action
        if (!empty($in['action']) && $in['action'] === 'return') {
            $stmt = mysqli_prepare($conn, "SELECT book_id, due_date, returned FROM borrowings WHERE id=?");
            mysqli_stmt_bind_param($stmt,'i',$id);
            mysqli_stmt_execute($stmt);
            $r = mysqli_stmt_get_result($stmt);
            $br = mysqli_fetch_assoc($r);
            if (!$br) res(['error'=>'Catatan tidak ditemukan'],404);
            if ($br['returned']) res(['error'=>'Sudah dikembalikan'],409);

            $today = date('Y-m-d');
            $fine = 0;
            if (!empty($br['due_date']) && strtotime($today) > strtotime($br['due_date'])) {
                $days = floor((strtotime($today)-strtotime($br['due_date']))/(60*60*24));
                $fine = $days * 10000; // tarif default
            }

            mysqli_begin_transaction($conn);
            $stmt2 = mysqli_prepare($conn, "UPDATE borrowings SET returned=1, return_date=?, fine=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2,'sii',$today,$fine,$id);
            if (!mysqli_stmt_execute($stmt2)) { mysqli_rollback($conn); res(['error'=>'Gagal update peminjaman','db'=>mysqli_error($conn)],500); }
            $book_id = (int)$br['book_id'];
            $stmt3 = mysqli_prepare($conn, "UPDATE books SET stock = stock + 1 WHERE id=?");
            mysqli_stmt_bind_param($stmt3,'i',$book_id);
            // Bind parameter book_id ke prepared statement
            mysqli_stmt_bind_param($stmt3,'i',$book_id);
            
            // Eksekusi query update stok, rollback jika gagal
            if (!mysqli_stmt_execute($stmt3)) { 
                mysqli_rollback($conn); 
                res(['error'=>'Gagal menambah stok','db'=>mysqli_error($conn)],500); 
            }
            
            // Commit transaksi jika semua berhasil
            mysqli_commit($conn);
            // Kirim response sukses dengan info denda
            res(['success'=>true,'fine'=>$fine]);
        }

        // Handler untuk mengatur denda manual
        if (isset($in['fine'])) {
            // Convert input denda ke integer
            $fine = (int)$in['fine'];
            
            // Prepare statement untuk update denda
            $stmt = mysqli_prepare($conn, "UPDATE borrowings SET fine=? WHERE id=?");
            // Bind parameter fine dan id
            mysqli_stmt_bind_param($stmt,'ii',$fine,$id);
            
            // Eksekusi query dan kirim response
            if (mysqli_stmt_execute($stmt)) {
                res(['success'=>true]);
            }
            // Jika gagal, kirim error
            res(['error'=>'Gagal mengatur denda','db'=>mysqli_error($conn)],500);
        }

        // Jika action tidak dikenali
        res(['error'=>'Aksi tidak diketahui'],422);
    }

    if ($method === 'DELETE') {
        require_admin();
        if (!$id) res(['error'=>'ID peminjaman diperlukan'],422);
        $stmt = mysqli_prepare($conn, "DELETE FROM borrowings WHERE id=?");
        mysqli_stmt_bind_param($stmt,'i',$id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) res(['success'=>true]);
        res(['error'=>'Catatan tidak ditemukan'],404);
    }

    res(['error'=>'Method tidak didukung untuk borrows'],405);
}

// Jika resource tidak ditemukan
res(['error'=>'Resource tidak ditemukan'],404);