<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

// Mark as returned
if (isset($_GET['return_id'])) {
    $id = (int)$_GET['return_id'];

    // Get borrow record (to check due date)
   $borrow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT book_id, due_date FROM borrowings WHERE id = $id"));

    if ($borrow) {
        $today = date('Y-m-d');
        $due_date = $borrow['due_date'];

        // Calculate overdue days
        $overdue_days = (strtotime($today) - strtotime($due_date)) / (60 * 60 * 24);
        $fine = 0;

        if ($overdue_days > 0) {
            $fine = $overdue_days * 10000; // Rp.10,000 per day
        }

        // Update record: mark returned, add fine, set actual return date
        mysqli_query($conn, "
            UPDATE borrowings 
            SET returned = 1, fine = $fine, return_date = '$today'
            WHERE id = $id
        ");

        // Increase stock back by 1
        $book_id = $borrow['book_id'];
        mysqli_query($conn, "UPDATE books SET stock = stock + 1 WHERE id = $book_id");
    }

    header("Location: borrows.php"); 
    exit;
}


// Update fine
if (isset($_POST['set_fine'])) {
    $id = (int)$_POST['borrow_id'];
    $fine = (int)$_POST['fine'];
    mysqli_query($conn, "UPDATE borrowings SET fine = $fine WHERE id = $id");
    header("Location: borrows.php");
    exit;
}

// Fetch borrow data
$query = "
  SELECT b.id, b.borrower_name, b.borrower_email,
         bk.title, b.borrow_date, b.return_date, b.returned, b.fine
  FROM borrowings b
  JOIN books bk ON b.book_id = bk.id
  ORDER BY b.borrow_date DESC
";
$result = mysqli_query($conn, $query);

if (!$result) {
    // debug helper: print SQL error (remove or comment out in production)
    die("DB error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Borrowings - Library Admin</title>
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
      <a href="index.php" class="btn btn-outline-light btn-sm">Home</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container my-5">
  <h2 class="mb-4">📋 Manage Borrowings</h2>

  <table class="table table-bordered table-striped align-middle">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>User</th>
        <th>Contact</th>
        <th>Book Title</th>
        <th>Borrowed On</th>
        <th>Returned On</th>
        <th>Returned?</th>
        <th>Fine</th>
        <th>Action</th>
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
              <span class="badge bg-success">Yes</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">No</span>
            <?php endif; ?>
          </td>
          <td>
            <?= $row['fine'] ? "Rp " . number_format($row['fine'], 0, ',', '.') : '-'; ?>
          </td>
          <td>
            <?php if (!$row['returned']): ?>
              <a href="borrows.php?return_id=<?= $row['id']; ?>" class="btn btn-sm btn-success mb-1" onclick="return confirm('Mark this book as returned?')">Mark Returned</a>
            <?php endif; ?>
            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#fineModal<?= $row['id']; ?>">Set Fine</button>
          </td>
        </tr>

        <!-- Fine Modal -->
        <div class="modal fade" id="fineModal<?= $row['id']; ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST">
                <div class="modal-header">
                  <h5 class="modal-title">Set Fine for <?= htmlspecialchars($row['borrower_name']); ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="borrow_id" value="<?= $row['id']; ?>">
                  <div class="mb-3">
                    <label for="fine" class="form-label">Fine Amount (Rp)</label>
                    <input type="number" name="fine" class="form-control" value="<?= $row['fine']; ?>" required min="0">
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" name="set_fine" class="btn btn-primary">Save Fine</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<footer class="text-center text-muted py-3">
  &copy; <?= date('Y'); ?> Library Management System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
