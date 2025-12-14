<?php
$page_title = 'Shopping Cart - Glowing';
include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>

<style>
    .cart-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    .cart-item {
        display: grid;
        grid-template-columns: 100px 3fr 1fr 1fr 1fr auto;
        gap: 20px;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid var(--cultured);
    }
    .cart-header {
        font-weight: var(--weight-bold);
        border-bottom: 2px solid var(--black);
    }
    .cart-item-img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: var(--radius-8);
    }
    .cart-item-info h3 {
        font-size: var(--fs-5);
        margin-bottom: 5px;
    }
    .cart-item-info p {
        font-size: var(--fs-6);
        color: var(--sonic-silver);
    }
    /* Using styles from product.php for quantity selector */
    .quantity-selector { display: flex; align-items: center; border: 1px solid var(--cultured); border-radius: var(--radius-8); }
    .quantity-btn { border: none; background: none; padding: 5px 10px; cursor: pointer; font-size: 18px; }
    .quantity-input { width: 40px; text-align: center; border: none; font-size: var(--fs-6); }
    .quantity-input::-webkit-outer-spin-button, .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .quantity-input[type=number] { -moz-appearance: textfield; }
    .remove-btn { color: var(--flame-pea); cursor: pointer; }
    .cart-summary {
        margin-top: 30px;
        float: right;
        width: 100%;
        max-width: 400px;
        text-align: right;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: var(--fs-5);
    }
    .summary-row.total {
        font-size: var(--fs-4);
        font-weight: var(--weight-bold);
        border-top: 1px solid var(--cultured);
        padding-top: 15px;
        margin-top: 15px;
    }
    .checkout-btn-container {
        text-align: right;
        margin-top: 20px;
    }
    .empty-cart {
        text-align: center;
        padding: 80px 0;
    }
</style>

<div class="cart-container" id="cart-page-content">
    <!-- Cart content will be dynamically generated here -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartContainer = document.getElementById('cart-page-content');

    function renderCart(cartData) {
        if (!cartData || !cartData.items || cartData.items.length === 0) {
            cartContainer.innerHTML = `
                <div class="empty-cart">
                    <h2 class="h2">Your cart is empty</h2>
                    <p style="margin: 20px 0;">Looks like you haven't added anything to your cart yet.</p>
                    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            `;
            return;
        }

        let itemsHtml = `
            <h1 class="h2">Your Cart</h1>
            <div class="cart-header cart-item" style="margin-top: 30px;">
                <div class="cart-item-col"></div>
                <div class="cart-item-col">Product</div>
                <div class="cart-item-col">Price</div>
                <div class="cart-item-col">Quantity</div>
                <div class="cart-item-col">Subtotal</div>
                <div class="cart-item-col"></div>
            </div>
        `;
        cartData.items.forEach(item => {
            const subtotal = item.price * item.quantity;
            itemsHtml += `
                <div class="cart-item" data-product-id="${item.id}">
                    <div><img src="../assets/images/${item.image}" alt="${item.name}" class="cart-item-img"></div>
                    <div class="cart-item-info">
                        <h3><a href="product.php?id=${item.id}">${item.name}</a></h3>
                    </div>
                    <div>$${parseFloat(item.price).toFixed(2)}</div>
                    <div>
                        <div class="quantity-selector">
                            <button class="quantity-btn decrease-qty">-</button>
                            <input type="number" class="quantity-input" value="${item.quantity}" min="1">
                            <button class="quantity-btn increase-qty">+</button>
                        </div>
                    </div>
                    <div>$${subtotal.toFixed(2)}</div>
                    <div><ion-icon name="trash-outline" class="remove-btn"></ion-icon></div>
                </div>
            `;
        });

        cartContainer.innerHTML = itemsHtml + `
            <div class="cart-summary">
                <div class="summary-row total">
                    <span>Total</span>
                    <span>$${parseFloat(cartData.total_price).toFixed(2)}</span>
                </div>
                <div class="checkout-btn-container">
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>
            </div>
        `;
        addCartEventListeners();
    }

    function updateCart(productId, quantity) {
        fetch('../api/cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'update', product_id: productId, quantity: quantity })
        })
        .then(res => res.json())
        .then(data => renderCart(data.cart))
        .catch(console.error);
    }
    
    function addCartEventListeners() {
        document.querySelectorAll('.cart-item').forEach(item => {
            const productId = item.dataset.productId;
            if(!productId) return;
            
            item.querySelector('.decrease-qty').addEventListener('click', () => {
                const input = item.querySelector('.quantity-input');
                let qty = parseInt(input.value);
                if (qty > 1) updateCart(productId, qty - 1);
            });
            
            item.querySelector('.increase-qty').addEventListener('click', () => {
                const input = item.querySelector('.quantity-input');
                let qty = parseInt(input.value);
                updateCart(productId, qty + 1);
            });

            item.querySelector('.quantity-input').addEventListener('change', (e) => {
                 const qty = parseInt(e.target.value);
                 if (qty >= 1) {
                     updateCart(productId, qty);
                 } else {
                     // fallback to 1 if user enters invalid number
                     e.target.value = 1;
                     updateCart(productId, 1);
                 }
            });

            item.querySelector('.remove-btn').addEventListener('click', () => {
                if (confirm('Remove this item from your cart?')) {
                     fetch('../api/cart.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ action: 'remove', product_id: productId })
                    })
                    .then(res => res.json())
                    .then(data => renderCart(data.cart))
                    .catch(console.error);
                }
            });
        });
    }

    // Initial Load
    fetch('../api/cart.php')
        .then(res => res.json())
        .then(data => renderCart(data.cart))
        .catch(console.error);
});
</script>


<?php
include_once '../includes/footer.php';
?>
