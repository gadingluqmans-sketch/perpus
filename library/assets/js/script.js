// =========================================
// Custom JS for Library System
// Main JavaScript untuk fungsionalitas client-side
// =========================================

// Auto-dismiss Bootstrap alerts after 3 seconds
// Fungsi untuk menghilangkan alert secara otomatis setelah 3 detik
document.addEventListener("DOMContentLoaded", function () {
  // Cari semua elemen dengan class alert
  const alerts = document.querySelectorAll(".alert");
  
  // Iterasi tiap alert
  alerts.forEach((alert) => {
    // Set timeout 3 detik
    setTimeout(() => {
      // Tambah class fade dan hapus show untuk animasi
      alert.classList.add("fade");
      alert.classList.remove("show");
    }, 3000);
  });
});

// Confirm delete action globally
// Fungsi konfirmasi sebelum menghapus data
// Parameter message: pesan konfirmasi yang ditampilkan
function confirmDelete(message = "Are you sure?") {
  // Tampilkan dialog confirm browser
  return confirm(message);
}

// Simple client-side search for book lists
// Fungsi pencarian client-side untuk tabel buku
// Parameter:
// - inputId: ID elemen input pencarian
// - tableId: ID tabel yang akan difilter
function filterTable(inputId, tableId) {
  // Ambil elemen input
  const input = document.getElementById(inputId);
  // Ambil nilai input dan ubah ke lowercase
  const filter = input.value.toLowerCase();
  
  // Ambil semua baris tabel (tr) dari tbody
  const rows = document.querySelectorAll(`#${tableId} tbody tr`);
  
  // Iterasi tiap baris
  rows.forEach(row => {
    // Ambil text content baris dan ubah ke lowercase
    const text = row.textContent.toLowerCase();
    // Tampilkan/sembunyikan baris berdasarkan filter
    row.style.display = text.includes(filter) ? "" : "none";
  });
}

// Cara penggunaan:
// 1. Alert autodismiss:
//    - Tambahkan class "alert" ke elemen yang ingin auto-dismiss
//
// 2. Confirm delete:
//    - Panggil confirmDelete() di onclick button/link
//    - Contoh: onclick="return confirmDelete('Yakin hapus?')"
//
// 3. Filter table:
//    - Tambahkan ID ke input pencarian dan tabel
//    - Panggil filterTable() di oninput input
//    - Contoh: oninput="filterTable('searchInput','booksTable')"
