<?php
// patient/blood_donors.php 
// Connects patients with a registry of blood donors. Features donor filtering 
// by blood group, availability, and city, with a quick registration flow.

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

// Determine the donation message dynamically
$canDonate = false;
$recipientGroups = '';
if ($bloodGroup && $bloodGroup !== 'N/A') {
    $canDonate = true;
    switch ($bloodGroup) {
        case 'A+':  $recipientGroups = 'A+ and AB+'; break;
        case 'A-':  $recipientGroups = 'A+, A-, AB+, and AB-'; break;
        case 'B+':  $recipientGroups = 'B+ and AB+'; break;
        case 'B-':  $recipientGroups = 'B+, B-, AB+, and AB-'; break;
        case 'AB+': $recipientGroups = 'AB+'; break;
        case 'AB-': $recipientGroups = 'AB+ and AB-'; break;
        case 'O+':  $recipientGroups = 'A+, B+, AB+, and O+'; break;
        case 'O-':  $recipientGroups = 'all blood groups (Universal Donor)'; break;
        default:    $canDonate = false; break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donors | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-section{padding:18px 0 4px 20px;font-size:.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px}
        /* High-impact hero section for the blood donor page */
        .blood-hero{background:linear-gradient(135deg,#e53e3e,#c53030);border-radius:16px;padding:28px;color:#fff;margin-bottom:26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px}
        .blood-hero h2{font-size:1.4rem;margin:0 0 6px}
        .blood-hero p{opacity:.85;margin:0;font-size:.9rem}
        /* Selectable chips for blood group filtering */
        .blood-groups{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px}
        .bg-chip{min-width:64px;text-align:center;padding:12px 10px;border-radius:12px;border:2px solid var(--input-border);cursor:pointer;transition:all .2s;background:var(--card-bg)}
        .bg-chip:hover,.bg-chip.active{border-color:#e53e3e;background:#e53e3e;color:#fff}
        .bg-chip .bg-label{font-size:1.2rem;font-weight:700}
        .bg-chip .bg-name{font-size:.65rem;color:inherit;opacity:.8;margin-top:2px}
        .donor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}
        .donor-card{background:var(--card-bg);border:1px solid var(--input-border);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);transition:transform .25s,box-shadow .25s;position:relative}
        .donor-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
        .donor-card .avail-dot{position:absolute;top:16px;right:16px;width:10px;height:10px;border-radius:50%;background:#38a169;box-shadow:0 0 6px #38a169}
        .donor-card .avail-dot.unavailable{background:#e53e3e;box-shadow:0 0 6px #e53e3e}
        .donor-header{display:flex;align-items:center;gap:14px;margin-bottom:14px}
        .donor-avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:3px solid #e53e3e;flex-shrink:0}
        .blood-badge{background:#e53e3e;color:#fff;border-radius:8px;padding:6px 10px;font-weight:700;font-size:.9rem;flex-shrink:0}
        .donor-info h4{margin:0 0 3px;font-size:.95rem}
        .donor-info p{margin:0;font-size:.8rem;color:var(--text-secondary)}
        .donor-details{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
        .donor-detail-item{background:var(--input-bg);border-radius:8px;padding:8px 10px;font-size:.8rem}
        .donor-detail-item .d-label{color:var(--text-secondary);margin-bottom:2px;font-size:.72rem}
        .donor-detail-item .d-value{font-weight:600}
        .filter-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:20px}
        .filter-bar input,.filter-bar select{padding:9px 14px;border:1px solid var(--input-border);border-radius:8px;background:var(--input-bg);color:var(--text-primary);font-size:.88rem;outline:none}
        .donate-banner{background:linear-gradient(135deg,#2b6cb0,#1a365d);border-radius:14px;padding:22px;color:#fff;margin-bottom:24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
        .donate-banner i{font-size:2.5rem;opacity:.85}
        .donate-banner h3{margin:0 0 4px;font-size:1.1rem}
        .donate-banner p{margin:0;font-size:.85rem;opacity:.85}
        .empty-state{text-align:center;padding:50px 20px;color:var(--text-secondary);grid-column:1/-1}
        .empty-state i{font-size:3rem;margin-bottom:14px;opacity:.3;display:block}
        .results-info{font-size:.85rem;color:var(--text-secondary);margin-bottom:14px}
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
        <li><a href="medicines.php"><i class="fa-solid fa-pills"></i><span>Medicines</span></a></li>
        <li><a href="blood_donors.php" class="active"><i class="fa-solid fa-droplet"></i><span>Blood Donors</span></a></li>
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
            <div class="page-title"><h1>Blood Donors</h1><p>Patient Portal / Blood Donors</p></div>
        </div>
        <div class="topbar-right">
            <div class="topbar-search"><i class="fa-solid fa-search"></i><input type="text" placeholder="Search donors..."></div>
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
                <img src="https://i.pravatar.cc/40?img=11" alt="Patient"><span><?= htmlspecialchars($patientName) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary);"></i>
                <div class="dropdown-menu">
                    <a href="profile.html"><i class="fa-solid fa-user"></i> My Profile</a>
                    <a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    <main class="page-content">
        <!-- Hero -->
        <div class="blood-hero">
            <div>
                <h2><i class="fa-solid fa-droplet"></i> Blood Donor Registry</h2>
                <p>Connect with verified donors · Save lives · Access live blood donor directory</p>
            </div>
            <button class="btn" style="background:#fff;color:#e53e3e;font-weight:700;border:none;padding:12px 24px;border-radius:10px;cursor:pointer;font-size:.9rem;transition:transform .2s;" onmouseover="this.style.transform='scale(1.04)'" onmouseout="this.style.transform='scale(1)'" onclick="document.getElementById('registerModal').classList.add('open')">
                <i class="fa-solid fa-heart-pulse"></i> Register as Donor
            </button>
        </div>

        <!-- Donate Banner -->
        <div class="donate-banner">
            <i class="fa-solid fa-hand-holding-heart"></i>
            <div style="flex:1">
                <?php if ($canDonate): ?>
                    <h3>You have Blood Group <?= htmlspecialchars($bloodGroup) ?> — You can donate to <?= htmlspecialchars($recipientGroups) ?></h3>
                    <p>Your donation can save lives! Contact coordinates will be validated on registry entry.</p>
                <?php else: ?>
                    <h3>Please set your Blood Group in your Profile</h3>
                    <p>Knowing your blood group is crucial in emergencies. Let's make a difference!</p>
                <?php endif; ?>
            </div>
            <?php if ($canDonate): ?>
                <button class="btn" style="background:#fff;color:#2b6cb0;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;" onclick="alert('Donation request submitted! A coordinator will contact you within 24 hours.')">Donate Now</button>
            <?php else: ?>
                <a href="profile.html" class="btn" style="background:#fff;color:#2b6cb0;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;">Go to Profile</a>
            <?php endif; ?>
        </div>

        <!-- Stats (live from DB) -->
        <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:24px;">
            <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-droplet"></i></div><div class="stat-info"><h2 id="statTotal">—</h2><p>Total Donors</p></div></div>
            <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><h2 id="statAvail">—</h2><p>Available Now</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-hospital"></i></div><div class="stat-info"><h2 class="counter" data-target="24">0</h2><p>Partner Hospitals</p></div></div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fa-solid fa-heart-pulse"></i></div><div class="stat-info"><h2 class="counter" data-target="1480">0</h2><p>Lives Saved</p></div></div>
        </div>

        <!-- Blood Group Filter -->
        <div style="margin-bottom:16px;font-weight:600;font-size:.9rem;color:var(--text-secondary);">Filter by Blood Group</div>
        <div class="blood-groups" id="bgChips"></div>

        <!-- Search + Filters -->
        <div class="filter-bar">
            <input type="text" id="donorSearch" placeholder="Search by name or city...">
            <select id="donorAvail"><option value="">All Availability</option><option value="available">Available</option><option value="unavailable">Unavailable</option></select>
            <select id="donorCity"><option value="">All Cities</option></select>
        </div>
        <div class="results-info" id="donorCount"></div>

        <!-- Donor Grid -->
        <div class="donor-grid" id="donorGrid"></div>
    </main>
</div>

<!-- Register Modal -->
<div class="modal-overlay" id="registerModal">
    <div class="modal-box">
        <h3 style="margin-bottom:18px;"><i class="fa-solid fa-heart-pulse" style="color:#e53e3e;"></i> Register as Blood Donor</h3>
        <div class="input-row" style="display:flex;gap:12px;margin-bottom:12px;">
            <div class="form-group" style="flex:1;"><label>Full Name</label><input type="text" class="form-control" id="regName" placeholder="Your full name" value="<?= htmlspecialchars($patientName) ?>"></div>
            <div class="form-group" style="flex:1;"><label>Blood Group</label><select class="form-control" id="regBlood">
                <option <?= $bloodGroup === 'A+' ? 'selected' : '' ?>>A+</option>
                <option <?= $bloodGroup === 'A-' ? 'selected' : '' ?>>A-</option>
                <option <?= $bloodGroup === 'B+' ? 'selected' : '' ?>>B+</option>
                <option <?= $bloodGroup === 'B-' ? 'selected' : '' ?>>B-</option>
                <option <?= $bloodGroup === 'AB+' ? 'selected' : '' ?>>AB+</option>
                <option <?= $bloodGroup === 'AB-' ? 'selected' : '' ?>>AB-</option>
                <option <?= $bloodGroup === 'O+' ? 'selected' : '' ?>>O+</option>
                <option <?= $bloodGroup === 'O-' ? 'selected' : '' ?>>O-</option>
            </select></div>
        </div>
        <div class="input-row" style="display:flex;gap:12px;margin-bottom:12px;">
            <div class="form-group" style="flex:1;"><label>Age</label><input type="number" class="form-control" id="regAge" placeholder="e.g. 25" min="18" max="65" required></div>
            <div class="form-group" style="flex:1;"><label>Total Donations</label><input type="number" class="form-control" id="regDonations" placeholder="e.g. 2" min="0" value="0"></div>
        </div>
        <div class="input-row" style="display:flex;gap:12px;margin-bottom:12px;">
            <div class="form-group" style="flex:1;"><label>Phone</label><input type="tel" class="form-control" id="regPhone" placeholder="017xxxxxxxx" required></div>
            <div class="form-group" style="flex:1;"><label>City</label><input type="text" class="form-control" id="regCity" placeholder="Your area/city" required></div>
        </div>
        <div class="form-group" style="margin-bottom:16px;"><label>Last Donation Date</label><input type="date" class="form-control" id="regDate"></div>
        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="btn btn-primary w-100" onclick="registerDonor()"><i class="fa-solid fa-check"></i> Register</button>
            <button class="btn btn-outline" onclick="document.getElementById('registerModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal-overlay" id="contactModal">
    <div class="modal-box" style="max-width:400px;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:12px;">🩸</div>
        <h3 id="contactName">Contact Donor</h3>
        <p id="contactInfo" style="color:var(--text-secondary);margin:10px 0 20px;font-size:.9rem;"></p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a id="contactPhone" href="#" class="btn btn-primary w-100" style="justify-content:center;"><i class="fa-solid fa-phone"></i> Call Donor</a>
            <button class="btn btn-outline w-100" onclick="alert('Message sent!')"><i class="fa-solid fa-message"></i> Send Message</button>
            <button class="btn btn-outline w-100" onclick="document.getElementById('contactModal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>

<script>
// ── Theme ────────────────────────────────────────────────────────────
const html=document.documentElement;
const saved=localStorage.getItem('mpes-theme')||'light';
html.setAttribute('data-theme',saved);
document.querySelectorAll('.theme-btn').forEach(btn=>{
    if(btn.dataset.setTheme===saved)btn.classList.add('active');
    btn.addEventListener('click',()=>{const t=btn.dataset.setTheme;html.setAttribute('data-theme',t);localStorage.setItem('mpes-theme',t);document.querySelectorAll('.theme-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');});
});
document.getElementById('sidebarToggle').addEventListener('click',()=>{const s=document.getElementById('sidebar'),m=document.getElementById('mainWrapper');if(window.innerWidth<=768)s.classList.toggle('mobile-open');else{s.classList.toggle('collapsed');m.classList.toggle('sidebar-collapsed');}});
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');}));
document.querySelectorAll('.counter').forEach(el=>{const t=+el.dataset.target;let c=0;const step=Math.ceil(t/40);const iv=setInterval(()=>{c+=step;if(c>=t){c=t;clearInterval(iv);}el.textContent=c.toLocaleString();},40);});

// ── Blood group chips ────────────────────────────────────────────────
const BLOOD_GROUPS=['All','A+','A-','B+','B-','AB+','AB-','O+','O-'];
let activeBG='All';
const bgContainer=document.getElementById('bgChips');
BLOOD_GROUPS.forEach(bg=>{
    const chip=document.createElement('div');
    chip.className='bg-chip'+(bg==='All'?' active':'');
    chip.innerHTML=`<div class="bg-label">${bg}</div>`;
    chip.addEventListener('click',()=>{
        document.querySelectorAll('.bg-chip').forEach(c=>c.classList.remove('active'));
        chip.classList.add('active');
        activeBG=bg;
        fetchDonors();
    });
    bgContainer.appendChild(chip);
});

// ── Avatar helper (no external service needed) ───────────────────────
function avatarUrl(name){
    const initials=name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=e53e3e&color=fff&size=52`;
}

// ── Contact modal ────────────────────────────────────────────────────
function openContact(d){
    document.getElementById('contactName').textContent=d.name+' ('+d.blood_group+')';
    document.getElementById('contactInfo').textContent='Age '+d.age+' · '+d.city+' · Donated '+d.total_donations+' times';
    document.getElementById('contactPhone').href='tel:'+(d.phone||'');
    document.getElementById('contactModal').classList.add('open');
}

// ── Render donors ────────────────────────────────────────────────────
function renderDonors(donors){
    const grid=document.getElementById('donorGrid');
    document.getElementById('donorCount').textContent=`Showing ${donors.length.toLocaleString()} donor${donors.length!==1?'s':''}`;
    if(!donors.length){
        grid.innerHTML=`<div class="empty-state"><i class="fa-solid fa-droplet"></i><p>No donors found matching your filters.</p></div>`;
        return;
    }
    grid.innerHTML=donors.map(d=>`
        <div class="donor-card">
            <div class="avail-dot ${d.available?'':'unavailable'}"></div>
            <div class="donor-header">
                <img src="${avatarUrl(d.name)}" class="donor-avatar" alt="${d.name}">
                <div class="donor-info"><h4>${d.name}</h4><p>${d.city||'—'} · Age ${d.age||'?'}</p></div>
                <div class="blood-badge">${d.blood_group}</div>
            </div>
            <div class="donor-details">
                <div class="donor-detail-item"><div class="d-label">Last Donated</div><div class="d-value">${d.last_donated||'—'}</div></div>
                <div class="donor-detail-item"><div class="d-label">Total Donations</div><div class="d-value">${d.total_donations} times</div></div>
                <div class="donor-detail-item"><div class="d-label">Availability</div><div class="d-value" style="color:${d.available?'#38a169':'#e53e3e'}">${d.available?'✓ Available':'✗ Unavailable'}</div></div>
                <div class="donor-detail-item"><div class="d-label">Status</div><div class="d-value"><span class="badge-pill ${d.available?'success':'danger'}">${d.available?'Ready':'On Cooldown'}</span></div></div>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-primary btn-sm w-100" onclick='openContact(${JSON.stringify(d).replace(/'/g,"&#39;")})'  ${!d.available?'disabled style="opacity:.5;cursor:not-allowed;"':''}><i class="fa-solid fa-phone"></i> Contact</button>
                <button class="btn btn-outline btn-sm"><i class="fa-solid fa-bookmark"></i></button>
            </div>
        </div>`).join('');
}

// ── Animate a number counter ─────────────────────────────────────────
function animateTo(el, target){
    if(!el) return;
    let c=0;
    const step=Math.max(1, Math.ceil(target/60));
    const iv=setInterval(()=>{
        c+=step;
        if(c>=target){c=target;clearInterval(iv);}
        el.textContent=c.toLocaleString();
    },30);
}

// ── Fetch live stats from API ────────────────────────────────────────
async function fetchStats(){
    try{
        const res=await fetch('../api/blood_donors.php?action=stats');
        const data=await res.json();
        if(data.success){
            animateTo(document.getElementById('statTotal'), data.total);
            animateTo(document.getElementById('statAvail'), data.available);
            // Populate city dropdown from DB
            const sel=document.getElementById('donorCity');
            sel.innerHTML='<option value="">All Cities</option>';
            data.cities.forEach(c=>{
                if(c){ const o=document.createElement('option'); o.value=c; o.textContent=c; sel.appendChild(o); }
            });
        }
    }catch(e){ console.error('Stats fetch error',e); }
}

// ── Main fetch: load donors with current filters ─────────────────────
async function fetchDonors(){
    const search=document.getElementById('donorSearch').value.trim();
    const avail =document.getElementById('donorAvail').value;
    const city  =document.getElementById('donorCity').value;

    const params=new URLSearchParams();
    params.set('action','list');
    if(activeBG && activeBG!=='All') params.set('blood_group', activeBG);
    if(avail)  params.set('availability', avail);
    if(city)   params.set('city', city);
    if(search) params.set('search', search);

    document.getElementById('donorGrid').innerHTML=`<div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading donors...</p></div>`;
    document.getElementById('donorCount').textContent='Loading...';

    try{
        const res=await fetch('../api/blood_donors.php?'+params.toString());
        const data=await res.json();
        if(data.success){
            renderDonors(data.donors);
        } else {
            document.getElementById('donorGrid').innerHTML=`<div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><p>Error loading donors: ${data.message}</p></div>`;
        }
    }catch(e){
        document.getElementById('donorGrid').innerHTML=`<div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><p>Could not reach server. Is the PHP server running?</p></div>`;
    }
}

// ── Wire up filter controls ──────────────────────────────────────────
let debounceTimer;
document.getElementById('donorSearch').addEventListener('input',()=>{
    clearTimeout(debounceTimer);
    debounceTimer=setTimeout(fetchDonors,300);
});
['donorAvail','donorCity'].forEach(id=>document.getElementById(id).addEventListener('change',fetchDonors));

// ── Register donor (dynamic) ─────────────────────────────────────────
async function registerDonor(){
    const name = document.getElementById('regName').value.trim();
    const blood_group = document.getElementById('regBlood').value;
    const age = document.getElementById('regAge').value.trim();
    const total_donations = document.getElementById('regDonations').value.trim();
    const phone = document.getElementById('regPhone').value.trim();
    const city = document.getElementById('regCity').value.trim();
    const last_donation_date = document.getElementById('regDate').value;

    if (!name || !blood_group || !age || !phone || !city) {
        alert('Please fill in all required fields (Name, Blood Group, Age, City, and Phone).');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'register');
    formData.append('name', name);
    formData.append('blood_group', blood_group);
    formData.append('age', age);
    formData.append('total_donations', total_donations);
    formData.append('phone', phone);
    formData.append('city', city);
    formData.append('last_donation_date', last_donation_date);

    try {
        const res = await fetch('../api/blood_donors.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ ' + data.message);
            document.getElementById('registerModal').classList.remove('open');
            // Refresh stats and donor list
            fetchStats();
            fetchDonors();
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch(e) {
        alert('❌ Failed to connect to server.');
    }
}

// ── Init ─────────────────────────────────────────────────────────────
fetchStats();
fetchDonors();
</script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
