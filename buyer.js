const BuyerManager = {
    // Variable para temporaryo i-store ang cart data gikan sa API
    currentCartItems: [],
    
    // Function para i-load ang cart gikan sa database
    loadCart: async () => {
        if (!currentUser || currentUser.role !== 'buyer') {
             BuyerManager.currentCartItems = []; // Ensure cart is empty if not logged in
             BuyerManager.updateCartBtn();
            return;
        }
        console.log("Loading cart for buyer ID:", currentUser.id); // Debug log
        const response = await API.getCart(currentUser.id);
        console.log("Cart Response:", response); // Debug log
        if (response.success) {
            BuyerManager.currentCartItems = response.data || [];
        } else {
            console.error("Failed to load cart:", response.message);
            BuyerManager.currentCartItems = []; // Reset on error
        }
        BuyerManager.updateCartBtn();
    },

    addToCart: async (productId) => {
        if (!currentUser || currentUser.role !== 'buyer') {
            return alert("Please log in as a buyer to add items.");
        }
        
        // Find product details from the globally loaded products list
        const productToAdd = products.find(p => p.id == productId);
        if (!productToAdd) return alert("Product details not found.");

        // Check stock if available
         if (productToAdd.stock_quantity !== null && productToAdd.stock_quantity <= 0) {
             return alert(`Sorry, ${productToAdd.name} is currently out of stock.`);
         }

         // Check current quantity in cart before adding
         const currentItemInCart = BuyerManager.currentCartItems.find(item => item.product_id == productId);
         const currentQuantityInCart = currentItemInCart ? currentItemInCart.quantity : 0;

         if (productToAdd.stock_quantity !== null && (currentQuantityInCart + 1) > productToAdd.stock_quantity) {
              return alert(`Cannot add more ${productToAdd.name}. Maximum stock (${productToAdd.stock_quantity}) reached in cart.`);
         }


        console.log("Adding product ID to cart:", productId); // Debug log
        const response = await API.addItemToCart({
            buyer_id: currentUser.id,
            product_id: productId,
            quantity: 1 // Add one at a time
        });
        if (response.success) {
            alert(`${productToAdd.name} added to cart!`);
            await BuyerManager.loadCart(); // Reload cart from DB after adding
        } else {
            alert(`Failed to add item: ${response.message}`);
        }
    },

    updateCartBtn: () => {
        const btn = document.getElementById("viewCartBtn");
        if (!btn) return;
        // Total items is the sum of quantities from the loaded cart
        const totalItems = BuyerManager.currentCartItems.reduce((sum, item) => sum + item.quantity, 0);
        btn.style.display = totalItems > 0 ? "inline-block" : "none";
        btn.textContent = `View Cart (${totalItems})`;
    },

    viewCart: () => {
        // Gamiton na nato ang currentCartItems
        if (BuyerManager.currentCartItems.length === 0) {
             // Ensure modal is shown even if cart is empty
            showModal("Cart", "<p>Your cart is empty.</p>");
            return;
        }

        const isAllPickup = BuyerManager.currentCartItems.every(item => item.option === 'pickup');
        const total = BuyerManager.currentCartItems.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);

        const addressSectionHTML = isAllPickup
            ? `<div class="cart-pickup-note"><p><strong>Note:</strong> All items are for pickup. Address is not required.</p></div>`
            : `<div class="cart-address">
                   <input type="text" id="cartCity" placeholder="City (required for delivery)" />
                   <input type="text" id="cartPurok" placeholder="Purok (required for delivery)" />
               </div>`;

        const content = `
            <div>
                ${addressSectionHTML}
                <div class="cart-products">
                    ${BuyerManager.currentCartItems.map((item) => `
                        <div class="cart-product-row" data-cartitemid="${item.cart_item_id}">
                            <div>
                                <div style="font-weight:600">${item.name}</div>
                                <div class="muted">₱${formatCurrency(item.price)} x ${item.quantity} = ₱${formatCurrency(item.price * item.quantity)}</div>
                                <div class="muted">Option: ${item.option}</div>
                                 ${item.stock_quantity !== null ? `<div class="muted">Stock: ${item.stock_quantity}</div>` : ''}
                            </div>
                            <div class="cart-item-controls">
                                <button class="ghost cart-qty-btn" data-cartitemid="${item.cart_item_id}" data-change="-1" data-stock="${item.stock_quantity ?? -1}">-</button>
                                <span>${item.quantity}</span>
                                <button class="ghost cart-qty-btn" data-cartitemid="${item.cart_item_id}" data-change="1" data-stock="${item.stock_quantity ?? -1}">+</button>
                                <button class="ghost removeCartBtn" data-cartitemid="${item.cart_item_id}" style="margin-left: 10px;">×</button>
                            </div>
                        </div>`).join("")}
                </div>
                <div class="cart-summary">
                    <div style="font-weight:700">Total: ₱${formatCurrency(total)}</div>
                    <button id="buyNowBtn" class="buy-now">
                        ${isAllPickup ? 'Confirm Pickup Order' : 'Place Delivery Order'}
                    </button>
                </div>
            </div>
        `;
        showModal("Cart", content); // Always show modal

        // --- Event Listeners para sa bag-ong cart ---
        document.querySelectorAll(".removeCartBtn").forEach(btn => {
            btn.addEventListener("click", async () => {
                const cartItemId = btn.dataset.cartitemid;
                await BuyerManager.removeItem(cartItemId);
            });
        });
        document.querySelectorAll(".cart-qty-btn").forEach(btn => {
            btn.addEventListener("click", async () => {
                const cartItemId = btn.dataset.cartitemid;
                const change = parseInt(btn.dataset.change);
                const stock = parseInt(btn.dataset.stock); // Get stock limit

                // Find current quantity from the displayed cart items
                const currentItem = BuyerManager.currentCartItems.find(item => item.cart_item_id == cartItemId);
                if (!currentItem) return;

                const currentQuantity = currentItem.quantity;
                const newQuantity = currentQuantity + change;

                 // Check against stock limit if increasing quantity
                 if (change > 0 && stock !== -1 && newQuantity > stock) {
                     alert(`Cannot add more. Available stock is ${stock}.`);
                     return; // Do not update quantity
                 }


                // Quantity must be at least 0 (will trigger removal in update)
                 if (newQuantity >= 0) {
                    await BuyerManager.updateItemQuantity(cartItemId, newQuantity);
                 }
            });
        });
        document.getElementById("buyNowBtn").addEventListener("click", BuyerManager.processSale);
    },

    // Bag-ong helper functions para tawagon ang API
    updateItemQuantity: async (cartItemId, newQuantity) => {
        if (!currentUser) return;
        console.log("Updating Cart Item ID:", cartItemId, "New Qty:", newQuantity); // Debug log
        const response = await API.updateCartItem({
            buyer_id: currentUser.id,
            cart_item_id: cartItemId,
            quantity: newQuantity
        });
        if (response.success) {
            await BuyerManager.loadCart(); // Reload cart data
            BuyerManager.viewCart();     // Re-render the modal with updated data
        } else {
            alert(`Failed to update quantity: ${response.message}`);
        }
    },
    removeItem: async (cartItemId) => {
        if (!currentUser) return;
        console.log("Removing Cart Item ID:", cartItemId); // Debug log
        if (confirm("Remove this item from your cart?")) {
            const response = await API.removeCartItem({
                buyer_id: currentUser.id,
                cart_item_id: cartItemId
            });
            if (response.success) {
                await BuyerManager.loadCart(); // Reload cart data
                BuyerManager.viewCart();     // Re-render the modal
            } else {
                alert(`Failed to remove item: ${response.message}`);
            }
        }
    },

    processSale: async () => {
         // Reload cart items right before processing to ensure consistency
         await BuyerManager.loadCart();
         if (BuyerManager.currentCartItems.length === 0) {
             return alert("Your cart is empty. Please add items before placing an order.");
         }


        const isAllPickup = BuyerManager.currentCartItems.every(item => item.option === 'pickup');
        let city = '';
        let purok = '';

        if (!isAllPickup) {
            city = document.getElementById("cartCity")?.value.trim() ?? ''; // Add safety check
            purok = document.getElementById("cartPurok")?.value.trim() ?? ''; // Add safety check
            if (!city || !purok) {
                return alert("Please enter City and Purok for delivery items.");
            }
        }

        if (!currentUser || currentUser.role !== "buyer") {
            return alert("You must be logged in as a buyer.");
        }

        // Check stock levels before proceeding
        for (const item of BuyerManager.currentCartItems) {
            if (item.stock_quantity !== null && item.quantity > item.stock_quantity) {
                 return alert(`Order cannot be placed. ${item.name} only has ${item.stock_quantity} stock available, but you have ${item.quantity} in your cart.`);
            }
        }


        const orderData = {
            buyerId: currentUser.id,
            address: { city: city, purok: purok },
            // I-pasa ang items gikan sa currentCartItems
            items: BuyerManager.currentCartItems.map(ci => ({
                id: ci.product_id, // Ensure correct ID is sent
                name: ci.name,
                price: ci.price,
                option: ci.option,
                quantity: ci.quantity
            })),
            total: BuyerManager.currentCartItems.reduce((s, it) => s + (Number(it.price) * it.quantity), 0),
            fulfillment_type: isAllPickup ? 'pickup' : 'deliver'
        };

        const response = await API.addSale(orderData);

        if (response.success) {
            alert("Order placed successfully!");
             // Clear the cart in the database after successful order
             await API.clearCart(currentUser.id);
            await BuyerManager.loadCart(); // Reload empty cart
            modalBack.style.display = "none";
            BuyerManager.renderOrders(); // I-refresh ang order list
        } else {
            alert(response.message || "Failed to place order.");
        }
    },
    renderOrders: async () => {
        const pendingDiv = document.getElementById("buyerPendingOrders");
        const completedDiv = document.getElementById("buyerCompletedOrders");
        if (!pendingDiv || !completedDiv || !currentUser) return;

        const response = await API.getOrders('buyer', currentUser.id);
        if (!response.success) return;

        const pendingOrders = response.data.filter(o => o.status === 'pending');
        const historyOrders = response.data.filter(o => o.status === 'completed' || o.status === 'cancelled');

        pendingDiv.innerHTML = pendingOrders.length === 0 ? "<p>No pending orders.</p>" : pendingOrders.map(o => `
            <div class="pending-order-card">
                 <div class="pending-order-details">
                    <p><strong>Order #${o.id}</strong> — Total: ₱${formatCurrency(o.total)}</p>
                    <p>Status: <span class="order-status-pending">Pending</span></p>
                     <p class="muted">Type: ${o.fulfillmentType || 'N/A'} | Method: ${o.paymentMethod || 'N/A'}</p>
                    <p class="muted" style="font-size: 14px;">Items: ${o.items.map(i => `${i.quantity}x ${i.name}`).join(', ')}</p>
                 </div>
                 <div class="pending-order-actions">
                    <button class="ghost danger btn-cancel-order" data-order-id="${o.id}">Cancel</button>
                 </div>
            </div>
        `).join('');

        completedDiv.innerHTML = historyOrders.length === 0 ? "<p>No orders in history.</p>" : historyOrders.map(o => {
            const statusClass = `order-status-${o.status}`;
            const statusText = o.status.charAt(0).toUpperCase() + o.status.slice(1);
            return `
            <div class="completed-order-card ${o.status === 'cancelled' ? 'is-cancelled' : ''}">
                 <div>
                    <p><strong>Order #${o.id}</strong> — ₱${formatCurrency(o.total)}</p>
                     <p class="muted">Items: ${o.items.map(i => `${i.quantity}x ${i.name}`).join(', ')}</p>
                 </div>
                 <div>
                    <p>Status: <span class="${statusClass}">${statusText}</span></p>
                    <p class="muted">${new Date(o.createdAt).toLocaleDateString()}</p>
                 </div>
            </div>`;
        }).join('');

        document.querySelectorAll('.btn-cancel-order').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const orderId = e.target.dataset.orderId;
                if (confirm(`Are you sure you want to cancel Order #${orderId}? This cannot be undone.`)) {
                    const response = await API.cancelOrder(orderId, currentUser.id);
                    if (response.success) {
                        alert(response.message);
                        BuyerManager.renderOrders();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });
    }
}; // End of BuyerManager