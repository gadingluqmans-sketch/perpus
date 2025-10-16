<?php include('includes/db.php'); ?>
<?php include('includes/header.php'); ?>

<h2 class="mb-4">📖 My Borrowed Books</h2>

<form method="GET" class="mb-4">
  <label for="email" class="form-label">Enter your email:</label>
  <div class="input-group">
    <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" required>
    <button type="submit" class="btn btn-primary">Search</button>
  </div>
</form>

<?php
if (isset($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $query = mysqli_query($conn, "
        SELECT b.title, br.borrow_date, br.return_date, br.returned, br.fine
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        WHERE borrower_email = '$email'
        ORDER BY br.borrow_date DESC
    ");

    if (mysqli_num_rows($query) > 0) {
        echo "<table class='table table-bordered table-striped'>
                <thead class='table-dark'>
                  <tr>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Fine (Rp)</th>
                  </tr>
                </thead>
                <tbody>";
        while ($row = mysqli_fetch_assoc($query)) {
            $status = $row['returned'] ? "<span class='badge bg-success'>Returned</span>" : "<span class='badge bg-warning text-dark'>Borrowed</span>";
            $return_date = $row['return_date'] ? $row['return_date'] : '-';
            echo "<tr>
                    <td>{$row['title']}</td>
                    <td>{$row['borrow_date']}</td>
                    <td>{$return_date}</td>
                    <td>{$status}</td>
                    <td>{$row['fine']}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>No records found for <strong>$email</strong>.</div>";
    }
}
?>

<?php include('includes/footer.php'); ?>
    