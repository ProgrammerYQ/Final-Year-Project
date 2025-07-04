// Global variables to store all products and current results
let allProducts = [];
let currentResults = [];

function displaySearchResults(query) {
    const resultsList = document.getElementById('results-list');
    const noResults = document.getElementById('no-results');
    const resultsCount = document.getElementById('results-count');
    
    resultsList.innerHTML = '';
    noResults.style.display = 'none';

    // Get category from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get('category');
    let searchTerm = (query || '').toLowerCase().trim();
    let categoryTerm = (categoryParam || '').toLowerCase().trim();

    // Map dropdown values to product category names
    const categoryMap = {
        manga: 'japanese manga',
        manhwa: 'korean comics',
        manhua: 'chinese comics',
        comic: 'western comics',
        novel: 'novels',
        stationary: 'stationery',
        all: ''
    };
    if (categoryMap[categoryTerm]) {
        categoryTerm = categoryMap[categoryTerm];
    }

    allProducts = getAllProducts();
    let matchingProducts = [];

    if (categoryParam && categoryParam !== 'all') {
        // Filter by mapped category only
        matchingProducts = allProducts.filter(product =>
            product.category && product.category.toLowerCase() === categoryTerm
        );
        document.getElementById('search-query').textContent = categoryParam.charAt(0).toUpperCase() + categoryParam.slice(1);
        document.getElementById('search-input').value = '';
        document.getElementById('categorySelect').value = categoryParam;
    } else if (searchTerm) {
        // Filter by query (title, condition, category, description)
        allProducts.forEach(product => {
            const titleMatch = product.title.toLowerCase().includes(searchTerm);
            const conditionMatch = product.condition.toLowerCase().includes(searchTerm);
            const categoryMatch = product.category && product.category.toLowerCase().includes(searchTerm);
            const descriptionMatch = product.description.toLowerCase().includes(searchTerm);
            if (titleMatch || conditionMatch || categoryMatch || descriptionMatch) {
                matchingProducts.push(product);
            }
        });
        document.getElementById('search-query').textContent = query || 'All Products';
        document.getElementById('search-input').value = query || '';
        document.getElementById('categorySelect').value = 'all';
    } else {
        // Show all products
        matchingProducts = allProducts;
        document.getElementById('search-query').textContent = 'All Products';
        document.getElementById('search-input').value = '';
        document.getElementById('categorySelect').value = 'all';
    }

    currentResults = matchingProducts;
    if (matchingProducts.length > 0) {
        displayFilteredResults(matchingProducts);
    } else {
        resultsList.innerHTML = '';
        noResults.style.display = 'block';
        resultsCount.textContent = '0';
    }
}

function filterResults(filter, query) {
    let filteredResults = [];
    
    if (filter === 'all') {
        filteredResults = currentResults;
    } else {
        filteredResults = currentResults.filter(product => {
            const condition = product.condition.toLowerCase();
            switch (filter) {
                case 'first':
                    return condition.includes('first');
                case 'second':
                    return condition.includes('second');
                case 'third':
                    return condition.includes('third');
                default:
                    return true;
            }
        });
    }
    
    displayFilteredResults(filteredResults);
}

function displayFilteredResults(products) {
    const resultsList = document.getElementById('results-list');
    const resultsCount = document.getElementById('results-count');
    
    resultsList.innerHTML = '';
    resultsCount.textContent = products.length;
    
    if (products.length === 0) {
        resultsList.innerHTML = '<div class="result-item">No products match your current filter.</div>';
        return;
    }
    
    products.forEach(product => {
        createProductItem(product, resultsList);
    });
}

