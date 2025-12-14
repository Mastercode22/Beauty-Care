<?php
include_once '../includes/admin_guard.php';
$page_title = 'Admin - Manage Products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .admin-table th, .admin-table td { padding: 10px; border: 1px solid #eee; text-align: left; }
        .admin-table th { background-color: #f9f9f9; }
        .admin-table img { max-width: 50px; border-radius: 4px; }
        .actions a { margin-right: 10px; cursor: pointer; }
        .form-container { display: none; padding: 30px; background-color: #f9f9f9; border-radius: 8px; margin-top: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .full-width { grid-column: 1 / -1; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background-color: #e4f8e4; color: #3d8b3d; }
        .message.error { background-color: #f8e4e4; color: #8b3d3d; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="h2">Manage Products</h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <button id="add-new-btn" class="btn btn-primary">Add New Product</button>
        </div>
    </div>

    <div id="message-container"></div>

    <!-- Product Form -->
    <div class="form-container" id="product-form-container">
        <h2 class="h3" id="form-title">Add New Product</h2>
        <form id="product-form" enctype="multipart/form-data">
            <input type="hidden" id="product-id" name="id">
            <input type="hidden" id="action" name="action" value="create">
             <input type="hidden" id="existing_image" name="existing_image">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required></select>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <img id="image-preview" src="" alt="Image Preview" style="max-width: 100px; margin-top: 10px; display: none;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Product</button>
            <button type="button" class="btn btn-secondary" id="cancel-edit">Cancel</button>
        </form>
    </div>

    <!-- Product List -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="products-list">
            <!-- Products will be loaded here -->
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiProducts = '../api/products.php';
    const apiCategories = '../api/categories.php';
    const productsList = document.getElementById('products-list');
    const productFormContainer = document.getElementById('product-form-container');
    const productForm = document.getElementById('product-form');
    const categorySelect = document.getElementById('category_id');
    const messageContainer = document.getElementById('message-container');
    
    const addNewBtn = document.getElementById('add-new-btn');
    const cancelBtn = document.getElementById('cancel-edit');
    const formTitle = document.getElementById('form-title');
    const imagePreview = document.getElementById('image-preview');

    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        setTimeout(() => messageContainer.innerHTML = '', 4000);
    }

    function toggleForm(show = false) {
        productFormContainer.style.display = show ? 'block' : 'none';
        if (!show) {
            productForm.reset();
            document.getElementById('product-id').value = '';
            document.getElementById('action').value = 'create';
            imagePreview.style.display = 'none';
        }
    }

    addNewBtn.addEventListener('click', () => {
        formTitle.innerText = 'Add New Product';
        toggleForm(true);
    });
    cancelBtn.addEventListener('click', () => toggleForm(false));

    function loadCategories() {
        fetch(apiCategories)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    categorySelect.innerHTML = '<option value="">Select a Category</option>';
                    data.data.forEach(cat => {
                        categorySelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                    });
                }
            });
    }

    function loadProducts() {
        fetch(apiProducts)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    productsList.innerHTML = '';
                    data.data.forEach(prod => {
                        productsList.innerHTML += `
                            <tr data-id="${prod.id}">
                                <td><img src="../assets/images/${prod.image}" alt="${prod.name}"></td>
                                <td>${prod.name}</td>
                                <td>${prod.category_name}</td>
                                <td>$${prod.price}</td>
                                <td>${prod.stock}</td>
                                <td class="actions">
                                    <a class="edit-btn">Edit</a>
                                    <a class="delete-btn">Delete</a>
                                </td>
                            </tr>
                        `;
                    });
                }
            });
    }

    productForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(apiProducts, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const action = document.getElementById('action').value;
                    showMessage('success', `Product successfully ${action === 'create' ? 'created' : 'updated'}.`);
                    toggleForm(false);
                    loadProducts();
                } else {
                    showMessage('error', data.message);
                }
            })
            .catch(error => showMessage('error', 'An error occurred.'));
    });

    productsList.addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        if (!row) return;
        const id = row.dataset.id;

        if (e.target.classList.contains('edit-btn')) {
            fetch(`${apiProducts}?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        const p = data.data;
                        formTitle.innerText = 'Edit Product';
                        document.getElementById('product-id').value = p.id;
                        document.getElementById('action').value = 'update';
                        document.getElementById('name').value = p.name;
                        document.getElementById('category_id').value = p.category_id;
                        document.getElementById('price').value = p.price;
                        document.getElementById('stock').value = p.stock;
                        document.getElementById('description').value = p.description;
                        document.getElementById('existing_image').value = p.image;
                        imagePreview.src = `../assets/images/${p.image}`;
                        imagePreview.style.display = 'block';
                        toggleForm(true);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
        }

        if (e.target.classList.contains('delete-btn')) {
            if (confirm('Are you sure you want to delete this product?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                fetch(apiProducts, { method: 'POST', body: formData})
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMessage('success', 'Product deleted.');
                        loadProducts();
                    } else {
                        showMessage('error', data.message);
                    }
                });
            }
        }
    });

    // Initial Load
    loadCategories();
    loadProducts();
});
</script>

</body>
</html>
