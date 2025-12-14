<?php
$page_title = 'Login - Glowing';
include_once '../includes/header.php';
include_once '../includes/navbar.php';
?>

<style>
    .auth-section {
        padding: 80px 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
    }
    .auth-container {
        width: 100%;
        max-width: 450px;
        padding: 40px;
        background-color: #fff;
        border: 1px solid var(--cultured);
        border-radius: var(--radius-12);
        box-shadow: 0 4px 20px hsla(0, 0%, 0%, 0.05);
    }
    .auth-title {
        text-align: center;
        margin-bottom: 30px;
    }
    .auth-form .form-group {
        margin-bottom: 20px;
    }
    .auth-form .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: var(--weight-medium);
    }
    .auth-form .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--cultured);
        border-radius: var(--radius-8);
        font-size: var(--fs-6);
    }
    .auth-form .btn-primary {
        width: 100%;
        margin-top: 10px;
    }
    .auth-link {
        text-align: center;
        margin-top: 20px;
    }
    .auth-link a {
        color: var(--black);
        text-decoration: underline;
    }
    .message {
        padding: 15px;
        border-radius: var(--radius-8);
        margin-bottom: 20px;
        text-align: center;
        font-weight: var(--weight-medium);
    }
    .message.success {
        background-color: hsla(145, 63%, 42%, 0.1);
        color: var(--black);
    }
    .message.error {
        background-color: hsla(0, 79%, 63%, 0.1);
        color: var(--black);
    }
</style>

<section class="auth-section">
    <div class="auth-container">
        <h2 class="h2 auth-title">Customer Login</h2>
        <div id="message-container"></div>
        <form id="login-form" class="auth-form">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        <div class="auth-link">
            <p>New customer? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</section>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const messageContainer = document.getElementById('message-container');

    const data = {
        action: 'login',
        email: email,
        password: password
    };

    fetch('../api/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        messageContainer.innerHTML = `<div class="message ${result.status}">${result.message}</div>`;
        if (result.status === 'success') {
            // Redirect to homepage or profile page after a short delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageContainer.innerHTML = `<div class="message error">An unexpected error occurred. Please try again.</div>`;
    });
});
</script>

<?php
include_once '../includes/footer.php';
?>
