// js/cart.js - Clean client-side cart & variant modal interactions

function showNotification(msg, isSuccess = true) {
    let toast = document.getElementById('shop-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'shop-toast';
        toast.style.cssText = `
            position: fixed;
            bottom: 25px;
            right: 25px;
            padding: 14px 24px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.95);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            z-index: 10000;
            transition: opacity 0.3s ease, transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,0.1);
        `;
        document.body.appendChild(toast);
    }
    toast.innerHTML = (isSuccess ? '✅ ' : '❌ ') + msg;
    toast.style.borderColor = isSuccess ? '#10b981' : '#ef4444';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 3200);
}

// Global Auth Prompt Modal for guests attempting to order or interact
window.showAuthPromptModal = function(productName = '') {
    let authModal = document.getElementById('auth-prompt-modal');
    if (!authModal) {
        authModal = document.createElement('div');
        authModal.id = 'auth-prompt-modal';
        authModal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10005;
        `;
        document.body.appendChild(authModal);
    }

    authModal.innerHTML = `
        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.18); border-radius: 24px; padding: 36px 28px; width: 90%; max-width: 440px; text-align: center; color: #fff; font-family: 'Poppins', sans-serif; box-shadow: 0 25px 60px rgba(0,0,0,0.8); position: relative;">
            <button onclick="document.getElementById('auth-prompt-modal').style.display='none'" 
                    style="position: absolute; top: 16px; right: 18px; background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
            
            <div style="font-size: 52px; margin-bottom: 12px;">🪵 🛍️</div>
            <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 22px; font-weight: 600; color: #f8fafc;">
                Ready to Place Your Order?
            </h3>
            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 26px;">
                ${productName ? `You selected <strong>${productName}</strong>.<br>` : ''}
                Please create a new account to checkout, or sign in if you already have an account.
            </p>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <a href="register.php" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; text-decoration: none; padding: 14px 20px; border-radius: 30px; font-weight: 600; font-size: 15px; box-shadow: 0 8px 20px rgba(16,185,129,0.35); transition: 0.2s; display: block;">
                    ✨ Create New Account
                </a>
                <a href="login.php" style="background: rgba(255,255,255,0.08); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.25); text-decoration: none; padding: 13px 20px; border-radius: 30px; font-weight: 500; font-size: 15px; transition: 0.2s; display: block;">
                    🔑 Login to Account
                </a>
            </div>

            <button onclick="document.getElementById('auth-prompt-modal').style.display='none'" 
                    style="background: none; border: none; color: #64748b; font-size: 13px; cursor: pointer; text-decoration: underline;">
                ← Continue Browsing Catalog
            </button>
        </div>
    `;
    authModal.style.display = 'flex';
};

// Global modal trigger for variant selection
window.promptProductOptions = function(productId, productName, basePrice, hasSizes, woodType) {
    // If guest user on single-option product, prompt auth directly
    if (hasSizes == 0 && woodType !== 'both') {
        if (window.IS_USER_LOGGED_IN === false) {
            window.showAuthPromptModal(productName);
            return;
        }
        window.sendAddToCart(productId, 1, woodType === 'none' ? null : woodType, null);
        return;
    }

    let modal = document.getElementById('variant-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'variant-modal';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        document.body.appendChild(modal);
    }

    let woodHtml = '';
    if (woodType === 'both') {
        woodHtml = `
            <div style="margin-bottom: 20px; text-align: left;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px; color:#38bdf8;">
                    🪵 Select Wood Material:
                </label>
                <div style="display:flex; gap:12px;">
                    <label style="flex:1; cursor:pointer; background:rgba(255,255,255,0.08); padding:10px; border-radius:10px; border:1px solid rgba(255,255,255,0.2); text-align:center;">
                        <input type="radio" name="modal_wood" value="Teakwood" checked style="margin-right:6px;"> Teakwood
                    </label>
                    <label style="flex:1; cursor:pointer; background:rgba(255,255,255,0.08); padding:10px; border-radius:10px; border:1px solid rgba(255,255,255,0.2); text-align:center;">
                        <input type="radio" name="modal_wood" value="Rosewood" style="margin-right:6px;"> Rosewood
                    </label>
                </div>
            </div>
        `;
    }

    let sizeHtml = '';
    if (hasSizes == 1) {
        let p5x6 = Number(basePrice).toLocaleString('en-IN');
        let p6x6 = (Number(basePrice) + 8000).toLocaleString('en-IN');
        sizeHtml = `
            <div style="margin-bottom: 25px; text-align: left;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px; color:#38bdf8;">
                    📏 Select Bed Dimensions:
                </label>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label style="cursor:pointer; background:rgba(255,255,255,0.08); padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.2); display:flex; justify-content:space-between; align-items:center;">
                        <span><input type="radio" name="modal_size" value="5 × 6" checked style="margin-right:8px;"> 5 × 6 (Standard)</span>
                        <strong style="color:#34d399;">₹ ${p5x6}</strong>
                    </label>
                    <label style="cursor:pointer; background:rgba(255,255,255,0.08); padding:12px; border-radius:10px; border:1px solid rgba(255,255,255,0.2); display:flex; justify-content:space-between; align-items:center;">
                        <span><input type="radio" name="modal_size" value="6 × 6" style="margin-right:8px;"> 6 × 6 (King Size)</span>
                        <strong style="color:#fbbf24;">₹ ${p6x6}</strong>
                    </label>
                </div>
            </div>
        `;
    }

    modal.innerHTML = `
        <div style="background:#1e293b; border:1px solid rgba(255,255,255,0.2); border-radius:18px; padding:30px; width:90%; max-width:440px; color:#fff; font-family:'Poppins',sans-serif; box-shadow:0 20px 50px rgba(0,0,0,0.6); position:relative;">
            <button onclick="document.getElementById('variant-modal').style.display='none'" 
                    style="position:absolute; top:15px; right:15px; background:none; border:none; color:#94a3b8; font-size:22px; cursor:pointer;">&times;</button>
            
            <h3 style="margin-top:0; margin-bottom:6px; font-size:20px;">Customize Your Selection</h3>
            <p style="color:#94a3b8; font-size:13px; margin-bottom:20px;">${productName}</p>

            ${woodHtml}
            ${sizeHtml}

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                <button onclick="document.getElementById('variant-modal').style.display='none'" 
                        style="background:none; border:1px solid #475569; color:#cbd5e1; padding:10px 20px; border-radius:25px; cursor:pointer;">
                    Cancel
                </button>
                <button onclick="confirmModalAddToCart(${productId}, '${encodeURIComponent(productName)}')" 
                        style="background:linear-gradient(45deg,#38bdf8,#6366f1); border:none; color:#fff; font-weight:600; padding:10px 25px; border-radius:25px; cursor:pointer;">
                    🛒 Confirm & Add to Cart
                </button>
            </div>
        </div>
    `;
    modal.style.display = 'flex';
};

window.confirmModalAddToCart = function(productId, encodedName = '') {
    let wood = null;
    const woodInput = document.querySelector('input[name="modal_wood"]:checked');
    if (woodInput) wood = woodInput.value;

    let size = null;
    const sizeInput = document.querySelector('input[name="modal_size"]:checked');
    if (sizeInput) size = sizeInput.value;

    const modal = document.getElementById('variant-modal');
    if (modal) modal.style.display = 'none';

    if (window.IS_USER_LOGGED_IN === false) {
        window.showAuthPromptModal(decodeURIComponent(encodedName));
        return;
    }

    window.sendAddToCart(productId, 1, wood, size);
};

window.sendAddToCart = function(productId, qty = 1, wood = null, size = null) {
    fetch("api/add_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            product_id: parseInt(productId),
            qty: parseInt(qty),
            wood_type: wood,
            size: size
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showNotification(data.message || "Added to cart!", true);
            const badge = document.getElementById('cart-count');
            if (badge && data.cart_qty !== undefined) badge.textContent = data.cart_qty;
        } else if (data.status === "auth_required") {
            window.showAuthPromptModal();
        } else {
            showNotification(data.message || "Could not add to cart", false);
        }
    })
    .catch(err => {
        console.error(err);
        showNotification("Failed to connect to server.", false);
    });
};

window.updateQty = function(cartId, newQty) {
    fetch("api/update_qty.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cart_id: parseInt(cartId), qty: parseInt(newQty) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            location.reload();
        } else {
            showNotification(data.message || "Failed to update quantity", false);
        }
    })
    .catch(err => {
        console.error(err);
        showNotification("Network error updating quantity.", false);
    });
};

window.removeProduct = function(cartId) {
    if (!confirm("Are you sure you want to remove this item from your cart?")) {
        return;
    }

    fetch("api/remove_cart.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cart_id: parseInt(cartId) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            location.reload();
        } else {
            showNotification(data.message || "Failed to remove item", false);
        }
    })
    .catch(err => {
        console.error(err);
        showNotification("Network error removing item.", false);
    });
};

window.rateProduct = function(productId, rating) {
    if (window.IS_USER_LOGGED_IN === false) {
        window.showAuthPromptModal();
        return;
    }
    fetch("api/rate_product.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ product_id: parseInt(productId), rating: parseInt(rating) })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showNotification(data.message || "Rating saved!", true);
            const scoreElem = document.getElementById('prod-rating-' + productId);
            if (scoreElem) {
                scoreElem.textContent = `★ ${data.avg_rating} (${data.total_ratings})`;
            }
        } else {
            showNotification(data.message || "Failed to record rating", false);
        }
    })
    .catch(err => {
        console.error(err);
        showNotification("Network error recording rating.", false);
    });
};