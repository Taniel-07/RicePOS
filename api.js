// RPOS/api.js

const API = {
    // Kini para sa JSON data
    async fetchJSON(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        };

        const config = { ...defaultOptions, ...options };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            // I-check kung ang HTTP status kay error (e.g., 400, 500)
            if (!response.ok) {
                // Kon sayop ang status, ibalik ang error gikan sa server
                return { success: false, message: data.message || `HTTP Error: ${response.status}` };
            }

            return data; // Ang PHP backend dapat mo-return na sa { success: true, data: ... }
        } catch (error) {
            console.error('API Error:', error);
            // I-handle ang network errors or JSON parsing errors
            return { success: false, message: 'Network or server error occurred. Check if your XAMPP/WAMP is running.' };
        }
    },

    // Function para sa file upload
    async fetchWithFile(url, formData) {
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('File Upload Error:', error);
            return { success: false, message: error.message || 'Server did not return valid JSON after file upload.' };
        }
    },

    // --- Auth ---
    login: (username, password) => API.fetchJSON('api/login.php', { method: 'POST', body: { username, password } }),
    signup: (userData) => API.fetchJSON('api/signup.php', { method: 'POST', body: userData }),

    // --- Users/Admin Management ---
    deleteUser: (id) => API.fetchJSON('api/user_delete.php', { method: 'POST', body: { id } }),

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