function getAllProducts() {
    // Helper to generate all three conditions for a poster
    function makeConditions(base) {
        return [
            {
                ...base,
                condition: "First Hand",
                price: base.priceFirst || base.price || "$49.99",
                seller: base.sellerFirst || base.seller || "OfficialStore",
                description: base.descriptionFirst || base.description || "Brand new, never used."
            },
            {
                ...base,
                condition: "Second Hand",
                price: base.priceSecond || "$29.99",
                seller: base.sellerSecond || "TrustedFan",
                description: base.descriptionSecond || "Gently used, excellent condition."
            },
            {
                ...base,
                condition: "Third Hand",
                price: base.priceThird || "$19.99",
                seller: base.sellerThird || "CollectorZ",
                description: base.descriptionThird || "Well-loved, some signs of wear."
            }
        ];
    }

    // List of ALL unique posters with exact titles from OtakuHavenProto.html
    const posters = [
        // Japanese Manga
        {
            title: "One Piece Manga Panel Collection",
            image: "Japanese manga/10-best-one-piece-manga-panels.avif",
            category: "Japanese Manga",
            price: "$49.99",
            seller: "MangaMaster",
            description: "Premium One Piece manga panels."
        },
        {
            title: "Romance Manga Collection",
            image: "Japanese manga/71jqm3aQqpL._AC_SL1500_.jpg",
            category: "Japanese Manga",
            price: "$39.99",
            seller: "MangaRomance",
            description: "Beautiful romance manga collection."
        },
        {
            title: "Initial D Complete Series",
            image: "Japanese manga/71xQCzfzYLL._AC_UF894,1000_QL80_.jpg",
            category: "Japanese Manga",
            price: "$59.99",
            seller: "RacingFan",
            description: "Complete Initial D manga series."
        },
        {
            title: "Jujutsu Kaisen Volume Set",
            image: "Japanese manga/Jujutsu_kaisen.jpg",
            category: "Japanese Manga",
            price: "$59.99",
            seller: "SorceryShop",
            description: "Jujutsu Kaisen manga set."
        },
        {
            title: "Chainsaw Man Poster Art",
            image: "Japanese manga/cool-poster-art-for-chainsaw-man-created-by-vincent-aseo.jpg",
            category: "Japanese Manga",
            price: "$29.99",
            seller: "ArtGallery",
            description: "Chainsaw Man poster."
        },
        {
            title: "Durarara!! Light Novel Set",
            image: "Japanese manga/filters_quality(95)format(webp).webp",
            category: "Japanese Manga",
            price: "$42.99",
            seller: "NovelWorld",
            description: "Durarara!! light novel set."
        },
        {
            title: "Haikyuu!! Volleyball Manga",
            image: "Japanese manga/haikyu.jpg",
            category: "Japanese Manga",
            price: "$44.99",
            seller: "HaikyuStore",
            description: "Haikyuu!! volleyball manga set."
        },
        {
            title: "Slam Dunk Basketball Manga",
            image: "Japanese manga/slamdunk.jpg",
            category: "Japanese Manga",
            price: "$39.99",
            seller: "SlamDunkShop",
            description: "Slam Dunk basketball manga set."
        },
        {
            title: "Fate Series Manga",
            image: "Japanese manga/fate.jpg",
            category: "Japanese Manga",
            price: "$34.99",
            seller: "FateStore",
            description: "Fate series manga collection."
        },
        {
            title: "Demon Slayer Manga",
            image: "Japanese manga/demonslayer.jpg",
            category: "Japanese Manga",
            price: "$54.99",
            seller: "DemonSlayerShop",
            description: "Demon Slayer manga set."
        },
        
        // Korean Comics (Manhwa)
        {
            title: "Business Proposal",
            image: "Korean comics/business proposal.jpg",
            category: "Korean Comics",
            price: "$24.99",
            seller: "KoreaManga",
            description: "Business Proposal manhwa poster."
        },
        {
            title: "Itaewon Class",
            image: "Korean comics/Itaewon Class.jpeg",
            category: "Korean Comics",
            price: "$22.99",
            seller: "DramaCollector",
            description: "Itaewon Class manhwa poster."
        },
        {
            title: "Navillera",
            image: "Korean comics/Navillera.jpg",
            category: "Korean Comics",
            price: "$21.99",
            seller: "BalletFan",
            description: "Navillera manhwa poster."
        },
        {
            title: "Our Beloved Summer",
            image: "Korean comics/Our Beloved Summer.jpg",
            category: "Korean Comics",
            price: "$23.99",
            seller: "RomanceShop",
            description: "Our Beloved Summer manhwa poster."
        },
        {
            title: "Romance 101",
            image: "Korean comics/Romance 101.webp",
            category: "Korean Comics",
            price: "$20.99",
            seller: "LoveStories",
            description: "Romance 101 manhwa poster."
        },
        {
            title: "Sweet Home",
            image: "Korean comics/sweet home.webp",
            category: "Korean Comics",
            price: "$22.99",
            seller: "HorrorFan",
            description: "Sweet Home manhwa poster."
        },
        {
            title: "True Beauty",
            image: "Korean comics/True Beauty.webp",
            category: "Korean Comics",
            price: "$21.99",
            seller: "BeautyCollector",
            description: "True Beauty manhwa poster."
        },
        
        // Chinese Comics (Manhua)
        {
            title: "Family",
            image: "chinese comics/5-A-256255-1030x842.png",
            category: "Chinese Comics",
            price: "$19.99",
            seller: "ChinaManga",
            description: "Family manhua poster."
        },
        {
            title: "War",
            image: "chinese comics/default.jpg",
            category: "Chinese Comics",
            price: "$18.99",
            seller: "BattleFan",
            description: "War manhua poster."
        },
        {
            title: "Mountain",
            image: "chinese comics/images (1).jpeg",
            category: "Chinese Comics",
            price: "$20.99",
            seller: "NatureLover",
            description: "Mountain manhua poster."
        },
        {
            title: "Politics",
            image: "chinese comics/images (2).jpeg",
            category: "Chinese Comics",
            price: "$19.99",
            seller: "HistoryBuff",
            description: "Politics manhua poster."
        },
        {
            title: "Farm",
            image: "chinese comics/images (3).jpeg",
            category: "Chinese Comics",
            price: "$18.99",
            seller: "RuralLife",
            description: "Farm manhua poster."
        },
        {
            title: "Restaurant",
            image: "chinese comics/images (4).jpeg",
            category: "Chinese Comics",
            price: "$19.99",
            seller: "FoodieFan",
            description: "Restaurant manhua poster."
        },
        {
            title: "Fight",
            image: "chinese comics/images.jpeg",
            category: "Chinese Comics",
            price: "$20.99",
            seller: "ActionCollector",
            description: "Fight manhua poster."
        },
        
        // Western Comics
        {
            title: "The Lost World",
            image: "western comics/65834.webp",
            category: "Western Comics",
            price: "$25.99",
            seller: "AdventureComics",
            description: "The Lost World comic poster."
        },
        {
            title: "Ghost Rider",
            image: "western comics/70180.webp",
            category: "Western Comics",
            price: "$24.99",
            seller: "MarvelFan",
            description: "Ghost Rider comic poster."
        },
        {
            title: "Spiderman",
            image: "western comics/download (1).jpeg",
            category: "Western Comics",
            price: "$23.99",
            seller: "SpiderCollector",
            description: "Spiderman comic poster."
        },
        {
            title: "Cowboy",
            image: "western comics/f8b036072120e8f350b5f7737391157d_original.avif",
            category: "Western Comics",
            price: "$22.99",
            seller: "WesternFan",
            description: "Cowboy comic poster."
        },
        {
            title: "Planet",
            image: "western comics/images.jpeg",
            category: "Western Comics",
            price: "$21.99",
            seller: "SpaceCollector",
            description: "Planet comic poster."
        },
        {
            title: "Cowboys 2",
            image: "western comics/Western_Comics_1.webp",
            category: "Western Comics",
            price: "$21.99",
            seller: "WildWest",
            description: "Cowboys 2 comic poster."
        },
        {
            title: "Ultimate Spider Man",
            image: "western comics/Ultimate spider man.jpg",
            category: "Western Comics",
            price: "$27.99",
            seller: "SpiderManShop",
            description: "Ultimate Spider Man comic poster."
        },
        {
            title: "Amazing Fantasy 15",
            image: "western comics/Amazing_Fantasy_15.jpg",
            category: "Western Comics",
            price: "$29.99",
            seller: "MarvelCollector",
            description: "Amazing Fantasy 15 comic poster."
        },
        {
            title: "Action Comics",
            image: "western comics/Action_Comics.jpg",
            category: "Western Comics",
            price: "$28.99",
            seller: "ActionComicsShop",
            description: "Action Comics poster."
        },
        {
            title: "Titans #1 Landscape Format Marvel UK",
            image: "western comics/titans #1 landscape format marvel uk.jpg",
            category: "Western Comics",
            price: "$26.99",
            seller: "TitansShop",
            description: "Titans #1 landscape format Marvel UK comic poster."
        },
        
        // Novels
        {
            title: "Sentinel",
            image: "Novel/33f7251d-3c3d-4223-be32-0c637c4164f3.jpg",
            category: "Novels",
            price: "$29.99",
            seller: "SciFiBooks",
            description: "Sentinel novel cover."
        },
        {
            title: "Secrets",
            image: "Novel/4f971bfe-2ea6-4ff7-8c5a-7eba039fa15c.jpg",
            category: "Novels",
            price: "$28.99",
            seller: "MysteryReader",
            description: "Secrets novel cover."
        },
        {
            title: "Saving Madonna",
            image: "Novel/9f7d82bf-735b-4800-9672-c17602938a77.jpg",
            category: "Novels",
            price: "$27.99",
            seller: "DramaBooks",
            description: "Saving Madonna novel cover."
        },
        {
            title: "Backstory",
            image: "Novel/attachment_124417681.jpeg",
            category: "Novels",
            price: "$26.99",
            seller: "VintageBooks",
            description: "Backstory novel cover."
        },
        {
            title: "Lost Gospel",
            image: "Novel/images (1).jpeg",
            category: "Novels",
            price: "$25.99",
            seller: "ReligiousBooks",
            description: "Lost Gospel novel cover."
        },
        {
            title: "Rowan Chronicle",
            image: "Novel/images.jpeg",
            category: "Novels",
            price: "$24.99",
            seller: "FantasyBooks",
            description: "Rowan Chronicle novel cover."
        },
        {
            title: "Milestone",
            image: "Novel/photo.jpeg",
            category: "Novels",
            price: "$23.99",
            seller: "InspirationBooks",
            description: "Milestone novel cover."
        },
        {
            title: "Diary of the Farting Creeper",
            image: "Novel/Diary of the farting creeper.jpg",
            category: "Novels",
            price: "$19.99",
            seller: "CreeperBooks",
            description: "Diary of the Farting Creeper novel cover."
        },
        {
            title: "The Cheater",
            image: "Novel/The Cheater.jpg",
            category: "Novels",
            price: "$21.99",
            seller: "CheaterBooks",
            description: "The Cheater novel cover."
        },
        {
            title: "Harry Potter",
            image: "Novel/Harrypotter.jpg",
            category: "Novels",
            price: "$24.99",
            seller: "PotterBooks",
            description: "Harry Potter novel cover."
        },
        
        // Stationery
        {
            title: "Cutter Knife",
            image: "Stationary/cutter knife.jpg",
            category: "Stationery",
            price: "$4.99",
            seller: "StationeryShop",
            description: "Sharp cutter knife for crafts."
        },
        {
            title: "Highlights",
            image: "Stationary/highlights.avif",
            category: "Stationery",
            price: "$2.99",
            seller: "StationeryShop",
            description: "Bright highlighter set."
        },
        {
            title: "Pens",
            image: "Stationary/pens.jpg",
            category: "Stationery",
            price: "$3.99",
            seller: "StationeryShop",
            description: "Smooth writing pens."
        },
        {
            title: "Mechanical Pencil",
            image: "Stationary/mechanical pencil.jpg",
            category: "Stationery",
            price: "$2.49",
            seller: "StationeryShop",
            description: "Precise mechanical pencil."
        },
        {
            title: "Pencil",
            image: "Stationary/pencil.webp",
            category: "Stationery",
            price: "$1.99",
            seller: "StationeryShop",
            description: "Classic pencil."
        },
        {
            title: "Water Colour",
            image: "Stationary/water colour.jpg",
            category: "Stationery",
            price: "$5.99",
            seller: "StationeryShop",
            description: "Water colour set."
        },
        {
            title: "Colour Pencils",
            image: "Stationary/colour pencils.webp",
            category: "Stationery",
            price: "$6.99",
            seller: "StationeryShop",
            description: "Colour pencils set."
        },
        {
            title: "Gel Highlighter",
            image: "Stationary/gel highlighter.jpg",
            category: "Stationery",
            price: "$3.49",
            seller: "StationeryShop",
            description: "Smooth gel highlighter."
        },
        {
            title: "Manga Making Paper",
            image: "Stationary/manga making paper.png",
            category: "Stationery",
            price: "$7.99",
            seller: "StationeryShop",
            description: "Paper for manga drawing."
        },
        {
            title: "Ink Pen",
            image: "Stationary/Ink pen.jpg",
            category: "Stationery",
            price: "$2.99",
            seller: "StationeryShop",
            description: "Fine ink pen."
        }
    ];

    // Flatten all posters with all three conditions
    return posters.flatMap(makeConditions);
}

