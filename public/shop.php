<?php
$page_title = 'Shop - Glowing';
include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>
<style>
    .shop-page-container {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        padding: 40px;
        max-width: 1400px;
        margin: 0 auto;
    }
    .shop-sidebar {
        border-right: 1px solid var(--cultured);
        padding-right: 30px;
    }
    .sidebar-widget {
        margin-bottom: 30px;
    }
    .widget-title {
        font-size: var(--fs-4);
        margin-bottom: 20px;
        border-bottom: 1px solid var(--cultured);
        padding-bottom: 10px;
    }
    .category-list li {
        margin-bottom: 10px;
    }
    .category-list a {
        color: var(--sonic-silver);
        cursor: pointer;
        transition: var(--transition-1);
    }
    .category-list a:hover, .category-list a.active {
        color: var(--black);
        font-weight: var(--weight-medium);
    }
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    /* Re-using shop-card styles from the main stylesheet */
</style>

<div class="shop-page-container">
    <aside class="shop-sidebar">
        <div class="sidebar-widget">
            <h3 class="widget-title">Categories</h3>
            <ul class="category-list" id="category-filter-list">
                <li><a href="#" class="category-filter active" data-id="all">All Products</a></li>
                <!-- Categories will be loaded here dynamically -->
            </ul>
        </div>
        <!-- Other filters like price range could go here -->
    </aside>

    <main class="shop-main">
        <div class="title-wrapper" style="margin-bottom: 30px;">
            <h2 class="h2 section-title" id="shop-title">All Products</h2>
        </div>
        <div class="product-grid" id="product-grid">
            <!-- Products will be loaded here dynamically -->
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryList = document.getElementById('category-filter-list');
    const productGrid = document.getElementById('product-grid');
    const shopTitle = document.getElementById('shop-title');

    // --- Load Categories for Filtering ---
    function loadCategories() {
        fetch('../api/categories.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    data.data.forEach(cat => {
                        categoryList.innerHTML += `<li><a href="#" class="category-filter" data-id="${cat.id}" data-name="${cat.name}">${cat.name}</a></li>`;
                    });
                }
            });
    }

    // --- Load Products ---
    function loadProducts(categoryId = 'all') {
        let url = '../api/products.php';
        if (categoryId !== 'all') {
            url += `?category_id=${categoryId}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                productGrid.innerHTML = ''; // Clear existing products
                if (data.status === 'success' && data.data.length > 0) {
                    data.data.forEach(product => {
                        productGrid.innerHTML += `
                        <div class="shop-card">
                            <div class="card-banner img-holder" style="--width: 540; --height: 720;">
                                <img src="../assets/images/${product.image}" width="540" height="720" loading="lazy" alt="${product.name}" class="img-cover">
                                <div class="card-actions">
                                    <button class="action-btn" aria-label="add to cart">
                                        <ion-icon name="bag-handle-outline" aria-hidden="true"></ion-icon>
                                    </button>
                                </div>
                            </div>
                            <div class="card-content">
                                <div class="price"><span class="span">$${product.price}</span></div>
                                <h3><a href="product.php?id=${product.id}" class="card-title">${product.name}</a></h3>
                                <div class="card-rating">
                                    <div class="rating-wrapper" aria-label="5 start rating">
                                        <!-- Static stars for now -->
                                        <ion-icon name="star" aria-hidden="true"></ion-icon>
                                        <ion-icon name="star" aria-hidden="true"></ion-icon>
                                        <ion-icon name="star" aria-hidden="true"></ion-icon>
                                        <ion-icon name="star" aria-hidden="true"></ion-icon>
                                        <ion-icon name="star-half" aria-hidden="true"></ion-icon>
                                    </div>
                                    <p class="rating-text">(4.5)</p>
                                </div>
                            </div>
                        </div>
                        `;
                    });
                } else {
                    productGrid.innerHTML = '<p>No products found in this category.</p>';
                }
            })
            .catch(error => {
                console.error('Error loading products:', error);
                productGrid.innerHTML = '<p>Error loading products. Please try again later.</p>';
            });
    }

    // --- Event Listener for Category Clicks ---
    categoryList.addEventListener('click', function(e) {
        e.preventDefault();
        if (e.target.classList.contains('category-filter')) {
            // Update active state
            document.querySelectorAll('.category-filter').forEach(el => el.classList.remove('active'));
            e.target.classList.add('active');
            
            const categoryId = e.target.dataset.id;
            const categoryName = e.target.dataset.name || 'All Products';
            
            shopTitle.textContent = categoryName;
            loadProducts(categoryId);
        }
    });

    // --- Initial Load ---
    loadCategories();
    loadProducts();
});
</script>

<?php
include_once '../includes/footer.php';
?>
