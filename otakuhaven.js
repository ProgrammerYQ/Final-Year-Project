const goods = document.querySelector('.goods');
// Duplicate items for continuous scrolling
const items = goods.innerHTML;
goods.innerHTML += items; // Append the same items again