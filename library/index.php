<?php include('includes/db.php'); ?>
<?php include('includes/header.php'); ?>

<?php
// Handle form submission
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

        echo "<div class='alert alert-success mt-3'>Book borrowed successfully! ✅<br>Return before <strong>$due_date</strong>.</div>";
    } else {
        echo "<div class='alert alert-danger mt-3'>Failed to borrow book. ❌</div>";
    }
}

?>

<div class="container my-5">
  <h2 class="mb-4">📚 Available Books</h2>
  <?php if (isset($message)) echo $message; ?>

  <table class="table table-bordered table-striped align-middle">
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
      $result = mysqli_query($conn, "SELECT * FROM books ORDER BY title ASC");
      $no = 1;
      while ($row = mysqli_fetch_assoc($result)):
      ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= htmlspecialchars($row['title']); ?></td>
        <td><?= htmlspecialchars($row['author']); ?></td>
        <td><?= htmlspecialchars($row['year']); ?></td>
        <td><?= $row['stock']; ?></td>
        <td>
          <?php if ($row['stock'] > 0): ?>
            <button 
              class="btn btn-success btn-sm borrow-btn" 
              data-id="<?= $row['id']; ?>" 
              data-title="<?= htmlspecialchars($row['title']); ?>"
            >
              Borrow
            </button>
          <?php else: ?>
            <button class="btn btn-secondary btn-sm" disabled>Out of Stock</button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </tbody>
  </table>
</div>

<!-- Borrow Form Modal -->
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Borrow Book</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="book_id" id="book_id">
          <div class="mb-3">
            <label class="form-label">Book</label>
            <input type="text" id="book_title" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Your Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Your Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="borrow" class="btn btn-primary">Borrow</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Open modal and fill book info
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
</script>

<?php include('includes/footer.php'); ?>
