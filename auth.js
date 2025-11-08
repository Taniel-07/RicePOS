const AuthManager = {
    // Helper function para sa show/hide password
    addPasswordToggleListener: (passwordInputId) => {
        const passwordInput = document.getElementById(passwordInputId);
        // Ang parent aning input kay ang .password-wrapper
        // More robust selector in case structure changes slightly
        const togglePassword = passwordInput?.parentElement?.querySelector('.toggle-password'); 

        if (passwordInput && togglePassword) {
            togglePassword.addEventListener('click', () => {
                // Usbon ang type sa input gikan sa 'password' paingon sa 'text' ug balik
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Usbon ang icon sa mata
                togglePassword.textContent = type === 'password' ? '👁️' : '🙈';
            });
        } else {
             console.warn("Could not find password input or toggle for ID:", passwordInputId);
        }
    },

    showLoginModal: (prefillUsername = "", prefillPassword = "") => {
        const content = `
            <div class="modal-content-container">
                <div class="auth-header">
                    <div class="logo">RP</div>
                    <h3>Welcome Back!</h3>
                    <p>Please enter your details to log in.</p>
                </div>
                <div class="auth-container">
                    <input type="text" id="loginUsername" placeholder="Username or Email" value="${prefillUsername}" />
                    <div class="password-wrapper">
                        <input type="password" id="loginPassword" placeholder="Password" value="${prefillPassword}" />
                        <span class="toggle-password">👁️</span>
                    </div>
                    <button class="primary" id="doLogin">Login</button>
                </div>
            </div>
        `;
        // Use showModal utility
        showModal("", content); // Pass empty string for title to hide default modal title

        document.getElementById("doLogin").addEventListener("click", AuthManager.login);
        AuthManager.addPasswordToggleListener('loginPassword');
    },

    login: async () => {
        const username = document.getElementById("loginUsername").value;
        const password = document.getElementById("loginPassword").value;
        
        const response = await API.login(username, password);

        console.log("Server Response:", response); // Para sa debugging

        if (response.success) {
            currentUser = response.data;
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            modalBack.style.display = "none";
            modalTitle.style.display = 'block'; // Ipakita pag-usab para sa ubang modal
            switchRole(currentUser.role); // Switch role will load cart if buyer
        } else {
            alert(response.message || "Invalid credentials.");
        }
    },

    showSignupModal: () => {
        const content = `
            <div class="modal-content-container">
                <div class="auth-header">
                    <div class="logo">RP</div>
                    <h3>Create an Account</h3>
                    <p>Join us to start buying or selling rice.</p>
                </div>
                <div class="auth-container">
                    <input type="text" id="signupUsername" placeholder="Username" />
                    <input type="email" id="signupEmail" placeholder="Email" />
                    <div class="password-wrapper">
                        <input type="password" id="signupPassword" placeholder="Password" />
                        <span class="toggle-password">👁️</span>
                    </div>
                    <div class="password-wrapper">
                        <input type="password" id="signupConfirmPassword" placeholder="Confirm Password" />
                        <span class="toggle-password">👁️</span>
                    </div>
                    <select id="signupRole"><option value="buyer">I want to Buy</option><option value="seller">I want to Sell</option></select>
                    <button class="primary" id="doSignup">Sign Up</button>
                </div>
            </div>
        `;
        // Use showModal utility
        showModal("", content); // Pass empty string for title to hide default modal title

        document.getElementById("doSignup").addEventListener("click", AuthManager.signup);
        AuthManager.addPasswordToggleListener('signupPassword');
        AuthManager.addPasswordToggleListener('signupConfirmPassword');
    },

    signup: async () => {
        const username = document.getElementById("signupUsername").value.trim();
        const email = document.getElementById("signupEmail").value.trim();
        const password = document.getElementById("signupPassword").value;
        const confirmPassword = document.getElementById("signupConfirmPassword").value;
        const role = document.getElementById("signupRole").value;

        if (!username || !email || !password || !confirmPassword) {
            return alert("Please fill in all fields.");
        }
        if (password !== confirmPassword) {
            return alert("Passwords do not match. Please try again.");
        }
        
        const response = await API.signup({ username, email, password, role });
        
        if (response.success) {
            alert("Account created! Please log in.");
            // Don't auto-show login modal, let user click login button
             modalBack.style.display = 'none';
             modalTitle.style.display = 'block'; // Reset modal title display
        } else {
            alert(response.message || "Signup failed.");
        }
    },

    logout: () => {
        currentUser = null;
        sessionStorage.removeItem('currentUser');
        
        // I-reset ang cart data sa BuyerManager kung naa siya
        if (typeof BuyerManager !== 'undefined') {
            BuyerManager.currentCartItems = []; // Reset ang local cache sa cart
            BuyerManager.updateCartBtn();
        }
        switchRole("front");
        // No need to reload, switchRole handles page display
        // window.location.reload(); 
    }
};

// Ensure closeModal listener resets the modal title display properly
// This might be better placed in main.js's DOMContentLoaded if not already there
// Adding it here for completeness based on previous context.
if (typeof closeModal !== 'undefined' && closeModal) {
     closeModal.addEventListener("click", () => {
         // Ensure modalTitle exists and reset its display
         const modalTitleElement = document.getElementById("modalTitle");
         if (modalTitleElement) {
             modalTitleElement.style.display = 'block';
         }
     });
}
