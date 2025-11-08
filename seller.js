const SellerManager = {
    init: () => {
        SellerManager.renderStoreInfo();
        SellerManager.renderSellerRiceList();
        SellerManager.renderOrders();
        document.getElementById("saveStoreBtn")?.addEventListener("click", SellerManager.saveStoreInfo);
        document.getElementById("addRiceBtn")?.addEventListener("click", SellerManager.addRice);
    },
    renderStoreInfo: () => {
         // Use new fields from ERD where available
        document.getElementById("storeName").value = currentUser.store_name || '';
        document.getElementById("storeOwner").value = currentUser.store_owner || ''; // Keep for now
        document.getElementById("contact").value = currentUser.contact || ''; // New field
        document.getElementById("address").value = currentUser.address || ''; // New field
        document.getElementById("fulfillmentOption").value = currentUser.fulfillment_option || ''; // New field
        // Old fields maybe can be removed later from HTML if contact/address replace them
        document.getElementById("storeContact").value = currentUser.store_contact || '';
        document.getElementById("storeEmail").value = currentUser.store_email || '';
        document.getElementById("storeDesc").value = currentUser.store_desc || '';
    },
    saveStoreInfo: async () => {
        const updatedInfo = {
            id: currentUser.id,
            storeName: document.getElementById("storeName").value,
            storeOwner: document.getElementById("storeOwner").value,
            contact: document.getElementById("contact").value, // New
            address: document.getElementById("address").value, // New
            storeContact: document.getElementById("storeContact").value, // Old
            storeEmail: document.getElementById("storeEmail").value, // Old
            storeDesc: document.getElementById("storeDesc").value, // Old
            fulfillmentOption: document.getElementById("fulfillmentOption").value // New
        };
        const response = await API.updateSellerInfo(updatedInfo); // fetchJSON gihapon
        if (response.success) {
            // Update local currentUser object para updated ang info without refresh
            Object.assign(currentUser, {
                store_name: updatedInfo.storeName, store_owner: updatedInfo.storeOwner,
                contact: updatedInfo.contact, address: updatedInfo.address,
                store_contact: updatedInfo.storeContact, store_email: updatedInfo.storeEmail,
                store_desc: updatedInfo.storeDesc, fulfillment_option: updatedInfo.fulfillmentOption
            });
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            alert("Store info saved!");
        }
    },
    addRice: async () => {
        const riceImageInput = document.getElementById("riceImage");
        const riceImageFile = riceImageInput.files[0];

        const formData = new FormData();
        formData.append('seller_id', currentUser.id);
        formData.append('name', document.getElementById("newRiceName").value);
        formData.append('price', document.getElementById("newRicePrice").value);
        formData.append('perKilo', document.getElementById("newRicePerKilo").value);
        formData.append('desc', document.getElementById("newRiceDesc").value);
        formData.append('option', document.getElementById("riceOption").value);
        // Idugang ang bag-ong fields
        formData.append('type', document.getElementById("newRiceType").value);
        formData.append('packaging', document.getElementById("newRicePackaging").value);
        formData.append('stock_quantity', document.getElementById("newRiceStock").value);


        if (riceImageFile) {
            formData.append('riceImage', riceImageFile);
        }
        
        if (!formData.get('name') || !formData.get('price')) {
            return alert("Rice name and price are required.");
        }

        const response = await API.addProduct(formData); // Uses fetchWithFile
        
        if (response.success) {
            alert("New rice added!");
            document.getElementById('addRiceForm').reset();
            SellerManager.renderSellerRiceList();
            if (typeof AdminManager !== 'undefined' && adminPage.style.display === 'block') {
                AdminManager.renderProducts();
            }
        }
    },
    renderSellerRiceList: async () => {
        const listDiv = document.getElementById("sellerRiceList");
        if (!listDiv) return;
        const response = await API.getProducts(); // fetchJSON gihapon
        if (!response.success || !currentUser) return;
        const sellerProducts = response.data.filter(p => p.seller_id == currentUser.id);
        listDiv.innerHTML = sellerProducts.length === 0 ? "<p>You have not added any rice products.</p>" : sellerProducts.map(r => `
            <div class="order">
                <div style="display:flex; align-items:center; gap:10px;">
                    <!-- Gamiton ang image_url -->
                    <img src="${r.image_url || 'https://via.placeholder.com/50'}" style="width:50px; height:50px; object-fit:cover; border-radius:4px;" alt="${r.name}">
                    <div>
                        <div><strong>${r.name}</strong> (${r.type || 'N/A'}) — ₱${formatCurrency(r.price)} / ${r.packaging || 'N/A'}</div>
                        <div class="muted">Option: ${r.option} | Stock: ${r.stock_quantity ?? 'N/A'}</div>
                    </div>
                </div>
            </div>`).join('');
    },
    renderOrders: async () => {
        const pendingDiv = document.getElementById("pendingOrders");
        const completedDiv = document.getElementById("completedOrders");
        if (!pendingDiv || !completedDiv || !currentUser) return;
        
        const response = await API.getOrders('seller', currentUser.id); // fetchJSON gihapon
        if (!response.success) return;

        const pendingOrders = response.data.filter(o => o.status === 'pending');
        const completedOrders = response.data.filter(o => o.status === 'completed' || o.status === 'cancelled'); // Apil na ang cancelled diri

        pendingDiv.innerHTML = pendingOrders.length === 0 ? "<p>No pending orders.</p>" : pendingOrders.map(o => {
            // Filter items for this specific seller within the order
             const itemsForThisSeller = o.items.filter(i => i.sellerInfo?.username === currentUser.username);
             // Calculate total for this seller only for this order
             const sellerTotal = itemsForThisSeller.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

             // Only display the order if there are items for this seller
             if (itemsForThisSeller.length === 0) return '';

            return `
            <div class="order">
                <div><strong>Order #${o.id}</strong> — Buyer: ${o.buyer}</div>
                <div class="muted">Address: ${o.address.city}, Purok ${o.address.purok}</div>
                 <div class="muted">Type: ${o.fulfillmentType || 'N/A'} | Method: ${o.paymentMethod || 'N/A'}</div>
                 <div style="font-weight: bold; margin-top: 5px;">Seller Total: ₱${formatCurrency(sellerTotal)}</div>
                <div style="margin-top: 6px; padding-left: 10px; border-left: 2px solid #eee;">
                    ${itemsForThisSeller.map(it => `<div>${it.quantity} x ${it.name} (${it.productPackaging || 'N/A'}) — ₱${formatCurrency(it.subtotal)}</div>`).join("")}
                </div>
                <div class="order-buttons-row">
                    <button class="primary btn-done" data-order-id="${o.id}">Mark as Done</button>
                </div>
            </div>`;
        }).join('');
        
        completedDiv.innerHTML = completedOrders.length === 0 ? "<p>No completed/cancelled orders.</p>" : completedOrders.map(o => {
             const itemsForThisSeller = o.items.filter(i => i.sellerInfo?.username === currentUser.username);
             if (itemsForThisSeller.length === 0 && o.status !== 'cancelled') return ''; // Don't show if no items unless cancelled

              const statusClass = `order-status-${o.status}`;
              const statusText = o.status.charAt(0).toUpperCase() + o.status.slice(1);

             return `
            <div class="completed-order-card ${o.status === 'cancelled' ? 'is-cancelled' : ''}">
                <p><strong>Order #${o.id}</strong> — Buyer: ${o.buyer} — Status: <span class="${statusClass}">${statusText}</span></p>
                 <p class="muted">Date: ${new Date(o.createdAt).toLocaleDateString()}</p>
            </div>`;
         }).join('');


        document.querySelectorAll('.btn-done').forEach(btn => btn.addEventListener('click', async e => {
            const orderId = e.target.dataset.orderId;
            if (confirm(`Mark Order #${orderId} as completed? This cannot be undone.`)) {
                const res = await API.updateOrderStatus(orderId, 'completed'); // fetchJSON gihapon
                if (res.success) {
                    alert("Order marked as completed!");
                    SellerManager.renderOrders();
                } else {
                     alert("Failed to update status: " + res.message); // Show error
                }
            }
        }));
    }
};