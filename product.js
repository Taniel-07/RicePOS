const ProductManager = {
    renderProducts: async (containerId) => {
        const listDiv = document.getElementById(containerId);
        if (!listDiv) return;
        listDiv.innerHTML = "<p>Loading products...</p>";
        const response = await API.getProducts(); // fetchJSON gihapon
        if (!response.success) {
            listDiv.innerHTML = `<p>Error: ${response.message}</p>`;
            return;
        }
        products = response.data; // Update global products array
        listDiv.innerHTML = "";
        if (products.length === 0) {
            listDiv.innerHTML = "<p>No products available yet.</p>";
            return;
        }
        products.forEach((p) => {
            const div = document.createElement("div");
            div.className = "card";
            // Gamiton ang image_url gikan sa response
            // Ipakita ang stock quantity kung naa
            const stockInfo = p.stock_quantity !== null ? `<div class="muted">Stock: ${p.stock_quantity}</div>` : '';
            div.innerHTML = `
                <img src="${p.image_url || "https://via.placeholder.com/150"}" alt="${p.name}" style="height:150px; object-fit:cover;">
                <div class="kilo">${p.name}</div>
                <div class="muted">Type: ${p.type || 'N/A'}</div>
                <div class="muted">Option: ${p.option}</div>
                <div class="muted">₱${formatCurrency(p.price)} per ${p.packaging || '25kg'}</div>
                 ${stockInfo}
                <div class="muted">Seller: ${p.seller?.storeName || 'N/A'}</div>
                <div style="display:flex;gap:8px;margin-top:8px">
                    <button class="primary viewBtn" data-id="${p.id}">View</button>
                    ${currentUser?.role === 'buyer' ? `<button class="ghost addCartBtn" data-id="${p.id}">Add to Cart</button>` : ''}
                </div>
            `;
            listDiv.appendChild(div);
        });
        // Re-attach event listeners
        document.querySelectorAll(".viewBtn").forEach(btn =>
            btn.addEventListener("click", e => ProductManager.viewProduct(parseInt(e.target.dataset.id)))
        );
        document.querySelectorAll(".addCartBtn").forEach(btn =>
            btn.addEventListener("click", e => BuyerManager.addToCart(parseInt(e.target.dataset.id)))
        );
    },

    viewProduct: (id) => {
        const p = products.find((x) => x.id == id); // Use == for potential type difference
        if (!p) return;
        // Gamiton ang image_url
        // Ipakita ang bag-ong fields
         const stockInfo = p.stock_quantity !== null ? `<p><strong>Stock:</strong> ${p.stock_quantity}</p>` : '';
        const content = `
            <div style="display:flex;gap:16px;align-items:flex-start; flex-wrap: wrap;">
                <img src="${p.image_url || 'https://via.placeholder.com/100'}" style="border-radius:8px;height:100px;object-fit:cover;" alt="${p.name}">
                <div>
                    <p><strong>Price:</strong> ₱${formatCurrency(p.price)} / ${p.packaging || '25kg'}</p>
                     ${p.price_per_kilo ? `<p><strong>Per Kilo:</strong> ₱${formatCurrency(p.price_per_kilo)}</p>` : ''}
                    <p><strong>Type:</strong> ${p.type || 'N/A'}</p>
                    <p><strong>Packaging:</strong> ${p.packaging || 'N/A'}</p>
                    <p><strong>Option:</strong> ${p.option}</p>
                    ${stockInfo}
                    <p><strong>Seller:</strong> ${p.seller?.storeName || "N/A"}</p>
                    <p><strong>Contact:</strong> ${p.seller?.contact || p.seller?.storeContact || "N/A"}</p>
                    <p><strong>Address:</strong> ${p.seller?.address || "N/A"}</p>
                    <p><strong>Description:</strong> ${p.description || p.seller?.storeDesc || "—"}</p>
                </div>
            </div>`;
        showModal(p.name, content);
    }
};