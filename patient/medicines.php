<?php
// patient/medicines.php
// Patient pharmacy interface. Allows users to browse medicine categories, 
// search for drugs, and add items to an online pharmacy cart.

require_once '../config/session.php'; // Session management
require_once '../config/db.php';      // Database connection
requireLogin('patient');              // Ensure user is a patient

$user_id = $_SESSION['user_id'];

// Retrieve patient details for personalization
$stmt = $pdo->prepare("SELECT name, email, blood_group FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

$patientName = $patient['name'] ?? 'Patient';
$bloodGroup = $patient['blood_group'] ?: 'N/A';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-section{padding:18px 0 4px 20px;font-size:.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px}
        /* Pharmacy branding hero area */
        .med-hero{background:linear-gradient(135deg,#38a169,#276749);border-radius:16px;padding:32px;color:#fff;margin-bottom:26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px}
        [data-theme="colorblind"] .med-hero{background:linear-gradient(135deg,#ed8936,#c05621)}
        .med-hero-text h2{font-size:1.6rem;margin:0 0 8px;display:flex;align-items:center;gap:10px}
        .med-hero-text p{opacity:0.9;margin:0;font-size:0.95rem;max-width:500px}
        .med-search-box{background:#fff;padding:6px;border-radius:12px;display:flex;gap:6px;width:100%;max-width:400px}
        .med-search-box input{flex:1;border:none;outline:none;padding:10px 14px;background:transparent;color:#333;font-size:0.95rem}
        .med-search-box button{background:#38a169;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;transition:transform 0.2s}
        .med-search-box button:hover{transform:scale(1.05)}
        [data-theme="colorblind"] .med-search-box button{background:#ed8936}
        
        .category-scroll{display:flex;gap:12px;overflow-x:auto;padding-bottom:12px;margin-bottom:20px}
        .cat-chip{padding:12px 20px;background:var(--card-bg);border:1px solid var(--input-border);border-radius:12px;cursor:pointer;display:flex;align-items:center;gap:10px;font-weight:600;font-size:0.9rem;transition:all 0.2s;white-space:nowrap;color:var(--text-secondary)}
        .cat-chip i{font-size:1.2rem;color:var(--primary-color)}
        .cat-chip:hover,.cat-chip.active{background:var(--primary-color);color:#fff;border-color:var(--primary-color)}
        .cat-chip:hover i,.cat-chip.active i{color:#fff}
        
        .med-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
        .med-card{background:var(--card-bg);border:1px solid var(--input-border);border-radius:16px;padding:20px;box-shadow:var(--shadow-sm);transition:transform 0.25s,box-shadow 0.25s;display:flex;flex-direction:column}
        .med-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
        
        .med-header{display:flex;gap:16px;margin-bottom:16px;align-items:flex-start}
        .med-icon-wrap{width:56px;height:56px;border-radius:14px;background:rgba(56,161,105,0.1);color:#38a169;display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0}
        .med-info{flex:1}
        .med-info h3{margin:0 0 4px;font-size:1.1rem;color:var(--text-primary)}
        .med-info p{margin:0;font-size:0.8rem;color:var(--text-secondary)}
        .med-tag{display:inline-block;padding:3px 8px;border-radius:6px;font-size:0.7rem;font-weight:700;margin-top:6px;background:var(--input-bg);color:var(--text-secondary)}
        .rx-required{background:rgba(229,62,62,0.1);color:#e53e3e}
        
        .med-details{background:var(--input-bg);border-radius:10px;padding:12px;margin-bottom:16px;flex:1}
        .med-detail-row{display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.82rem}
        .med-detail-row:last-child{margin-bottom:0}
        .md-label{color:var(--text-secondary)}
        .md-val{font-weight:600;color:var(--text-primary)}
        
        .med-actions{display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--input-border);padding-top:16px}
        .med-price{font-size:1.2rem;font-weight:700;color:var(--text-primary)}
        
        .btn-order{background:#38a169;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-weight:600;cursor:pointer;font-size:0.85rem;display:flex;align-items:center;gap:6px;transition:background 0.2s}
        .btn-order:hover{background:#2f855a}
        
        .empty-state{text-align:center;padding:50px 20px;color:var(--text-secondary);grid-column:1/-1}
        .empty-state i{font-size:3rem;margin-bottom:14px;opacity:.3;display:block}

        /* Cart Sidebar */
        .cart-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;display:none}
        .cart-overlay.open{display:block}
        .cart-panel{position:fixed;top:0;right:-420px;width:400px;max-width:95vw;height:100vh;background:var(--card-bg);box-shadow:-4px 0 24px rgba(0,0,0,.18);z-index:901;display:flex;flex-direction:column;transition:right .3s ease}
        .cart-panel.open{right:0}
        .cart-head{padding:22px 20px;border-bottom:1px solid var(--input-border);display:flex;align-items:center;justify-content:space-between}
        .cart-head h2{margin:0;font-size:1.1rem;display:flex;align-items:center;gap:8px}
        .cart-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-secondary)}
        .cart-items{flex:1;overflow-y:auto;padding:16px}
        .cart-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--input-border)}
        .cart-item-info{flex:1}
        .cart-item-info strong{display:block;font-size:.9rem}
        .cart-item-info span{font-size:.8rem;color:var(--text-secondary)}
        .cart-qty{display:flex;align-items:center;gap:8px}
        .qty-btn{width:28px;height:28px;border-radius:7px;border:1px solid var(--input-border);background:var(--input-bg);cursor:pointer;font-size:1rem;font-weight:700;display:flex;align-items:center;justify-content:center}
        .qty-val{font-weight:700;min-width:20px;text-align:center}
        .cart-item-price{font-weight:700;color:var(--primary-color);white-space:nowrap}
        .cart-remove{background:none;border:none;color:#e53e3e;cursor:pointer;font-size:1rem}
        .cart-foot{padding:20px;border-top:1px solid var(--input-border)}
        .cart-total-row{display:flex;justify-content:space-between;margin-bottom:16px;font-size:1rem;font-weight:700}
        .cart-total-row span:last-child{color:var(--primary-color);font-size:1.2rem}
        .btn-checkout{width:100%;padding:14px;background:#38a169;color:#fff;border:none;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;transition:background .2s}
        .btn-checkout:hover{background:#2f855a}
        .cart-empty-msg{text-align:center;padding:40px 0;color:var(--text-secondary)}
        .cart-empty-msg i{font-size:2.5rem;opacity:.3;display:block;margin-bottom:10px}

        /* Checkout Modal */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center}
        .modal-overlay.open{display:flex}
        .modal-box{background:var(--card-bg);border-radius:18px;padding:32px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
        .modal-box h3{margin:0 0 20px;font-size:1.2rem;display:flex;align-items:center;gap:8px}
        .modal-field{margin-bottom:16px}
        .modal-field label{display:block;margin-bottom:6px;font-weight:600;font-size:.87rem;color:var(--text-secondary)}
        .modal-field input,.modal-field textarea{width:100%;padding:12px 14px;border:1px solid var(--input-border);border-radius:10px;background:var(--input-bg);color:var(--text-primary);font-size:.95rem;box-sizing:border-box}
        .modal-field textarea{resize:vertical;min-height:80px}
        .modal-actions{display:flex;gap:10px;margin-top:20px}
        .modal-actions button{flex:1;padding:12px;border-radius:10px;font-weight:700;cursor:pointer;border:none;font-size:.95rem}
        .btn-cancel{background:var(--input-bg);color:var(--text-primary)}
        .btn-confirm{background:#38a169;color:#fff}
        .btn-confirm:hover{background:#2f855a}

        /* Toast Notification */
        .toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(80px);background:#2d3748;color:#fff;padding:12px 22px;border-radius:12px;font-size:.9rem;font-weight:600;z-index:2000;opacity:0;transition:all .35s ease;display:flex;align-items:center;gap:10px;white-space:nowrap;box-shadow:0 8px 24px rgba(0,0,0,.25)}
        .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
        .toast i{color:#68d391;font-size:1.1rem}
    </style>
</head>
<body class="dashboard-body">

<aside class="sidebar" id="sidebar">
    <a class="sidebar-logo" href="dashboard.php"><i class="fa-solid fa-truck-medical logo-icon"></i><span class="logo-text">MPES</span></a>
    <p class="nav-section">MAIN</p>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fa-solid fa-house-medical"></i><span>Dashboard</span></a></li>
        <li><a href="profile.html"><i class="fa-solid fa-user-injured"></i><span>My Profile</span></a></li>
        <li><a href="appointments.html"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a></li>
        <li><a href="prescriptions.php"><i class="fa-solid fa-file-prescription"></i><span>Prescriptions</span></a></li>
        <li><a href="test_reports.html"><i class="fa-solid fa-flask-vial"></i><span>Test Reports</span></a></li>
    </ul>
    <p class="nav-section">FIND</p>
    <ul class="sidebar-menu">
        <li><a href="find_doctors.html"><i class="fa-solid fa-user-doctor"></i><span>Find Doctors</span></a></li>
        <li><a href="hospitals.php"><i class="fa-solid fa-hospital"></i><span>Hospitals</span></a></li>
        <li><a href="medicines.php" class="active"><i class="fa-solid fa-pills"></i><span>Medicines</span></a></li>
        <li><a href="blood_donors.php"><i class="fa-solid fa-droplet"></i><span>Blood Donors</span></a></li>
    </ul>
    <p class="nav-section">HEALTH</p>
    <ul class="sidebar-menu">
        <li><a href="symptom_checker.html"><i class="fa-solid fa-robot"></i><span>AI Symptom Checker</span></a></li>
        <li><a href="settings.html"><i class="fa-solid fa-gear"></i><span>Settings</span></a></li>
        <li class="danger"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</aside>

<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button class="icon-btn" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="page-title"><h1>Medicines</h1><p>Patient Portal / Pharmacy</p></div>
        </div>
        <div class="topbar-right">
            <button class="icon-btn" id="cartBtn" style="background:var(--primary-color);color:#fff;border-radius:10px;padding:8px 16px;width:auto;" onclick="openCart()"><i class="fa-solid fa-cart-shopping"></i> <span style="font-size:0.85rem;font-weight:600;margin-left:6px;">Cart (<span id="cartCount">0</span>)</span></button>
            <div class="theme-switcher-row">
                <button class="theme-btn" data-set-theme="light"><i class="fa-solid fa-sun"></i></button>
                <button class="theme-btn" data-set-theme="dark"><i class="fa-solid fa-moon"></i></button>
                <button class="theme-btn" data-set-theme="colorblind"><i class="fa-solid fa-eye-low-vision"></i></button>
            </div>
            <div class="notification-container">
                <button class="icon-btn" id="notificationBtn" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <span class="badge" id="notificationBadge" style="display:none;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button id="markAllReadBtn" class="mark-all-read">Mark all as read</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">
                            <i class="fa-solid fa-bell-slash"></i>
                            <p>Loading notifications...</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="settings.html">Notification Settings</a>
                    </div>
                </div>
            </div>
            <div class="user-pill">
                <img src="https://i.pravatar.cc/40?img=11" alt="Patient">
                <span><?= htmlspecialchars($patientName) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary);"></i>
                <div class="dropdown-menu">
                    <a href="profile.html"><i class="fa-solid fa-user"></i> My Profile</a>
                    <a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="page-content">
        <div class="med-hero">
            <div class="med-hero-text">
                <h2><i class="fa-solid fa-prescription-bottle-medical"></i> Online Pharmacy</h2>
                <p>Search and order your prescribed medicines. Get them delivered to your door within 24 hours.</p>
            </div>
            <div class="med-search-box">
                <input type="text" id="medSearch" placeholder="Search for medicines...">
                <button onclick="filterMeds()"><i class="fa-solid fa-search"></i> Search</button>
            </div>
        </div>

        <div class="category-scroll" id="catScroll">
            <div class="cat-chip active" data-cat="All"><i class="fa-solid fa-border-all"></i> All Medicines</div>
            <div class="cat-chip" data-cat="Antibiotics"><i class="fa-solid fa-capsules"></i> Antibiotics</div>
            <div class="cat-chip" data-cat="Painkillers"><i class="fa-solid fa-pills"></i> Painkillers</div>
            <div class="cat-chip" data-cat="Vitamins"><i class="fa-solid fa-leaf"></i> Vitamins</div>
            <div class="cat-chip" data-cat="Syrups"><i class="fa-solid fa-wine-bottle"></i> Syrups</div>
            <div class="cat-chip" data-cat="Inhalers"><i class="fa-solid fa-spray-can"></i> Inhalers</div>
            <div class="cat-chip" data-cat="First Aid"><i class="fa-solid fa-kit-medical"></i> First Aid</div>
        </div>

        <div class="med-grid" id="medGrid">
            <!-- Rendered by JS -->
        </div>

    </main>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast"><i class="fa-solid fa-circle-check"></i><span id="toastMsg">Added to cart!</span></div>

<!-- Cart Overlay & Panel -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<div class="cart-panel" id="cartPanel">
    <div class="cart-head">
        <h2><i class="fa-solid fa-cart-shopping"></i> Your Cart</h2>
        <button class="cart-close" onclick="closeCart()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-foot" id="cartFoot" style="display:none">
        <div class="cart-total-row"><span>Total</span><span id="cartTotal">$0.00</span></div>
        <button class="btn-checkout" onclick="openCheckout()"><i class="fa-solid fa-truck"></i> Proceed to Checkout</button>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal-box">
        <h3><i class="fa-solid fa-location-dot" style="color:#38a169"></i> Delivery Details</h3>
        <div class="modal-field">
            <label>Full Name</label>
            <input type="text" id="chkName" placeholder="Enter your full name" value="<?= htmlspecialchars($patientName) ?>">
        </div>
        <div class="modal-field">
            <label>Phone Number</label>
            <input type="tel" id="chkPhone" placeholder="e.g. 01700000000">
        </div>
        <div class="modal-field">
            <label>Delivery Address</label>
            <textarea id="chkAddress" placeholder="House, Road, Area, City..."></textarea>
        </div>
        <div class="modal-field">
            <label>Order Note (optional)</label>
            <input type="text" id="chkNote" placeholder="Any special instructions...">
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeCheckout()">Cancel</button>
            <button class="btn-confirm" onclick="placeOrder()"><i class="fa-solid fa-check"></i> Place Order</button>
        </div>
    </div>
</div>

<script>
const html=document.documentElement;
const saved=localStorage.getItem('mpes-theme')||'light';
html.setAttribute('data-theme',saved);
document.querySelectorAll('.theme-btn').forEach(btn=>{
    if(btn.dataset.setTheme===saved)btn.classList.add('active');
    btn.addEventListener('click',()=>{const t=btn.dataset.setTheme;html.setAttribute('data-theme',t);localStorage.setItem('mpes-theme',t);document.querySelectorAll('.theme-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');});
});
const sidebar=document.getElementById('sidebar'),mw=document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click',()=>{if(window.innerWidth<=768)sidebar.classList.toggle('mobile-open');else{sidebar.classList.toggle('collapsed');mw.classList.toggle('sidebar-collapsed');}});

let MEDS = [];
let currentCat = 'All';

async function loadMedicines() {
    try {
        const grid = document.getElementById('medGrid');
        grid.innerHTML = `<div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading medicines from database...</p></div>`;
        
        const response = await fetch('../api/crud.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_medicines'
        });
        const data = await response.json();
        
        if (data.success) {
            MEDS = data.medicines.map(m => ({
                name: m.name,
                type: m.type,
                category: m.category,
                manufacturer: m.company,
                rxReq: m.rx_required == 1,
                stock: m.stock_status,
                price: parseFloat(m.price)
            }));
            filterMeds();
        } else {
            grid.innerHTML = `<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Error loading medicines: ${data.message}</p></div>`;
        }
    } catch (e) {
        console.error('Fetch error:', e);
        document.getElementById('medGrid').innerHTML = `<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Network error loading medicines.</p></div>`;
    }
}

function renderMeds(list) {
    const grid = document.getElementById('medGrid');
    if(list.length === 0) {
        grid.innerHTML = `<div class="empty-state"><i class="fa-solid fa-prescription-bottle-slash"></i><p>No medicines found matching your search.</p></div>`;
        return;
    }

    grid.innerHTML = list.map(m => `
        <div class="med-card">
            <div class="med-header">
                <div class="med-icon-wrap"><i class="fa-solid ${getIcon(m.category)}"></i></div>
                <div class="med-info">
                    <h3>${m.name}</h3>
                    <p>${m.type} · ${m.manufacturer}</p>
                    ${m.rxReq ? '<span class="med-tag rx-required"><i class="fa-solid fa-file-prescription"></i> Rx Required</span>' : '<span class="med-tag">OTC</span>'}
                </div>
            </div>
            <div class="med-details">
                <div class="med-detail-row"><span class="md-label">Category</span><span class="md-val">${m.category}</span></div>
                <div class="med-detail-row"><span class="md-label">Availability</span><span class="md-val" style="color:${m.stock === 'Out of Stock' ? '#e53e3e' : '#38a169'}">${m.stock}</span></div>
            </div>
            <div class="med-actions">
                <div class="med-price">৳${m.price.toFixed(2)}</div>
                <button class="btn-order" onclick="addToCart('${m.name}', ${m.price})" ${m.stock === 'Out of Stock' ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
            </div>
        </div>
    `).join('');
}

function getIcon(cat) {
    switch(cat) {
        case 'Antibiotics': return 'fa-capsules';
        case 'Painkillers': return 'fa-pills';
        case 'Vitamins': return 'fa-leaf';
        case 'Syrups': return 'fa-wine-bottle';
        case 'Inhalers': return 'fa-spray-can';
        case 'First Aid': return 'fa-kit-medical';
        default: return 'fa-prescription-bottle-medical';
    }
}

function filterMeds() {
    const q = document.getElementById('medSearch').value.toLowerCase();
    const filtered = MEDS.filter(m => {
        const matchQ = m.name.toLowerCase().includes(q) || m.manufacturer.toLowerCase().includes(q);
        const matchCat = currentCat === 'All' ? true : m.category === currentCat;
        return matchQ && matchCat;
    });
    renderMeds(filtered);
}

document.querySelectorAll('.cat-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.cat-chip').forEach(c=>c.classList.remove('active'));
        chip.classList.add('active');
        currentCat = chip.getAttribute('data-cat');
        filterMeds();
    });
});

document.getElementById('medSearch').addEventListener('keypress', (e) => {
    if(e.key === 'Enter') filterMeds();
});

// ─── CART STATE ───────────────────────────────────────────────
let cart = JSON.parse(localStorage.getItem('mpes-cart') || '[]');

function saveCart() { localStorage.setItem('mpes-cart', JSON.stringify(cart)); }

function addToCart(name, price) {
    price = parseFloat(price);
    if (isNaN(price)) { alert('Price error, please refresh.'); return; }
    const existing = cart.find(i => i.name === name);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ name, price, qty: 1 });
    }
    saveCart();
    updateCartCount();
    showToast('Added to cart: ' + name);
    openCart();
}

// ─── CART COUNT ───────────────────────────────────────────────
function updateCartCount() {
    const total = cart.reduce((s, i) => s + i.qty, 0);
    document.getElementById('cartCount').textContent = total;
}

function openCart() {
    document.getElementById('cartOverlay').classList.add('open');
    document.getElementById('cartPanel').classList.add('open');
    renderCartItems();
}

function closeCart() {
    document.getElementById('cartOverlay').classList.remove('open');
    document.getElementById('cartPanel').classList.remove('open');
}

function renderCartItems() {
    const box = document.getElementById('cartItems');
    const foot = document.getElementById('cartFoot');
    if (cart.length === 0) {
        box.innerHTML = `<div class="cart-empty-msg"><i class="fa-solid fa-cart-shopping"></i><p>Your cart is empty.</p></div>`;
        foot.style.display = 'none';
        return;
    }
    foot.style.display = 'block';
    let total = 0;
    box.innerHTML = cart.map((item, idx) => {
        total += item.price * item.qty;
        return `<div class="cart-item">
            <div class="cart-item-info">
                <strong>${item.name}</strong>
                <span>৳${item.price.toFixed(2)} each</span>
            </div>
            <div class="cart-qty">
                <button class="qty-btn" onclick="changeQty(${idx},-1)">-</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty(${idx},1)">+</button>
            </div>
            <div class="cart-item-price">৳${(item.price * item.qty).toFixed(2)}</div>
            <button class="cart-remove" onclick="removeItem(${idx})" title="Remove"><i class="fa-solid fa-trash"></i></button>
        </div>`;
    }).join('');
    document.getElementById('cartTotal').textContent = '৳' + total.toFixed(2);
}

function changeQty(idx, delta) {
    cart[idx].qty += delta;
    if (cart[idx].qty <= 0) cart.splice(idx, 1);
    saveCart(); updateCartCount(); renderCartItems();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    saveCart(); updateCartCount(); renderCartItems();
}

function openCheckout() {
    if (cart.length === 0) { alert('Your cart is empty!'); return; }
    closeCart();
    document.getElementById('checkoutModal').classList.add('open');
}

function closeCheckout() {
    document.getElementById('checkoutModal').classList.remove('open');
}

function placeOrder() {
    const name = document.getElementById('chkName').value.trim();
    const phone = document.getElementById('chkPhone').value.trim();
    const address = document.getElementById('chkAddress').value.trim();
    if (!name) { alert('Please enter your full name.'); return; }
    if (!phone) { alert('Please enter your phone number.'); return; }
    if (!address) { alert('Please enter your delivery address.'); return; }
    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
    closeCheckout();
    cart = []; saveCart(); updateCartCount();
    alert(`✅ Order placed successfully!\n\nDelivery to: ${address}\nContact: ${phone}\nTotal: ৳${total.toFixed(2)}\n\nExpected delivery within 24 hours.`);
}

let toastTimer;
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
}

updateCartCount();
loadMedicines();
</script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
