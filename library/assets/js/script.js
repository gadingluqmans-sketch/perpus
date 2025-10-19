// =========================================
// Custom JS for Library System
// =========================================

// Auto-dismiss Bootstrap alerts after 3 seconds
document.addEventListener("DOMContentLoaded", function () {
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.classList.add("fade");
      alert.classList.remove("show");
    }, 3000);
  });
});

// Confirm delete action globally
function confirmDelete(message = "Are you sure?") {
  return confirm(message);
}

// Simple client-side search for book lists
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll(`#${tableId} tbody tr`);
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
}
    