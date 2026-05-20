<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Portal | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Guest Landing header (no sidebar) ─────────────── */
        body { flex-direction: column; }
        .guest-navbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--input-border);
            padding: 16px 40px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top:0; z-index:900;
        }
        .guest-navbar .logo { display:flex; align-items:center; gap:10px; font-size:1.3rem; font-weight:700; color:var(--primary-color); text-decoration:none; }
        .guest-navbar .logo i { font-size:1.6rem; }
        .guest-nav-links { display:flex; gap:24px; align-items:center; }
        .guest-nav-links a { color:var(--text-secondary); text-decoration:none; font-weight:500; font-size:0.9rem; transition:color 0.2s; }
        .guest-nav-links a:hover { color:var(--primary-color); }
        .hero {
            background: linear-gradient(135deg, #2b6cb0 0%, #1a365d 60%, #2c7a7b 100%);
            color:#fff;
            padding: 60px 40px;
            text-align:center;
        }
        [data-theme="colorblind"] .hero { background: linear-gradient(135deg,#ed8936,#805ad5); }
        .hero h1 { font-size:2.2rem; font-weight:800; margin-bottom:12px; }
        .hero p { font-size:1rem; opacity:0.85; margin-bottom:30px; }
        .hero-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
        .btn-hero { padding:12px 28px; border-radius:25px; font-weight:700; font-size:0.95rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:transform 0.2s, box-shadow 0.2s; }
        .btn-hero.white { background:#fff; color:#2b6cb0; }
        .btn-hero.outline { background:transparent; border:2px solid rgba(255,255,255,0.7); color:#fff; }
        .btn-hero:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.2); }
        .guest-login-modal { max-width:400px; margin:0 auto; }
        .section-header { text-align:center; margin:48px 0 28px; }
        .section-header h2 { font-size:1.5rem; font-weight:700; }
        .section-header p { color:var(--text-secondary); font-size:0.9rem; margin-top:6px; }
        .section-container { max-width:1200px; margin:0 auto; padding:0 24px; }
        .filter-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
        .filter-bar select, .filter-bar input { padding:9px 14px; border-radius:8px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary); font-size:0.88rem; outline:none; }
        .blood-donor-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:18px; }
        .donor-card { background:var(--card-bg); border:1px solid var(--input-border); border-radius:var(--border-radius); padding:18px; text-align:center; transition:transform 0.2s, box-shadow 0.2s; }
        .donor-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }
        .blood-badge-large { width:56px; height:56px; background:rgba(229,62,62,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:800; color:#e53e3e; margin:0 auto 12px; }
        .medicine-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }
        .medicine-card { background:var(--card-bg); border:1px solid var(--input-border); border-radius:var(--border-radius); padding:16px; transition:transform 0.2s; }
        .medicine-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .medicine-card .price { font-size:1.1rem; font-weight:700; color:var(--primary-color); margin-top:8px; }
        .report-lookup { max-width:500px; margin:0 auto; display:flex; gap:12px; }
        .report-lookup input { flex:1; padding:12px 18px; border-radius:25px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary); outline:none; font-size:0.9rem; }
        .report-result { max-width:600px; margin:20px auto 0; background:var(--card-bg); border-radius:var(--border-radius); padding:20px; border:1px solid var(--input-border); display:none; }
        .ambulance-hero { background:linear-gradient(135deg,#e53e3e,#c53030); color:#fff; border-radius:var(--border-radius); padding:28px 32px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
        .ambulance-hero h2 { font-size:1.4rem; margin-bottom:6px; }
        .ambulance-hero p { opacity:0.85; font-size:0.88rem; }
        .ambulance-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:18px; }
        .ambulance-card { background:var(--card-bg); border:1px solid var(--input-border); border-radius:var(--border-radius); padding:18px; border-left:4px solid #e53e3e; }
        .ambulance-card h4 { margin:0 0 6px; }
        .ambulance-card p { margin:0; font-size:0.82rem; color:var(--text-secondary); }
        .tab-bar { display:flex; gap:6px; border-bottom:2px solid var(--input-border); margin-bottom:28px; }
        .tab-btn { padding:10px 20px; border:none; background:transparent; font-weight:600; color:var(--text-secondary); cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; font-size:0.9rem; transition:all 0.2s; }
        .tab-btn.active { color:var(--primary-color); border-bottom-color:var(--primary-color); }
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }
        .guest-footer { background:var(--card-bg); border-top:1px solid var(--input-border); padding:32px 40px; text-align:center; margin-top:60px; }
        .guest-footer .emerg-numbers { display:flex; gap:24px; justify-content:center; flex-wrap:wrap; font-weight:700; margin:12px 0; }
        .guest-footer .emerg-numbers span { color:#e53e3e; }
    </style>
</head>
<body>

<!-- Guest Navbar -->
<nav class="guest-navbar">
    <a href="#" class="logo"><i class="fa-solid fa-truck-medical"></i> MPES</a>
    <div class="guest-nav-links">
        <a href="#doctors">Doctors</a>
        <a href="#medicines">Medicines</a>
        <a href="#donors">Blood Donors</a>
        <a href="#reports">Test Reports</a>
        <a href="#ambulance">Ambulance</a>
        <div class="theme-switcher-row">
            <button class="theme-btn" data-set-theme="light"><i class="fa-solid fa-sun"></i></button>
            <button class="theme-btn" data-set-theme="dark"><i class="fa-solid fa-moon"></i></button>
            <button class="theme-btn" data-set-theme="colorblind"><i class="fa-solid fa-eye-low-vision"></i></button>
        </div>
        <a href="../login.php" class="btn btn-primary btn-sm">Login / Register</a>
    </div>
</nav>

<!-- Hero Section -->
<!-- Landing area for guests explaining the value proposition and quick actions -->
<div class="hero">
    <h1><i class="fa-solid fa-truck-medical"></i> Medical Primary Emergency System</h1>
    <p>Instant access to doctors, hospitals, medicines, blood donors and emergency ambulances — no login required.</p>
    <div class="hero-actions">
        <!-- Triggers the guest registration/login modal -->
        <button class="btn-hero white" onclick="document.getElementById('guestModal').classList.add('open')"><i class="fa-solid fa-user-plus"></i> Quick Guest Access</button>
        <!-- Scrolls down to the emergency ambulance section -->
        <a href="#ambulance" class="btn-hero outline"><i class="fa-solid fa-truck-medical"></i> Emergency Ambulance</a>
        <!-- Redirects to main login page -->
        <a href="../login.php" class="btn-hero outline"><i class="fa-solid fa-right-to-bracket"></i> Patient Login</a>
    </div>
</div>

<!-- Guest Quick-Login Modal -->
<!-- A popup overlay to capture basic guest information (Name and Phone) -->
<div class="modal-overlay" id="guestModal">
    <div class="modal-box" style="max-width:380px;">
        <h3 style="margin-bottom:20px;"><i class="fa-solid fa-user" style="color:var(--primary-color);"></i> Guest Access</h3>
        
        <!-- Name Input -->
        <div class="form-group">
            <label>Your Name</label>
            <input type="text" class="form-control" placeholder="John Doe" id="guestName">
        </div>
        
        <!-- Phone Input -->
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" class="form-control" placeholder="+1 555 000 0000" id="guestPhone">
        </div>
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button class="btn btn-primary w-100" onclick="guestLogin()">Continue as Guest</button>
            <button class="btn btn-outline" onclick="document.getElementById('guestModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<div class="section-container">

    <!-- Tab Navigation -->
    <div class="tab-bar" style="margin-top:36px;">
        <button class="tab-btn active" onclick="switchTab('doctors')"><i class="fa-solid fa-user-doctor"></i> Doctors</button>
        <button class="tab-btn" onclick="switchTab('medicines')"><i class="fa-solid fa-pills"></i> Medicines</button>
        <button class="tab-btn" onclick="switchTab('donors')"><i class="fa-solid fa-droplet"></i> Blood Donors</button>
        <button class="tab-btn" onclick="switchTab('reports')"><i class="fa-solid fa-file-medical"></i> Test Reports</button>
        <button class="tab-btn" onclick="switchTab('ambulance')"><i class="fa-solid fa-truck-medical"></i> Ambulance</button>
    </div>

    <!-- ─── DOCTORS TAB ─── -->
    <div class="tab-panel active" id="tab-doctors">
        <div class="filter-bar">
            <input type="text" placeholder="Search by name..." id="drSearch">
            <select id="drSpecialist">
                <option value="">All Specialists</option>
                <option>Cardiologist</option>
                <option>Neurologist</option>
                <option>Dermatologist</option>
                <option>General Physician</option>
                <option>Orthopedic</option>
            </select>
            <select id="drSort">
                <option value="">Sort by</option>
                <option value="fee">Fee: Low to High</option>
                <option value="exp">Experience</option>
                <option value="rating">Rating</option>
            </select>
        </div>
        <div class="doctor-grid" id="doctorGrid">
            <!-- Populated by JS -->
        </div>
    </div>

    <!-- ─── MEDICINES TAB ─── -->
    <div class="tab-panel" id="tab-medicines">
        <div class="filter-bar">
            <input type="text" placeholder="Search medicine name..." id="medSearch">
            <select id="medSort">
                <option value="">Sort by</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name">Name A-Z</option>
            </select>
        </div>
        <div class="medicine-grid" id="medicineGrid"></div>
    </div>

    <!-- ─── BLOOD DONORS TAB ─── -->
    <div class="tab-panel" id="tab-donors">
        <div class="filter-bar">
            <select id="bloodFilter">
                <option value="">All Blood Groups</option>
                <option>A+</option><option>A-</option>
                <option>B+</option><option>B-</option>
                <option>O+</option><option>O-</option>
                <option>AB+</option><option>AB-</option>
            </select>
        </div>
        <div class="blood-donor-grid" id="donorGrid"></div>
    </div>

    <!-- ─── TEST REPORTS TAB ─── -->
    <div class="tab-panel" id="tab-reports">
        <div class="section-header">
            <h2>View Test Report</h2>
            <p>Enter your report code to instantly retrieve your lab results.</p>
        </div>
        <div class="report-lookup">
            <input type="text" id="reportCode" placeholder="e.g. RPT-2026-00123">
            <button class="btn btn-primary" onclick="lookupReport()"><i class="fa-solid fa-search"></i> Lookup</button>
        </div>
        <div class="report-result" id="reportResult">
            <h4><i class="fa-solid fa-file-medical" style="color:var(--primary-color);"></i> Report Found</h4>
            <p><strong>Patient:</strong> John Doe &nbsp;|&nbsp; <strong>Test:</strong> Complete Blood Count</p>
            <p><strong>Date:</strong> May 10, 2026 &nbsp;|&nbsp; <strong>Lab:</strong> City Diagnostics</p>
            <div style="margin-top:14px;"><a href="#" class="btn btn-secondary btn-sm"><i class="fa-solid fa-download"></i> Download PDF</a></div>
        </div>
    </div>

    <!-- ─── AMBULANCE TAB ─── -->
    <div class="tab-panel" id="tab-ambulance">
        <div class="ambulance-hero">
            <div>
                <h2><i class="fa-solid fa-truck-medical"></i> Emergency Ambulance</h2>
                <p>Call the nearest government or private ambulance instantly.</p>
            </div>
            <a href="tel:911" class="btn btn-emergency"><i class="fa-solid fa-phone-volume"></i> Call 911</a>
        </div>
        <div class="ambulance-grid" id="ambulanceGrid"></div>
    </div>

</div><!-- /section-container -->

<!-- Footer -->
<footer class="guest-footer">
    <div style="font-size:1.1rem; font-weight:700;"><i class="fa-solid fa-truck-medical" style="color:var(--primary-color);"></i> MPES</div>
    <div class="emerg-numbers">
        <div>🚑 Ambulance: <span>911</span></div>
        <div>🔥 Fire: <span>101</span></div>
        <div>👮 Police: <span>999</span></div>
        <div>📞 MPES Hotline: <span>+1-800-MPES-911</span></div>
    </div>
    <div style="font-size:0.82rem; color:var(--text-secondary); margin-top:10px;">
        <a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Terms & Conditions</a> &nbsp;|&nbsp;
        &copy; 2026 Medical Primary Emergency System. All rights reserved.
    </div>
</footer>

<script src="../assets/js/main.js"></script>
<script>
// ── Demo data ──────────────────────────────────────────────
const DOCTORS = [
    { name:'Dr. Sarah Ahmed',    specialty:'Cardiologist',        hospital:'City Hospital',   fee:80,  exp:12, rating:4.8, img:'https://i.pravatar.cc/80?img=47' },
    { name:'Dr. Karim Hossain',  specialty:'Neurologist',         hospital:'Apollo Hospital', fee:100, exp:15, rating:4.9, img:'https://i.pravatar.cc/80?img=12' },
    { name:'Dr. Nadia Islam',    specialty:'Dermatologist',       hospital:'LifeCare Clinic', fee:60,  exp:8,  rating:4.6, img:'https://i.pravatar.cc/80?img=48' },
    { name:'Dr. Rahman Khan',    specialty:'General Physician',   hospital:'MedPlus',         fee:40,  exp:20, rating:4.5, img:'https://i.pravatar.cc/80?img=33' },
    { name:'Dr. Priya Singh',    specialty:'Orthopedic',          hospital:'BoneCare',        fee:90,  exp:11, rating:4.7, img:'https://i.pravatar.cc/80?img=44' },
    { name:'Dr. Ali Hassan',     specialty:'Cardiologist',        hospital:'HeartCare',       fee:120, exp:18, rating:5.0, img:'https://i.pravatar.cc/80?img=52' },
];
const MEDICINES = [
    { name:'Paracetamol 500mg', company:'MedCo',      price:4.50,  type:'Tablet' },
    { name:'Amoxicillin 250mg', company:'PharmaCorp', price:12.00, type:'Capsule' },
    { name:'Omeprazole 20mg',   company:'GastroMed',  price:8.75,  type:'Capsule' },
    { name:'Amlodipine 5mg',    company:'CardioPharm',price:6.20,  type:'Tablet' },
    { name:'Metformin 500mg',   company:'DiaCare',    price:5.50,  type:'Tablet' },
    { name:'Ibuprofen 400mg',   company:'PainRelief', price:3.80,  type:'Tablet' },
    { name:'Cetirizine 10mg',   company:'AllerCure',  price:7.00,  type:'Tablet' },
    { name:'Azithromycin 500mg',company:'BioPharm',   price:18.50, type:'Tablet' },
];
const DONORS = [
    { name:'Ahmed Rafi',    blood:'A+', phone:'+1 555 0101', city:'New York' },
    { name:'Sonia Begum',   blood:'B+', phone:'+1 555 0202', city:'Brooklyn' },
    { name:'Mark Johnson',  blood:'O-', phone:'+1 555 0303', city:'Manhattan' },
    { name:'Lisa Chen',     blood:'AB+',phone:'+1 555 0404', city:'Queens' },
    { name:'David Park',    blood:'A-', phone:'+1 555 0505', city:'Bronx' },
    { name:'Fatima Al-Ali', blood:'O+', phone:'+1 555 0606', city:'Jersey City' },
];
const AMBULANCES = [
    { name:'City General Ambulance', type:'Government', phone:'911',         status:'Available', eta:'5 min' },
    { name:'Apollo Care Transport',  type:'Private',    phone:'+1 555 2000', status:'Busy',      eta:'12 min' },
    { name:'LifeLine EMS',           type:'Private',    phone:'+1 555 3000', status:'Available', eta:'8 min' },
    { name:'MedRush Services',       type:'Government', phone:'911',         status:'Available', eta:'3 min' },
];

function starRating(r) {
    let s = '';
    for(let i=1;i<=5;i++) s += `<i class="fa-star ${i<=Math.floor(r)?'fa-solid':'fa-regular'}"></i>`;
    return `<div class="rating">${s} <small style="color:var(--text-secondary)">(${r})</small></div>`;
}

function renderDoctors(list) {
    const grid = document.getElementById('doctorGrid');
    grid.innerHTML = list.map(d => `
        <div class="doctor-card">
            <img src="${d.img}" alt="${d.name}">
            <h4>${d.name}</h4>
            <p>${d.specialty} · ${d.hospital}</p>
            ${starRating(d.rating)}
            <div class="fee">$${d.fee} / visit &nbsp;·&nbsp; ${d.exp} yrs exp</div>
            <button class="btn btn-primary btn-sm w-100" onclick="alert('Please login to book an appointment.')"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</button>
        </div>`).join('');
}

function renderMedicines(list) {
    document.getElementById('medicineGrid').innerHTML = list.map(m => `
        <div class="medicine-card">
            <div style="font-size:1.8rem; margin-bottom:6px;">💊</div>
            <h4 style="font-size:0.95rem; margin:0;">${m.name}</h4>
            <p style="font-size:0.8rem; color:var(--text-secondary); margin:4px 0;">${m.company} · ${m.type}</p>
            <div class="price">$${m.price.toFixed(2)}</div>
            <button class="btn btn-outline btn-sm w-100" style="margin-top:10px;" onclick="alert('Please login to order medicines.')">Order Now</button>
        </div>`).join('');
}

function renderDonors(list) {
    document.getElementById('donorGrid').innerHTML = list.map(d => `
        <div class="donor-card">
            <div class="blood-badge-large">${d.blood}</div>
            <h4>${d.name}</h4>
            <p style="font-size:0.82rem; color:var(--text-secondary);">${d.city}</p>
            <a href="tel:${d.phone}" class="btn btn-danger btn-sm" style="margin-top:10px;"><i class="fa-solid fa-phone"></i> ${d.phone}</a>
        </div>`).join('');
}

function renderAmbulances() {
    document.getElementById('ambulanceGrid').innerHTML = AMBULANCES.map(a => `
        <div class="ambulance-card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <h4>${a.name}</h4>
                <span class="badge-pill ${a.status==='Available'?'success':'warning'}">${a.status}</span>
            </div>
            <p>${a.type} · ETA: <strong>${a.eta}</strong></p>
            <a href="tel:${a.phone}" class="btn btn-danger btn-sm" style="margin-top:12px;"><i class="fa-solid fa-phone-volume"></i> Call ${a.phone}</a>
        </div>`).join('');
}

// ── Tab switching ───────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    event.currentTarget.classList.add('active');
}

// ── Filters ─────────────────────────────────────────────────
document.getElementById('drSearch').addEventListener('input', function() {
    renderDoctors(DOCTORS.filter(d => d.name.toLowerCase().includes(this.value.toLowerCase())));
});
document.getElementById('drSpecialist').addEventListener('change', function() {
    renderDoctors(this.value ? DOCTORS.filter(d => d.specialty === this.value) : DOCTORS);
});
document.getElementById('medSearch').addEventListener('input', function() {
    renderMedicines(MEDICINES.filter(m => m.name.toLowerCase().includes(this.value.toLowerCase())));
});
document.getElementById('medSort').addEventListener('change', function() {
    let list = [...MEDICINES];
    if(this.value==='price_asc')  list.sort((a,b)=>a.price-b.price);
    if(this.value==='price_desc') list.sort((a,b)=>b.price-a.price);
    if(this.value==='name')       list.sort((a,b)=>a.name.localeCompare(b.name));
    renderMedicines(list);
});
document.getElementById('bloodFilter').addEventListener('change', function() {
    renderDonors(this.value ? DONORS.filter(d=>d.blood===this.value) : DONORS);
});

// ── Report lookup ────────────────────────────────────────────
function lookupReport() {
    const code = document.getElementById('reportCode').value.trim();
    const result = document.getElementById('reportResult');
    if(code) result.style.display = 'block';
}

// ── Guest quick login ────────────────────────────────────────
function guestLogin() {
    const name = document.getElementById('guestName').value.trim();
    const phone = document.getElementById('guestPhone').value.trim();
    if(name && phone) {
        document.getElementById('guestModal').classList.remove('open');
        alert('Welcome, ' + name + '! You now have guest access.');
    } else { alert('Please enter both name and phone number.'); }
}

// ── Init ─────────────────────────────────────────────────────
renderDoctors(DOCTORS);
renderMedicines(MEDICINES);
renderDonors(DONORS);
renderAmbulances();
</script>
</body>
</html>
