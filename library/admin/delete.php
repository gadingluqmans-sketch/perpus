<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM books WHERE id=$id");
}

header("Location: books.php");
exit;
?>
