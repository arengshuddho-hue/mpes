<?php
// admin/dashboard.php
// Central administration interface. Provides a high-level overview of system metrics,
// user management (CRUD operations for patients, doctors, etc.), and activity logs.

require_once '../config/session.php'; // Session management helper
require_once '../config/db.php';      // Database connection
requireLogin('admin');                // Guard: Only admins can access this page

// Fetch key system metrics to populate the dashboard stat cards
$patientsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$doctorsCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$hospitalsCount = $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$ambulancesCount = $pdo->query("SELECT COUNT(*) FROM ambulances")->fetchColumn();

// Fetch initial data sets to be injected directly into JavaScript for fast client-side rendering
$patientsData = $pdo->query("SELECT CONCAT('PT-', id), name, email, COALESCE(blood_group, 'N/A'), COALESCE(phone, 'N/A'), 'Active' FROM users WHERE role = 'patient' ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_NUM);
$doctorsData = $pdo->query("SELECT CONCAT('DR-', u.id), u.name, COALESCE(d.specialist, 'General'), COALESCE(h.name, 'N/A'), u.status, u.id FROM users u LEFT JOIN doctor_details d ON u.id = d.user_id LEFT JOIN hospitals h ON d.hospital_id = h.id WHERE u.role = 'doctor' ORDER BY u.id DESC LIMIT 50")->fetchAll(PDO::FETCH_NUM);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .nav-section{padding:18px 0 4px 20px;font-size:.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px}
        .section-heading{font-size:1.02rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:10px}
        .section-heading i{color:var(--primary-color)}
        .view-all{font-size:.82rem;color:var(--primary-color);text-decoration:none;margin-left:auto}
        .admin-stat::before{background:var(--primary-color)}
        .admin-stat:nth-child(2)::before{background:#38a169}
        .admin-stat:nth-child(3)::before{background:#ed8936}
        .admin-stat:nth-child(4)::before{background:#e53e3e}
        .tab-nav{display:flex;gap:4px;background:var(--input-bg);border-radius:10px;padding:4px;margin-bottom:20px;flex-wrap:wrap}
        .tab-btn{padding:8px 18px;border:none;border-radius:7px;cursor:pointer;font-weight:600;font-size:.85rem;background:transparent;color:var(--text-secondary);transition:all .2s}
        .tab-btn.active{background:var(--card-bg);color:var(--primary-color);box-shadow:var(--shadow-sm)}
        .tab-pane{display:none}.tab-pane.active{display:block}
        .action-btn{padding:5px 12px;border-radius:6px;border:none;cursor:pointer;font-size:.78rem;font-weight:600;transition:all .2s}
        .action-btn.edit{background:rgba(43,108,176,.1);color:var(--primary-color)}
        .action-btn.edit:hover{background:var(--primary-color);color:#fff}
        .action-btn.del{background:rgba(229,62,62,.1);color:#e53e3e}
        .action-btn.del:hover{background:#e53e3e;color:#fff}
        .action-btn.view{background:rgba(56,161,105,.1);color:#38a169}
        .action-btn.view:hover{background:#38a169;color:#fff}
        .add-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:var(--primary-color);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:.85rem;margin-bottom:16px;transition:all .2s}
        .add-btn:hover{background:var(--primary-hover);transform:translateY(-2px)}
        .chart-container{position:relative;height:260px}
        .activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--input-border)}
        .activity-item:last-child{border-bottom:none}
        .activity-icon{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
        .ai-blue{background:rgba(43,108,176,.15);color:var(--primary-color)}
        .ai-green{background:rgba(56,161,105,.15);color:#38a169}
        .ai-orange{background:rgba(237,137,54,.15);color:#ed8936}
        .ai-red{background:rgba(229,62,62,.15);color:#e53e3e}
        .activity-text p{margin:0;font-size:.88rem;font-weight:500}
        .activity-text span{font-size:.75rem;color:var(--text-secondary)}
        .ambulance-status{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--input-bg);border-radius:10px;margin-bottom:8px}
        .amb-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
        .amb-dot.available{background:#38a169;box-shadow:0 0 6px #38a169}
        .amb-dot.busy{background:#e53e3e;box-shadow:0 0 6px #e53e3e}
        .amb-dot.maintenance{background:#ed8936;box-shadow:0 0 6px #ed8936}
    </style>
</head>
<body class="dashboard-body">

<aside class="sidebar" id="sidebar">
    <a class="sidebar-logo" href="dashboard.html">
        <i class="fa-solid fa-shield-halved logo-icon"></i>
        <span class="logo-text">MPES Admin</span>
    </a>
    <p class="nav-section">OVERVIEW</p>
    <ul class="sidebar-menu">
        <li><a href="dashboard.html" class="active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></li>
        <li><a href="#analyticsSection"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></li>
        <li><a href="#activitySection"><i class="fa-solid fa-clock-rotate-left"></i><span>Activity Log</span></a></li>
    </ul>
    <p class="nav-section">MANAGE</p>
    <ul class="sidebar-menu">
        <li><a href="#crudSection"><i class="fa-solid fa-users-gear"></i><span>Users &amp; Doctors</span></a></li>
        <li><a href="#crudSection"><i class="fa-solid fa-hospital"></i><span>Hospitals</span></a></li>
        <li><a href="#crudSection"><i class="fa-solid fa-truck-medical"></i><span>Ambulances</span></a></li>
        <li><a href="#crudSection"><i class="fa-solid fa-pills"></i><span>Medicines</span></a></li>
    </ul>
    <p class="nav-section">SYSTEM</p>
    <ul class="sidebar-menu">
        <li><a href="#"><i class="fa-solid fa-gear"></i><span>Settings</span></a></li>
        <li><a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</aside>

<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button class="icon-btn" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="page-title"><h1>Admin Control Panel</h1><p>Full System Access</p></div>
        </div>
        <div class="topbar-right">
            <div class="topbar-search"><i class="fa-solid fa-search"></i><input type="text" placeholder="Search anything..."></div>
            <div class="theme-switcher-row">
                <button class="theme-btn" data-set-theme="light" title="Light"><i class="fa-solid fa-sun"></i></button>
                <button class="theme-btn" data-set-theme="dark" title="Dark"><i class="fa-solid fa-moon"></i></button>
                <button class="theme-btn" data-set-theme="colorblind" title="Color-Blind"><i class="fa-solid fa-eye-low-vision"></i></button>
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
                        <a href="../patient/settings.html">Notification Settings</a>
                    </div>
                </div>
            </div>
            <div class="user-pill">
                <img src="https://i.pravatar.cc/40?img=3" alt="Admin">
                <span>Admin</span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary);"></i>
                <div class="dropdown-menu">
                    <a href="#"><i class="fa-solid fa-user-shield"></i> Admin Profile</a>
                    <a href="#"><i class="fa-solid fa-gear"></i> System Settings</a>
                    <a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="page-content">

        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card admin-stat">
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $patientsCount ?>">0</h2>
                    <p>Total Patients</p>
                    <div class="stat-trend"><i class="fa-solid fa-arrow-up"></i> Live DB Sync</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon green"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $doctorsCount ?>">0</h2>
                    <p>Active Doctors</p>
                    <div class="stat-trend" style="color:#38a169;"><i class="fa-solid fa-arrow-up"></i> Live DB Sync</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon orange"><i class="fa-solid fa-hospital"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $hospitalsCount ?>">0</h2>
                    <p>Hospitals Registered</p>
                    <div class="stat-trend" style="color:#ed8936;">Live DB Sync</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon red"><i class="fa-solid fa-truck-medical"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $ambulancesCount ?>">0</h2>
                    <p>Ambulances</p>
                    <div class="stat-trend" style="color:#e53e3e;">Live DB Sync</div>
                </div>
            </div>
        </div>

        <!-- Analytics Charts -->
        <div class="grid-2" id="analyticsSection">
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-chart-bar"></i> Monthly Appointments</div>
                <div class="chart-container"><canvas id="apptChart"></canvas></div>
            </div>
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-chart-pie"></i> User Role Distribution</div>
                <div class="chart-container"><canvas id="roleChart"></canvas></div>
            </div>
        </div>

        <!-- CRUD Tabs -->
        <div class="card mt-4" id="crudSection">
            <div class="section-heading"><i class="fa-solid fa-database"></i> Data Management</div>
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('patients',this)">Patients</button>
                <button class="tab-btn" onclick="switchTab('doctors',this)">Doctors</button>
                <button class="tab-btn" onclick="switchTab('hospitals',this)">Hospitals</button>
                <button class="tab-btn" onclick="switchTab('ambulances',this)">Ambulances</button>
                <button class="tab-btn" onclick="switchTab('medicines',this)">Medicines</button>
            </div>

            <!-- Patients Tab -->
            <div class="tab-pane active" id="tab-patients">
                <button class="add-btn" onclick="openAddUserModal('patient')"><i class="fa-solid fa-plus"></i> Add Patient</button>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Blood</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="patientsTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Doctors Tab -->
            <div class="tab-pane" id="tab-doctors">
                <button class="add-btn" onclick="openAddUserModal('doctor')"><i class="fa-solid fa-plus"></i> Add Doctor</button>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Specialty</th><th>Hospital</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="doctorsTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Hospitals Tab -->
            <div class="tab-pane" id="tab-hospitals">
                <button class="add-btn" onclick="alert('Add Hospital form coming soon!')"><i class="fa-solid fa-plus"></i> Add Hospital</button>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Location</th><th>Beds</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="hospitalsTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Ambulances Tab -->
            <div class="tab-pane" id="tab-ambulances">
                <button class="add-btn" onclick="alert('Add Ambulance form coming soon!')"><i class="fa-solid fa-plus"></i> Add Ambulance</button>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>ID</th><th>Vehicle No.</th><th>Type</th><th>Driver</th><th>Hospital</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="ambulancesTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Medicines Tab -->
            <div class="tab-pane" id="tab-medicines">
                <button class="add-btn" onclick="alert('Add Medicine form coming soon!')"><i class="fa-solid fa-plus"></i> Add Medicine</button>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Stock</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="medicinesTable"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Activity + Ambulance Status -->
        <div class="grid-2 mt-4">
            <div class="card" id="activitySection">
                <div class="section-heading"><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</div>
                <div id="activityLog"></div>
            </div>
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-truck-medical"></i> Ambulance Fleet Status</div>
                <div id="ambulanceStatus"></div>
            </div>
        </div>

    </main>
</div>

<!-- ADD USER MODAL -->
<div class="modal-overlay" id="addUserModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-box" style="background:var(--card-bg); padding:30px; border-radius:12px; width:100%; max-width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:20px;"><i class="fa-solid fa-user-plus" style="color:var(--primary-color);"></i> Add New <span id="addUserRoleText">User</span></h3>
        <input type="hidden" id="addUserRole">
        <div class="form-group" style="margin-bottom:14px;">
            <label style="display:block; margin-bottom:6px; font-size:0.85rem; color:var(--text-secondary);">Full Name</label>
            <input type="text" class="form-control" id="addUserName" style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary);">
        </div>
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:6px; font-size:0.85rem; color:var(--text-secondary);">Email Address</label>
            <input type="email" class="form-control" id="addUserEmail" style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--text-primary);">
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" style="flex:1; padding:10px; border:none; border-radius:6px; background:var(--primary-color); color:#fff; cursor:pointer;" onclick="submitAddUser()">Save User</button>
            <button class="btn btn-outline" style="flex:1; padding:10px; border:1px solid var(--input-border); border-radius:6px; background:transparent; color:var(--text-primary); cursor:pointer;" onclick="document.getElementById('addUserModal').style.display='none'">Cancel</button>
        </div>
    </div>
</div>

<script>
// Theme
const html=document.documentElement;
const saved=localStorage.getItem('mpes-theme')||'light';
html.setAttribute('data-theme',saved);
document.querySelectorAll('.theme-btn').forEach(btn=>{
    if(btn.dataset.setTheme===saved)btn.classList.add('active');
    btn.addEventListener('click',()=>{
        const t=btn.dataset.setTheme;
        html.setAttribute('data-theme',t);
        localStorage.setItem('mpes-theme',t);
        document.querySelectorAll('.theme-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
    });
});

// Sidebar
const sidebar=document.getElementById('sidebar'),mainWrapper=document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click',()=>{
    if(window.innerWidth<=768){sidebar.classList.toggle('mobile-open');}
    else{sidebar.classList.toggle('collapsed');mainWrapper.classList.toggle('sidebar-collapsed');}
});

// Counters
document.querySelectorAll('.counter').forEach(el=>{
    const target=+el.dataset.target;let count=0;
    const step=Math.ceil(target/40);
    const iv=setInterval(()=>{count+=step;if(count>=target){count=target;clearInterval(iv);}el.textContent=count.toLocaleString();},40);
});

// Tab switching
function switchTab(name,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    btn.classList.add('active');
}

// Charts
const isDark=()=>html.getAttribute('data-theme')==='dark';
const gridColor=()=>isDark()?'rgba(255,255,255,0.08)':'rgba(0,0,0,0.07)';
const textColor=()=>isDark()?'#a0aec0':'#718096';

const apptCtx=document.getElementById('apptChart').getContext('2d');
new Chart(apptCtx,{
    type:'bar',
    data:{
        labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets:[
            {label:'Patients',data:[320,410,380,500,620,580,700,650,720,680,760,840],backgroundColor:'rgba(43,108,176,0.7)',borderRadius:6},
            {label:'Doctors',data:[80,95,88,105,130,120,145,138,150,140,160,175],backgroundColor:'rgba(56,161,105,0.7)',borderRadius:6}
        ]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:textColor()}}},scales:{x:{grid:{color:gridColor()},ticks:{color:textColor()}},y:{grid:{color:gridColor()},ticks:{color:textColor()}}}}
});

const roleCtx=document.getElementById('roleChart').getContext('2d');
new Chart(roleCtx,{
    type:'doughnut',
    data:{
        labels:['Patients','Doctors','Admins','Guests'],
        datasets:[{data:[<?= $patientsCount ?>, <?= $doctorsCount ?>, 1, 0],backgroundColor:['rgba(43,108,176,0.8)','rgba(56,161,105,0.8)','rgba(229,62,62,0.8)','rgba(237,137,54,0.8)'],borderWidth:0,hoverOffset:8}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:textColor(),padding:16}}}}
});

// CRUD Data
const actBtns=(id)=>`<button class="action-btn view" onclick="alert('View #${id}')"><i class="fa-solid fa-eye"></i></button> <button class="action-btn edit" onclick="alert('Edit #${id}')"><i class="fa-solid fa-pen"></i></button> <button class="action-btn del" onclick="deletRow(this,'${id}')"><i class="fa-solid fa-trash"></i></button>`;

const PATIENTS_DATA = <?= json_encode($patientsData) ?>;
const sc={'Active':'<span class="badge-pill success">Active</span>','Inactive':'<span class="badge-pill danger">Inactive</span>'};
document.getElementById('patientsTable').innerHTML=PATIENTS_DATA.map(r=>`<tr><td>${r[0]}</td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td>${r[4]}</td><td>${sc[r[5]]}</td><td>${actBtns(r[0])}</td></tr>`).join('');

const DOCTORS_DATA = <?= json_encode($doctorsData) ?>;
const ds = {
    'approved': '<span class="badge-pill success">Approved</span>',
    'pending': '<span class="badge-pill warning">Pending</span>',
    'rejected': '<span class="badge-pill danger">Rejected</span>'
};

function docActions(id, status) {
    let act = '';
    if (status === 'pending') {
        act += `<button class="action-btn view" onclick="updateDocStatus(${id}, 'approved')" title="Approve"><i class="fa-solid fa-check"></i></button> `;
        act += `<button class="action-btn del" onclick="updateDocStatus(${id}, 'rejected')" title="Reject"><i class="fa-solid fa-xmark"></i></button> `;
    } else if (status === 'approved') {
        act += `<button class="action-btn del" onclick="updateDocStatus(${id}, 'rejected')" title="Reject"><i class="fa-solid fa-xmark"></i></button> `;
    } else if (status === 'rejected') {
        act += `<button class="action-btn view" onclick="updateDocStatus(${id}, 'approved')" title="Approve"><i class="fa-solid fa-check"></i></button> `;
    }
    act += `<button class="action-btn del" onclick="deletRow(this,'DR-${id}')" title="Delete User"><i class="fa-solid fa-trash"></i></button>`;
    return act;
}

document.getElementById('doctorsTable').innerHTML = DOCTORS_DATA.map(r => `
    <tr>
        <td>${r[0]}</td>
        <td>${r[1]}</td>
        <td>${r[2]}</td>
        <td>${r[3]}</td>
        <td>${ds[r[4]] || r[4]}</td>
        <td>${docActions(r[5], r[4])}</td>
    </tr>
`).join('');

async function updateDocStatus(userId, status) {
    if (!confirm('Are you sure you want to change this doctor\'s status to ' + status + '?')) return;
    
    const params = new URLSearchParams();
    params.append('action', 'update_doctor_status');
    params.append('user_id', userId);
    params.append('status', status);

    try {
        const res = await fetch('../api/crud.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });
        
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch(e) {
            alert('Server returned invalid response: ' + text.substring(0, 50));
            return;
        }

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Failed to connect to server: ' + e.message);
    }
}

const HOSPITALS_DATA=[
    ['HOS-001','City General Hospital','Government','New York','480','<span class="badge-pill success">Open</span>'],
    ['HOS-002','Apollo Medical Center','Private','New York','320','<span class="badge-pill success">Open</span>'],
    ['HOS-003','LifeCare Clinic','Private','Brooklyn','150','<span class="badge-pill success">Open</span>'],
    ['HOS-004','Metro Emergency Hospital','Government','Queens','600','<span class="badge-pill warning">Limited</span>'],
];
document.getElementById('hospitalsTable').innerHTML=HOSPITALS_DATA.map(r=>`<tr><td>${r[0]}</td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td>${r[4]}</td><td>${r[5]}</td><td>${actBtns(r[0])}</td></tr>`).join('');

const AMB_DATA=[
    ['AMB-001','NY-A-1001','Government','James Wilson','City Hospital','<span class="badge-pill success">Available</span>'],
    ['AMB-002','NY-P-2002','Private','Maria Lopez','Apollo Center','<span class="badge-pill danger">On Call</span>'],
    ['AMB-003','NY-A-1003','Government','David Kim','Metro Hospital','<span class="badge-pill success">Available</span>'],
    ['AMB-004','NY-P-2004','Private','Sara Ahmed','LifeCare Clinic','<span class="badge-pill warning">Maintenance</span>'],
];
document.getElementById('ambulancesTable').innerHTML=AMB_DATA.map(r=>`<tr><td>${r[0]}</td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td>${r[4]}</td><td>${r[5]}</td><td>${actBtns(r[0])}</td></tr>`).join('');

const MED_DATA=[
    ['MED-001','Paracetamol 500mg','Analgesic','1200 units','$0.50','<span class="badge-pill success">In Stock</span>'],
    ['MED-002','Amoxicillin 250mg','Antibiotic','680 units','$1.20','<span class="badge-pill success">In Stock</span>'],
    ['MED-003','Omeprazole 20mg','Antacid','340 units','$0.80','<span class="badge-pill warning">Low Stock</span>'],
    ['MED-004','Metformin 500mg','Antidiabetic','920 units','$0.60','<span class="badge-pill success">In Stock</span>'],
    ['MED-005','Amlodipine 5mg','Antihypertensive','0 units','$1.50','<span class="badge-pill danger">Out of Stock</span>'],
];
document.getElementById('medicinesTable').innerHTML=MED_DATA.map(r=>`<tr><td>${r[0]}</td><td>${r[1]}</td><td>${r[2]}</td><td>${r[3]}</td><td>${r[4]}</td><td>${r[5]}</td><td>${actBtns(r[0])}</td></tr>`).join('');

// Activity Log
const ACTIVITIES=[
    {icon:'fa-user-plus',cls:'ai-blue',text:'New patient John Doe registered',time:'2 mins ago'},
    {icon:'fa-file-prescription',cls:'ai-green',text:'Dr. Sarah Ahmed issued prescription #RX-1048',time:'15 mins ago'},
    {icon:'fa-truck-medical',cls:'ai-red',text:'Ambulance AMB-002 dispatched — Emergency call',time:'32 mins ago'},
    {icon:'fa-calendar-check',cls:'ai-blue',text:'Appointment #1245 confirmed — Dr. Karim',time:'1 hour ago'},
    {icon:'fa-user-doctor',cls:'ai-green',text:'Dr. Nadia Islam profile updated',time:'2 hours ago'},
    {icon:'fa-pills',cls:'ai-orange',text:'Omeprazole stock running low (340 units)',time:'3 hours ago'},
    {icon:'fa-hospital',cls:'ai-blue',text:'City Hospital bed capacity updated to 480',time:'5 hours ago'},
];
document.getElementById('activityLog').innerHTML=ACTIVITIES.map(a=>`
    <div class="activity-item">
        <div class="activity-icon ${a.cls}"><i class="fa-solid ${a.icon}"></i></div>
        <div class="activity-text"><p>${a.text}</p><span>${a.time}</span></div>
    </div>`).join('');

// Ambulance Status
const AMB_STATUS=[
    {id:'AMB-001',no:'NY-A-1001',status:'available',label:'Available'},
    {id:'AMB-002',no:'NY-P-2002',status:'busy',label:'On Call'},
    {id:'AMB-003',no:'NY-A-1003',status:'available',label:'Available'},
    {id:'AMB-004',no:'NY-P-2004',status:'maintenance',label:'Maintenance'},
    {id:'AMB-005',no:'NY-A-1005',status:'available',label:'Available'},
    {id:'AMB-006',no:'NY-P-2006',status:'busy',label:'On Call'},
];
document.getElementById('ambulanceStatus').innerHTML=AMB_STATUS.map(a=>`
    <div class="ambulance-status">
        <div class="amb-dot ${a.status}"></div>
        <div style="flex:1"><strong>${a.id}</strong> — ${a.no}</div>
        <span class="badge-pill ${a.status==='available'?'success':a.status==='busy'?'danger':'warning'}">${a.label}</span>
    </div>`).join('');

function deletRow(btn,id){
    if(confirm('Delete record '+id+'?')){btn.closest('tr').remove();}
}

function openAddUserModal(role) {
    document.getElementById('addUserRole').value = role;
    document.getElementById('addUserRoleText').innerText = role.charAt(0).toUpperCase() + role.slice(1);
    document.getElementById('addUserModal').style.display = 'flex';
}

async function submitAddUser() {
    const role = document.getElementById('addUserRole').value;
    const name = document.getElementById('addUserName').value;
    const email = document.getElementById('addUserEmail').value;

    if(!name || !email) { alert("Name and Email required."); return; }

    const formData = new FormData();
    formData.append('action', 'add_user');
    formData.append('role', role);
    formData.append('name', name);
    formData.append('email', email);

    try {
        const res = await fetch('../api/crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Failed to connect to server.');
    }
}
</script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
