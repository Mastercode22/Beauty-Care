<?php
// We can get the product ID to set the page title dynamically, though it requires a DB call.
// For simplicity, we'll set a generic title first.
$page_title = 'Product Details - Glowing'; 
include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>
<style>
    .product-detail-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        max-width: 1200px;
        margin: 50px auto;
        padding: 0 20px;
    }
    .product-gallery {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .product-gallery .img-holder {
        border-radius: var(--radius-12);
        overflow: hidden;
    }
    .product-info .product-category {
        font-size: var(--fs-7);
        color: var(--sonic-silver);
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .product-info .product-title {
        font-size: var(--fs-1);
        margin-bottom: 20px;
    }
    .product-info .product-price {
        font-size: var(--fs-2);
        color: var(--black);
        margin-bottom: 20px;
    }
    .product-info .product-description {
        color: var(--sonic-silver);
        line-height: 1.7;
        margin-bottom: 30px;
    }
    .product-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    .product-actions .quantity-selector {
        display: flex;
        align-items: center;
        border: 1px solid var(--cultured);
        border-radius: var(--radius-8);
    }
    .product-actions .quantity-btn {
        border: none;
        background: none;
        padding: 10px 15px;
        cursor: pointer;
        font-size: 20px;
    }
    .product-actions .quantity-input {
        width: 50px;
        text-align: center;
        border: none;
        font-size: var(--fs-5);
    }
    /* Hide number input arrows */
    .quantity-input::-webkit-outer-spin-button,
    .quantity-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .quantity-input[type=number] {
        -moz-appearance: textfield;
    }
</style>

<div id="product-detail-content">
    <!-- Product details will be loaded here dynamically -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productDetailContainer = document.getElementById('product-detail-content');
    const productId = new URLSearchParams(window.location.search).get('id');

    if (!productId) {
        productDetailContainer.innerHTML = '<p style="text-align: center; padding: 50px;">Product not found. Please select a product from the shop.</p>';
        return;
    }

    fetch(`../api/products.php?id=${productId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const product = data.data;
                document.title = `${product.name} - Glowing`; // Update page title
                
                productDetailContainer.innerHTML = `
                <div class="product-detail-container">
                    <section class="product-gallery">
                        <div class="img-holder">
                            <img src="../assets/images/${product.image}" alt="${product.name}" class="img-cover">
                        </div>
                        <!-- Additional gallery images could go here -->
                    </section>

                    <section class="product-info">
                        <p class="product-category">${product.category_name || 'Uncategorized'}</p>
                        <h2 class="h1 product-title">${product.name}</h2>
                        <p class="h2 product-price">$${product.price}</p>
                        <p class="product-description">${product.description}</p>
                        
                        <div class="product-actions">
                            <div class="quantity-selector">
                                <button class="quantity-btn" id="decrease-qty">-</button>
                                <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="${product.stock}">
                                <button class="quantity-btn" id="increase-qty">+</button>
                            </div>
                            <button class="btn btn-primary" id="add-to-cart-btn">Add to Cart</button>
                        </div>
                         <div id="add-to-cart-message" style="margin-top: 15px;"></div>
                    </section>
                </div>
                `;
                
                // Add event listeners for quantity buttons
                const decreaseBtn = document.getElementById('decrease-qty');
                const increaseBtn = document.getElementById('increase-qty');
                const quantityInput = document.getElementById('quantity');

                decreaseBtn.addEventListener('click', () => {
                    let qty = parseInt(quantityInput.value);
                    if (qty > 1) {
                        quantityInput.value = qty - 1;
                    }
                });

                increaseBtn.addEventListener('click', () => {
                    let qty = parseInt(quantityInput.value);
                    if (qty < parseInt(quantityInput.max)) {
                       quantityInput.value = qty + 1;
                    }
                });

                // Add to cart functionality
                document.getElementById('add-to-cart-btn').addEventListener('click', function() {
                    const quantity = parseInt(document.getElementById('quantity').value);
                    const messageContainer = document.getElementById('add-to-cart-message');

                    fetch('../api/cart.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            action: 'add',
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            messageContainer.innerHTML = `<p style="color: green;">${data.message}</p>`;
                             // Optionally, update a mini-cart icon in the header
                        } else {
                            messageContainer.innerHTML = `<p style="color: red;">${data.message}</p>`;
                        }
                        setTimeout(() => messageContainer.innerHTML = '', 3000);
                    })
                    .catch(error => {
                        console.error('Add to cart error:', error);
                        messageContainer.innerHTML = `<p style="color: red;">Error adding to cart.</p>`;
                        setTimeout(() => messageContainer.innerHTML = '', 3000);
                    });
                });

            } else {
                productDetailContainer.innerHTML = `<p style="text-align: center; padding: 50px;">${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Error fetching product:', error);
            productDetailContainer.innerHTML = '<p style="text-align: center; padding: 50px;">Error loading product details.</p>';
        });
});
</script>

<?php
include_once '../includes/footer.php';
?>
