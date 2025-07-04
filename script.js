// script.js

// Simulate form submission and redirect with alert
document.getElementById("buy-form")?.addEventListener("submit", function (e) {
  e.preventDefault();
  alert("Order placed successfully!");
  window.location.href = "receipt.html";
});

// Fill receipt details on page load
document.addEventListener("DOMContentLoaded", () => {
  const receiptBox = document.getElementById("receipt-box");
  if (receiptBox) {
    receiptBox.innerHTML = `
      <p><strong>Title:</strong> Sample Manga</p>
      <p><strong>Seller:</strong> Otaku Haven</p>
      <p><strong>Shipping Status:</strong> In Transit</p>
      <p><strong>Address:</strong> 123 Otaku Lane</p>
    `;
  }
});

// Password match check (for register/reset forms)
const pw = document.getElementById("password");
const confirm = document.getElementById("confirm");
if (pw && confirm) {
  confirm.addEventListener("input", () => {
    const msg = document.getElementById("message");
    if (pw.value !== confirm.value) {
      msg.textContent = "Passwords do not match!";
      msg.style.color = "red";
    } else {
      msg.textContent = "";
    }
  });
}

// Return home button
function goHome() {
  window.location.href = "OtakuHavenProto.html";
}
