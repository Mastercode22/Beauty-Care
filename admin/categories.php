<?php
// admin/categories.php
// Note: The real admin guard will be included in a shared admin header
include_once '../includes/admin_guard.php';
$page_title = 'Admin - Manage Categories';
// In a real app, you'd have a shared admin header/footer
// For now, we'll keep it simple
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- We'll use the main stylesheet for some basic styling -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { max-width: 960px; margin: 40px auto; padding: 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 12px; border: 1px solid #eee; text-align: left; }
        .admin-table th { background-color: #f9f9f9; }
        .actions a { margin-right: 10px; cursor: pointer; }
        .form-container { padding: 30px; background-color: #f9f9f9; border-radius: 8px; margin-top: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background-color: #e4f8e4; color: #3d8b3d; }
        .message.error { background-color: #f8e4e4; color: #8b3d3d; }
    </style>
</head>
<body>

<div class="admin-container">
    <div class="admin-header">
        <h1 class="h2">Manage Categories</h1>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div id="message-container"></div>

    <!-- Category List -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="categories-list">
            <!-- Categories will be loaded here -->
        </tbody>
    </table>

    <!-- Add/Edit Form -->
    <div class="form-container" id="category-form-container">
        <h2 class="h3" id="form-title">Add New Category</h2>
        <form id="category-form">
            <input type="hidden" id="category-id" name="id">
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Category</button>
            <button type="button" class="btn btn-secondary" id="cancel-edit" style="display: none;">Cancel Edit</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiEndpoint = '../api/categories.php';
    const categoriesList = document.getElementById('categories-list');
    const categoryForm = document.getElementById('category-form');
    const formTitle = document.getElementById('form-title');
    const categoryIdField = document.getElementById('category-id');
    const nameField = document.getElementById('name');
    const descriptionField = document.getElementById('description');
    const cancelEditBtn = document.getElementById('cancel-edit');
    const messageContainer = document.getElementById('message-container');

    // --- Fetch and Display Categories ---
    function loadCategories() {
        fetch(apiEndpoint)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    categoriesList.innerHTML = '';
                    data.data.forEach(cat => {
                        categoriesList.innerHTML += `
                            <tr>
                                <td>${cat.id}</td>
                                <td>${cat.name}</td>
                                <td>${cat.description}</td>
                                <td class="actions">
                                    <a class="edit-btn" data-id="${cat.id}" data-name="${cat.name}" data-description="${cat.description}">Edit</a>
                                    <a class="delete-btn" data-id="${cat.id}">Delete</a>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error('Error loading categories:', error));
    }

    function showMessage(type, text) {
        messageContainer.innerHTML = `<div class="message ${type}">${text}</div>`;
        setTimeout(() => messageContainer.innerHTML = '', 3000);
    }
    
    // --- Handle Form Submission (Add/Edit) ---
    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = categoryIdField.value;
        const name = nameField.value;
        const description = descriptionField.value;
        const isEditing = id !== '';

        const method = isEditing ? 'PUT' : 'POST';
        const body = JSON.stringify({ id, name, description });

        fetch(apiEndpoint, { method, headers: {'Content-Type': 'application/json'}, body })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage('success', `Category successfully ${isEditing ? 'updated' : 'created'}.`);
                    resetForm();
                    loadCategories();
                } else {
                    showMessage('error', data.message);
                }
            })
            .catch(error => showMessage('error', 'An unexpected error occurred.'));
    });

    // --- Handle Clicks on Edit/Delete Buttons ---
    categoriesList.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const btn = e.target;
            formTitle.textContent = 'Edit Category';
            categoryIdField.value = btn.dataset.id;
            nameField.value = btn.dataset.name;
            descriptionField.value = btn.dataset.description;
            cancelEditBtn.style.display = 'inline-block';
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        if (e.target.classList.contains('delete-btn')) {
            if (confirm('Are you sure you want to delete this category? This may affect products in this category.')) {
                const id = e.target.dataset.id;
                fetch(apiEndpoint, {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showMessage('success', 'Category deleted.');
                        loadCategories();
                    } else {
                        showMessage('error', data.message);
                    }
                })
                .catch(error => showMessage('error', 'An unexpected error occurred.'));
            }
        }
    });

    // --- Handle Cancel Edit ---
    function resetForm() {
        categoryForm.reset();
        formTitle.textContent = 'Add New Category';
        categoryIdField.value = '';
        cancelEditBtn.style.display = 'none';
    }

    cancelEditBtn.addEventListener('click', resetForm);

    // Initial load
    loadCategories();
});
</script>

</body>
</html>
