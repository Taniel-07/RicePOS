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
        storeDiv.innerHTML = response.data.length === 0 ? "No stores registered." : response.data.map(s => `
            <div class="card admin-store-card">
                <h4>${s.store_name || "(no store name)"}</h4>
                <p><strong>Owner:</strong> ${s.store_owner || s.username}</p>
                <p><strong>Contact:</strong> ${s.contact || s.store_contact || "—"}</p>
                 <p><strong>Address:</strong> ${s.address || "—"}</p>
                 <p><strong>Fulfillment:</strong> ${s.fulfillment_option || "—"}</p>
            </div>`).join('');
    },
    renderProducts: async () => {
        const productDiv = document.getElementById("adminProductList");
        if (!productDiv) {
            console.error("Element with ID 'adminProductList' not found!");
            return;
        }
        const response = await API.getProducts(); // fetchJSON gihapon
        if (!response.success) return;
        productDiv.innerHTML = response.data.length === 0 ? "<p>No products available.</p>" : response.data.map(p => `
            <div class="card">
                 <img src="${p.image_url || 'https://via.placeholder.com/100'}" style="width:100px; height: 100px; object-fit:cover; border-radius:8px; margin-bottom: 5px;" alt="${p.name}">
                <h4>${p.name} (${p.type || 'N/A'})</h4>
                <p><b>₱${formatCurrency(p.price)}</b> / ${p.packaging || 'N/A'}</p>
                 <p class="muted">Stock: ${p.stock_quantity ?? 'N/A'}</p>
                <p>Seller: ${p.seller_username}</p>
                <button class="danger remove-product-btn" data-id="${p.id}">Remove</button>
            </div>`).join('');
        
        document.querySelectorAll('.remove-product-btn').forEach(btn => btn.addEventListener('click', async e => {
            const productId = e.target.dataset.id;
            if (confirm("Are you sure you want to permanently remove this product?")) {
                const res = await API.deleteProduct(productId); // fetchJSON gihapon
                if (res.success) {
                    alert('Product removed.');
                    AdminManager.renderProducts();
                }
            }
        }));
    },
     renderCompletedOrders: async () => {
        const ordersDiv = document.getElementById("adminCompletedOrders");
        if (!ordersDiv) {
            console.error("Element with ID 'adminCompletedOrders' not found!");
            return;
        }
        // Use role 'admin', user_id 0 to get all orders
        const response = await API.getOrders('admin', 0); // fetchJSON gihapon
        if (!response.success) return;

        // Filter for completed or cancelled orders
        const historyOrders = response.data.filter(o => o.status === 'completed' || o.status === 'cancelled');

        ordersDiv.innerHTML = historyOrders.length === 0 ? "<p>No completed or cancelled orders.</p>" : historyOrders.map(o => {
             const statusClass = `order-status-${o.status}`;
             const statusText = o.status.charAt(0).toUpperCase() + o.status.slice(1);
             // Calculate total quantity for display
             const totalQuantity = o.items.reduce((sum, item) => sum + item.quantity, 0);

             return `
            <div class="completed-order-card ${o.status === 'cancelled' ? 'is-cancelled' : ''}">
                 <div>
                    <p><strong>Order #${o.id}</strong> — Buyer: ${o.buyer}</p>
                    <p>Total: ₱${formatCurrency(o.total)} (${totalQuantity} items)</p>
                 </div>
                 <div>
                    <p>Status: <span class="${statusClass}">${statusText}</span></p>
                    <p class="muted">Date: ${new Date(o.createdAt).toLocaleDateString()}</p>
                 </div>
            </div>`;
         }).join('');
    }
};