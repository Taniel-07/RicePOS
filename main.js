// ==================== GLOBAL STATE & DOM ====================
let products = []; // Cache for product details
// let cart = []; // Dili na ni gamiton, naa na sa BuyerManager.currentCartItems
let currentUser = JSON.parse(sessionStorage.getItem("currentUser")) || null;

const frontPage = document.getElementById("frontPage");
const buyerDashboard = document.getElementById("buyerDashboard");
const sellerPage = document.getElementById("sellerPage");
const adminPage = document.getElementById("adminPage");

const modalBack = document.getElementById("modalBack");
const modalTitle = document.getElementById("modalTitle");
const modalBody = document.getElementById("modalBody");
const closeModal = document.getElementById("closeModal");

// ==================== UTILS ====================
function formatCurrency(n) {
    if (isNaN(n) || n === null) return "0.00"; // Added null check
    return parseFloat(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function showModal(title, content) {
    // I-handle kung ang title kay blangko o dili string
     if (typeof title === 'string' && title.trim() !== '') {
        modalTitle.textContent = title;
        modalTitle.style.display = 'block'; // Make sure title element is visible if text exists
     } else {
         modalTitle.style.display = 'none'; // Hide title element if title is empty or not provided
     }
    modalBody.innerHTML = content;
    modalBack.style.display = "flex";
}
closeModal.addEventListener("click", () => {
     modalBack.style.display = "none";
     modalTitle.style.display = 'block'; // Ensure title is visible for next standard modal
});

// ==================== ROLE SWITCHING ====================
async function switchRole(role) { // Make async to await cart loading
    frontPage.style.display = "none";
    buyerDashboard.style.display = "none";
    sellerPage.style.display = "none";
    adminPage.style.display = "none";

    // Limpyuhan daan ang tanang username display
    document.querySelectorAll('.user-display').forEach(el => el.textContent = '');

    if (role === "buyer") {
        buyerDashboard.style.display = "block";
        await ProductManager.renderProducts('list'); // Await para mauna ang products
        await BuyerManager.loadCart(); // Tawagon para ma-load ang cart gikan sa DB
        BuyerManager.renderOrders();
        if (currentUser) {
            document.getElementById('buyerUsername').textContent = `👤 ${currentUser.username}`;
        }
    } else if (role === "seller") {
        sellerPage.style.display = "block";
        await ProductManager.renderProducts('list'); // Load products needed for seller view too? (If viewProduct modal is used)
        SellerManager.init(); // Contains async calls inside
        if (currentUser) {
            document.getElementById('sellerUsername').textContent = `👤 ${currentUser.username}`;
        }
    } else if (role === "admin") {
        adminPage.style.display = "block";
        await ProductManager.renderProducts('list'); // Load products needed for admin view too
        AdminManager.init(); // Contains async calls inside
        if (currentUser) {
            document.getElementById('adminUsername').textContent = `👤 ${currentUser.username}`;
        }
    } else {
        frontPage.style.display = "block";
    }
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', async () => { // Make async
    // Load products once on initial load, they might be needed regardless of role
    // await ProductManager.renderProducts('list'); // Optionally load products here if needed on front page too

    if (currentUser) {
       await switchRole(currentUser.role); // Await role switching which includes loading data
    } else {
       await switchRole("front");
    }

    // Sakto na nga event listeners
    document.getElementById("loginBtn")?.addEventListener("click", () => AuthManager.showLoginModal());
    document.getElementById("signupBtn")?.addEventListener("click", AuthManager.showSignupModal);
    document.getElementById("logoutBuyerBtn")?.addEventListener("click", AuthManager.logout);
    document.getElementById("logoutSellerBtn")?.addEventListener("click", AuthManager.logout);
    document.getElementById("logoutAdminBtn")?.addEventListener("click", AuthManager.logout);

    // This listener should only trigger viewCart modal, actual data loading happens on role switch
    document.getElementById("viewCartBtn")?.addEventListener("click", () => {
         if (currentUser && currentUser.role === 'buyer') {
             BuyerManager.viewCart(); // Render modal using BuyerManager.currentCartItems
         }
     });
});
