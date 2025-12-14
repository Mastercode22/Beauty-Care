<?php
$page_title = 'Checkout - Glowing';
include_once '../includes/auth_guard.php'; // Protect this page
include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>

<style>
    .checkout-container {
        max-width: 960px;
        margin: 40px auto;
        padding: 20px;
    }
    .checkout-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 40px;
    }
    .checkout-step {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--cultured);
    }
    .step-title {
        font-size: var(--fs-4);
        font-weight: var(--weight-bold);
        margin-bottom: 20px;
    }
    .order-summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: var(--fs-6);
    }
    .order-summary-total {
        display: flex;
        justify-content: space-between;
        font-size: var(--fs-5);
        font-weight: var(--weight-bold);
        padding-top: 10px;
        border-top: 1px solid var(--cultured);
        margin-top: 10px;
    }
    .momo-instructions {
        background-color: var(--ghost-white);
        padding: 20px;
        border-radius: var(--radius-8);
    }
    .momo-instructions p { margin-bottom: 15px; line-height: 1.6; }
    .momo-instructions .merchant-number { font-weight: var(--weight-bold); font-size: var(--fs-4); color: var(--black); }
</style>

<div class="checkout-container">
    <h1 class="h2" style="margin-bottom: 30px;">Checkout</h1>

    <div class="checkout-grid">
        <!-- Left Column: Steps -->
        <div id="checkout-main">
            <!-- Step 1: Shipping -->
            <div id="step-1-shipping" class="checkout-step">
                <h2 class="step-title">1. Shipping Information</h2>
                <form id="shipping-form">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="shipping_address">Shipping Address</label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required class="form-control" style="width: 100%; padding: 10px;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Place Order</button>
                </form>
            </div>

            <!-- Step 2: Payment -->
            <div id="step-2-payment" class="checkout-step" style="display: none;">
                <h2 class="step-title">2. Make Payment</h2>
                <div class="momo-instructions">
                    <p>Please send the total amount of <strong id="total-amount-display"></strong> to the number below:</p>
                    <p class="merchant-number">024 123 4567</p>
                    <p>Use your Order ID as the payment reference: <strong id="order-id-display"></strong></p>
                    <form id="payment-confirmation-form">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="momo-network">Select your network:</label>
                            <select id="momo-network" name="momo-network" required class="form-control" style="width: 100%; padding: 10px;">
                                <option value="MTN">MTN Mobile Money</option>
                                <option value="Telecel">Telecel Cash</option>
                                <option value="AirtelTigo">AirtelTigo Money</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">I Have Paid</button>
                    </form>
                </div>
            </div>
             <!-- Step 3: Confirmation -->
            <div id="step-3-confirmation" style="display: none;">
                <h2 class="h2" style="color: var(--black);">Thank You!</h2>
                <p style="margin: 20px 0;">Your order has been received and is being processed. You will receive an email confirmation shortly.</p>
                <a href="orders.php" class="btn btn-primary">View My Orders</a>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <aside id="order-summary-container">
            <h2 class="step-title">Order Summary</h2>
            <div id="summary-items"></div>
            <div class="order-summary-total">
                <span>Total</span>
                <span id="summary-total">$0.00</span>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentOrder = {};
    const summaryItems = document.getElementById('summary-items');
    const summaryTotal = document.getElementById('summary-total');

    // Fetch cart to populate summary
    fetch('../api/cart.php').then(res => res.json()).then(data => {
        if(data.status === 'success') {
            currentOrder.cart = data.cart;
            summaryTotal.textContent = `$${data.cart.total_price}`;
            document.getElementById('total-amount-display').textContent = `$${data.cart.total_price}`;
            data.cart.items.forEach(item => {
                summaryItems.innerHTML += `
                    <div class="order-summary-item">
                        <span>${item.name} (x${item.quantity})</span>
                        <span>$${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `;
            });
        }
    });

    // Handle Step 1: Place Order
    document.getElementById('shipping-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const shippingAddress = document.getElementById('shipping_address').value;
        
        fetch('../api/orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ shipping_address: shippingAddress })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentOrder.id = data.data.order_id;
                document.getElementById('order-id-display').textContent = currentOrder.id;
                // Move to next step
                document.getElementById('step-1-shipping').style.display = 'none';
                document.getElementById('step-2-payment').style.display = 'block';
            } else {
                alert(`Error: ${data.message}`);
            }
        });
    });

    // Handle Step 2: Confirm Payment
    document.getElementById('payment-confirmation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const network = document.getElementById('momo-network').value;
        
        fetch('../api/payments.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                order_id: currentOrder.id,
                network: network,
                amount: currentOrder.cart.total_price
            })
        })
        .then(res => res.json())
        .then(data => {
             if (data.status === 'success') {
                // Move to final step
                document.getElementById('step-2-payment').style.display = 'none';
                document.getElementById('step-3-confirmation').style.display = 'block';
             } else {
                 alert(`Error: ${data.message}`);
             }
        });
    });
});
</script>

<?php
include_once '../includes/footer.php';
?>
