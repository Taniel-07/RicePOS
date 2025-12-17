// RPOS/admin.js

const AdminManager = {
    init: () => {
        console.log("AdminManager.init() has been called!");
        AdminManager.renderStores();
        AdminManager.renderProducts();
        AdminManager.renderCompletedOrders();
    },
    renderStores: async () => {
        const storeDiv = document.getElementById("adminStoreInfo");
        if (!storeDiv) {
            console.error("Element with ID 'adminStoreInfo' not found!");
            return;
        }
        const response = await API.getStores(); // fetchJSON gihapon
        if (!response.success) return;
        
        // Include buyers who registered as sellers
        storeDiv.innerHTML = response.data.length === 0 ? "No stores registered." : response.data.map(s => `
            <div class="card admin-store-card">
                <h4>${s.store_name || `(User: ${s.username})`}</h4>
                <p><strong>Owner:</strong> ${s.store_owner || s.username}</p>
                <p><strong>Contact:</strong> ${s.contact || s.store_contact || "—"}</p>
                 <p><strong>Address:</strong> ${s.address || "—"}</p>
                 <p><strong>Fulfillment:</strong> ${s.fulfillment_option || "—"}</p>
                 <button class="danger remove-user-btn" data-id="${s.id}" ${s.id == 1 ? 'disabled title="Cannot delete primary admin"' : ''}>
                     Delete Account (${s.username})
                 </button>
            </div>`).join('');

        // Attach event listeners for delete buttons
        document.querySelectorAll('.remove-user-btn').forEach(btn => {
            btn.addEventListener('click', async e => {
                const userId = e.target.dataset.id;
                AdminManager.deleteAccount(userId);
            });
        });
    },
    // NEW FUNCTION: Handle user deletion
    deleteAccount: async (userId) => {
        if (!confirm(`WARNING: Are you sure you want to permanently delete this user (ID: ${userId})? All associated data (products, orders, cart) will be removed. This action is irreversible.`)) {
            return;
        }

        const res = await API.deleteUser(userId);
        
        if (res.success) {
            alert(`User account ID ${userId} deleted successfully.`);
            AdminManager.renderStores(); // Re-render the list of stores/users
            AdminManager.renderProducts(); // Refresh products on display
            AdminManager.renderCompletedOrders(); // Refresh orders on display
        } else {
            alert("Failed to delete user: " + res.message); // Show API error
        }
    },
    renderProducts: async () => {
        const productDiv = document.getElementById("adminProductList");
        if (!productDiv) {
            console.error("Element with ID 'adminProductList' not found!");
            return;
        }
        productDiv.innerHTML = "<p>Loading products for admin view...</p>"; // Display loading state
        
        const response = await API.getProducts(); // Calls RPOS/api/products_get.php
        
        if (!response.success) {
            // IMPORTANT: This will show if RPOS/api/products_get.php fails (e.g., due to DB connection error)
            productDiv.innerHTML = `<p class="error">Error fetching products: ${response.message}</p>`; 
            return;
        }
        
        const products = response.data;
        
        // Render the product list
        productDiv.innerHTML = products.length === 0 ? "No products available." : products.map(p => {
            const sellerInfo = p.seller?.storeName || p.seller_username;
            return `
                 <div class="card admin-product-card">
                    <h4>${p.name} (ID: ${p.id})</h4>
                    <p><strong>Price:</strong> ₱${formatCurrency(p.price)} / ${p.packaging || '25kg'}</p>
                    <p><strong>Stock:</strong> ${p.stock_quantity !== null ? p.stock_quantity : 'N/A'}</p>
                    <p><strong>Seller:</strong> ${sellerInfo || 'N/A'}</p>
                    <p style="margin-bottom:8px;"><strong>Image:</strong> <img src="${p.image_url || 'https://via.placeholder.com/50'}" style="height:50px;width:50px;object-fit:cover;border-radius:4px;"></p>
                    <button class="danger remove-product-btn" data-id="${p.id}">
                        Delete Product
                    </button>
                 </div>
            `;
        }).join('');

        // Attach event listeners for delete buttons
        document.querySelectorAll('.remove-product-btn').forEach(btn => btn.addEventListener('click', async e => {
            const productId = e.target.dataset.id;
            // Assuming AdminManager has a deleteProduct function or you'll create one
            // For now, you might just need to reload: AdminManager.deleteProduct(productId); 
        }));
    },
     renderCompletedOrders: async () => {
// ... (rest of renderCompletedOrders unchanged) ...
    }
};