function createProductItem(product, container) {
    const item = document.createElement('div');
    item.className = 'result-item';
    
    const conditionClass = product.condition.toLowerCase().includes('first') ? 'first' : 
                          product.condition.toLowerCase().includes('second') ? 'second' : 'third';
    
    item.innerHTML = `
        <div class="product-result">
            <img src="${product.image}" alt="${product.title}" onclick="window.location.href='productui.html?image=${encodeURIComponent(product.image)}&condition=${encodeURIComponent(product.condition)}'">
            <div class="product-info">
                <h3>${product.title}</h3>
                <div class="condition-badge ${conditionClass}">${product.condition}</div>
                <p class="price">${product.price}</p>
                <p class="description">${product.description}</p>
                <p class="seller">Sold by: ${product.seller}</p>
                <p class="category">Category: ${product.category}</p>
                <div class="product-actions">
                    <button class="buy-btn" onclick="addToCart('${product.title}')">Add to Cart</button>
                    <button class="wishlist-btn" onclick="addToWishlistWithCondition('${product.title.replace(/'/g, "\\'")}', '${product.condition.replace(/'/g, "\\'")}')">Add to Wishlist</button>
                </div>
            </div>
        </div>
    `;
    container.appendChild(item);
}

function addToCart(productTitle) {
    alert(`Added "${productTitle}" to cart!`);
}

function addToWishlistWithCondition(productTitle, condition) {
    // Find the product in allProducts with matching title and condition
    const product = allProducts.find(p => p.title === productTitle && p.condition === condition);
    if (!product) return;
    let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
    if (!wishlist.some(item => item.title === product.title && item.condition === product.condition)) {
        wishlist.push(product);
        localStorage.setItem('wishlist', JSON.stringify(wishlist));
    }
    window.location.href = 'wishlist.html';
}