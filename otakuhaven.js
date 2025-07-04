document.addEventListener('DOMContentLoaded', function() {
    const productGrid = document.querySelector('.product-grid');
    const leftButton = document.querySelector('.nav-button.left');
    const rightButton = document.querySelector('.nav-button.right');
    
    leftButton.addEventListener('click', () => {
        productGrid.scrollBy({ left: -300, behavior: 'smooth' });
    });
    
    rightButton.addEventListener('click', () => {
        productGrid.scrollBy({ left: 300, behavior: 'smooth' });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    
    searchForm.addEventListener('submit', function(e) {
        // You could add validation or search logic here
        // For now, it will just pass the query to the results page
        
        // Example: If you want to ensure One Piece shows specific results
        const query = searchInput.value.toLowerCase();
        if (query.includes('one piece')) {
            // You could redirect to a specific page or highlight certain results
            // This is already handled by the form action
        }
    });
});