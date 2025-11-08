const API = {
    // Kini para sa JSON data
    async fetchJSON(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        };
        const config = { ...defaultOptions, ...options };
        if (config.body) {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            if (!response.ok) { // Check for non-2xx responses
                 // Attempt to parse error JSON, fallback to status text
                try {
                    const errorData = await response.json();
                    throw new Error(errorData.message || response.statusText);
                } catch (e) {
                     throw new Error(response.statusText);
                }
            }
             // Check if response is actually JSON before parsing
             const contentType = response.headers.get("content-type");
             if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
             } else {
                 // Handle non-JSON responses if necessary, or throw an error
                 console.warn("Received non-JSON response from API:", await response.text());
                 // Allow non-JSON for setup.php which returns text/plain
                 if (!url.endsWith('setup.php')) {
                     throw new Error("Received non-JSON response from API.");
                 } else {
                     return { success: true, message: "Setup script likely executed.", data: null }; // Assume success for setup script text output
                 }
             }
        } catch (error) {
            console.error('API Fetch JSON Error:', error);
            alert(`Error: ${error.message}`);
            return { success: false, message: error.message, data: null };
        }
    },

    // Function para sa file upload
    async fetchWithFile(url, formData) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
             if (!response.ok) {
                try {
                    const errorData = await response.json();
                    throw new Error(errorData.message || response.statusText);
                } catch (e) {
                     throw new Error(response.statusText);
                }
            }
              // Check if response is actually JSON before parsing
             const contentType = response.headers.get("content-type");
             if (contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
             } else {
                 console.warn("Received non-JSON response from file upload API:", await response.text());
                 throw new Error("Received non-JSON response from file upload API.");
             }
        } catch (error) {
            console.error('API File Upload Error:', error);
            alert(`Error: ${error.message}`);
            return { success: false, message: error.message, data: null };
        }
    },

    // --- Auth ---
    login: (username, password) => API.fetchJSON('api/login.php', { method: 'POST', body: { username, password } }),
    signup: (userData) => API.fetchJSON('api/signup.php', { method: 'POST', body: userData }),

    // --- Products ---
    getProducts: () => API.fetchJSON('api/products_get.php'),
    addProduct: (formData) => API.fetchWithFile('api/product_add.php', formData),
    deleteProduct: (id) => API.fetchJSON('api/product_delete.php', { method: 'POST', body: { id } }),

    // --- Seller ---
    updateSellerInfo: (sellerData) => API.fetchJSON('api/seller_info_update.php', { method: 'POST', body: sellerData }),
    getStores: () => API.fetchJSON('api/stores_get.php'),

    // --- Orders ---
    addSale: (orderData) => API.fetchJSON('api/sales_add.php', { method: 'POST', body: orderData }),
    getOrders: (role, userId) => API.fetchJSON(`api/orders_get.php?role=${role}&user_id=${userId}`),
    updateOrderStatus: (orderId, status) => API.fetchJSON('api/order_status_update.php', { method: 'POST', body: { orderId, status } }),
    cancelOrder: (orderId, buyerId) => API.fetchJSON('api/order_cancel.php', { method: 'POST', body: { orderId, buyerId } }),

    // --- CART API Calls ---
    getCart: (buyerId) => API.fetchJSON(`api/cart_get.php?buyer_id=${buyerId}`),
    addItemToCart: (itemData) => API.fetchJSON('api/cart_add_item.php', { method: 'POST', body: itemData }),
    updateCartItem: (itemData) => API.fetchJSON('api/cart_update_item.php', { method: 'POST', body: itemData }),
    removeCartItem: (itemData) => API.fetchJSON('api/cart_remove_item.php', { method: 'POST', body: itemData }),
    clearCart: (buyerId) => API.fetchJSON('api/cart_clear.php', { method: 'POST', body: { buyer_id: buyerId } })
};
