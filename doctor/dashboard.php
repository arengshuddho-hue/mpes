<?php
// doctor/dashboard.php
// Main interface for doctors. Handles fetching doctor-specific statistics,
// recent patient data, and provides the UI for appointments and prescriptions.

require_once '../config/session.php'; // Load session security helper
require_once '../config/db.php';      // Load database connection
requireLogin('doctor');               // Restrict access to logged-in doctors only

$doctor_id = $_SESSION['user_id'];

// Retrieve the logged-in doctor's name for the header
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();
$doctorName = $doctor['name'] ?? 'Doctor';

// Fetch key metrics to display on the dashboard stat cards
$totalPatients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$rxCount = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = $doctor_id")->fetchColumn();
$apptCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE doctor_id = $doctor_id")->fetchColumn();

// Fetch dynamic appointments list for queue and schedule
$stmtAppts = $pdo->prepare("
    SELECT a.id, a.serial_number, a.appointment_date, a.appointment_time, a.status, a.notes,
           u.id AS patient_id, u.name AS patient_name, u.phone AS patient_phone, u.blood_group AS patient_blood, u.profile_picture
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = ? AND a.status != 'cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmtAppts->execute([$doctor_id]);
$doctorAppointments = $stmtAppts->fetchAll(PDO::FETCH_ASSOC);

// Fetch a small list of recent patients for the 'My Patients' quick-view section
$recentPatientsData = $pdo->query("SELECT name, 30 as age, COALESCE(blood_group, 'N/A') as blood, 'Recently' as last, 'https://i.pravatar.cc/40' as img FROM users WHERE role = 'patient' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all patients to populate the prescription writer dropdown
$allPatients = $pdo->query("SELECT id, name FROM users WHERE role = 'patient'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-section{padding:18px 0 4px 20px;font-size:.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px}
        .section-heading{font-size:1.02rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:10px}
        .section-heading i{color:var(--primary-color)}
        .view-all{font-size:.82rem;color:var(--primary-color);text-decoration:none;margin-left:auto}
        .patient-row{display:flex;align-items:center;gap:14px;padding:13px 0;border-bottom:1px solid var(--input-border)}
        .patient-row:last-child{border-bottom:none}
        .patient-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--primary-color)}
        .patient-info{flex:1}
        .patient-info h4{margin:0 0 2px;font-size:.92rem}
        .patient-info p{margin:0;font-size:.78rem;color:var(--text-secondary)}
        .queue-number{width:36px;height:36px;border-radius:50%;background:var(--primary-color);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0}
        .rx-form-box{background:var(--card-bg);border:1px solid var(--input-border);border-radius:12px;padding:20px;margin-bottom:14px}
        .drug-row{display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
        .drug-row input,.drug-row select{flex:1;min-width:100px}
        .remove-drug{background:#e53e3e;color:#fff;border:none;border-radius:6px;padding:8px 12px;cursor:pointer;font-size:.8rem}
        .schedule-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--input-border)}
        .schedule-time{font-weight:700;color:var(--primary-color);font-size:.88rem;min-width:65px}
        .stat-card::before{background:var(--primary-color)}
        .stat-card:nth-child(2)::before{background:#38a169}
        .stat-card:nth-child(3)::before{background:#ed8936}
        .stat-card:nth-child(4)::before{background:#805ad5}
        .search-patient-box{background:linear-gradient(135deg,#2b6cb0,#1a365d);border-radius:14px;padding:22px;color:#fff;margin-bottom:24px}
        .search-patient-box h3{margin:0 0 14px}
        .search-row{display:flex;gap:10px}
        .search-row input{flex:1;padding:10px 16px;border-radius:20px;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.12);color:#fff;outline:none;font-size:.9rem}
        .search-row input::placeholder{color:rgba(255,255,255,.6)}
        .patient-result-card{background:var(--card-bg);border:1px solid var(--input-border);border-radius:12px;padding:18px;margin-top:14px;display:none}
        .p-result-header{display:flex;align-items:center;gap:14px;margin-bottom:14px}
        .p-result-header img{width:60px;height:60px;border-radius:50%;object-fit:cover;border:3px solid var(--primary-color)}
        .vitals-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:12px}
        .vital-box{background:var(--input-bg);border-radius:10px;padding:10px;text-align:center}
        .vital-box .val{font-size:1.1rem;font-weight:700;color:var(--primary-color)}
        .vital-box .lbl{font-size:.7rem;color:var(--text-secondary);margin-top:2px}
    </style>
</head>
<body class="dashboard-body">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <a class="sidebar-logo" href="dashboard.html">
        <i class="fa-solid fa-stethoscope logo-icon"></i>
        <span class="logo-text">MPES</span>
    </a>
    <p class="nav-section">MAIN</p>
    <ul class="sidebar-menu">
        <li><a href="dashboard.html" class="active"><i class="fa-solid fa-house-medical"></i><span>Dashboard</span></a></li>
        <li><a href="#apptSection"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a></li>
        <li><a href="#patientsSection"><i class="fa-solid fa-users"></i><span>My Patients</span></a></li>
        <li><a href="#prescriptionSection"><i class="fa-solid fa-file-prescription"></i><span>Prescriptions</span></a></li>
    </ul>
    <p class="nav-section">TOOLS</p>
    <ul class="sidebar-menu">
        <li><a href="#searchSection"><i class="fa-solid fa-magnifying-glass"></i><span>Search Patient</span></a></li>
        <li><a href="#scheduleSection"><i class="fa-solid fa-clock"></i><span>My Schedule</span></a></li>
        <li><a href="profile.html"><i class="fa-solid fa-id-badge"></i><span>My Profile</span></a></li>
        <li><a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</aside>

<!-- MAIN -->
<div class="main-wrapper" id="mainWrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button class="icon-btn" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="page-title"><h1>Doctor Dashboard</h1><p>Doctor Portal / Dashboard</p></div>
        </div>
        <div class="topbar-right">
            <div class="topbar-search">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search patients...">
            </div>
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
                <img src="../assets/images/default_avatar.svg" alt="Doctor">
                <span><?= htmlspecialchars($doctorName) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary);"></i>
                <div class="dropdown-menu">
                    <a href="profile.html"><i class="fa-solid fa-id-badge"></i> My Profile</a>
                    <a href="#"><i class="fa-solid fa-gear"></i> Settings</a>
                    <a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="page-content">
        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $apptCount ?>">0</h2>
                    <p>Total Appointments</p>
                    <div class="stat-trend"><i class="fa-solid fa-arrow-up"></i> Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $totalPatients ?>">0</h2>
                    <p>System Patients</p>
                    <div class="stat-trend" style="color:#38a169;"><i class="fa-solid fa-arrow-up"></i> Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-file-prescription"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $rxCount ?>">0</h2>
                    <p>Prescriptions Issued</p>
                    <div class="stat-trend" style="color:#ed8936;">Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(128,90,213,.1);color:#805ad5;"><i class="fa-solid fa-star"></i></div>
                <div class="stat-info">
                    <h2>4.8</h2>
                    <p>Average Rating</p>
                    <div class="stat-trend" style="color:#805ad5;">⭐ 128 reviews</div>
                </div>
            </div>
        </div>

        <!-- Search Patient -->
        <div class="search-patient-box" id="searchSection">
            <h3><i class="fa-solid fa-magnifying-glass"></i> Search Patient by Phone / ID</h3>
            <div class="search-row">
                <input type="text" id="patientSearchInput" placeholder="Enter patient phone number or ID...">
                <button class="btn btn-primary" onclick="searchPatient()" style="border-radius:20px;white-space:nowrap;">
                    <i class="fa-solid fa-search"></i> Find Patient
                </button>
            </div>
            <div class="patient-result-card" id="patientResult">
                <div class="p-result-header">
                    <img src="../assets/images/default_avatar.svg" alt="Patient">
                    <div>
                        <h3 style="margin:0 0 4px;">John Doe</h3>
                        <p style="margin:0;font-size:.85rem;color:var(--text-secondary);">ID: PT-001 &nbsp;|&nbsp; Age: 32 &nbsp;|&nbsp; Blood: B+ &nbsp;|&nbsp; Phone: +1-555-0101</p>
                        <span class="badge-pill success" style="margin-top:6px;display:inline-block;">Active Patient</span>
                    </div>
                </div>
                <div class="vitals-grid">
                    <div class="vital-box"><div class="val">120/80</div><div class="lbl">Blood Pressure</div></div>
                    <div class="vital-box"><div class="val">98.6°F</div><div class="lbl">Temperature</div></div>
                    <div class="vital-box"><div class="val">72 bpm</div><div class="lbl">Heart Rate</div></div>
                    <div class="vital-box"><div class="val">98%</div><div class="lbl">SpO2</div></div>
                </div>
                <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('rxModal').classList.add('open')"><i class="fa-solid fa-file-prescription"></i> Write Prescription</button>
                    <button class="btn btn-outline btn-sm"><i class="fa-solid fa-flask-vial"></i> Order Test</button>
                    <button class="btn btn-outline btn-sm"><i class="fa-solid fa-notes-medical"></i> View History</button>
                </div>
            </div>
        </div>

        <!-- Appointment Queue + Schedule -->
        <div class="grid-2" id="apptSection">
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-list-ol"></i> Today's Queue <span class="badge-pill info" style="margin-left:auto;">12 patients</span></div>
                <div id="queueList"></div>
            </div>
            <div class="card" id="scheduleSection">
                <div class="section-heading"><i class="fa-solid fa-clock"></i> Today's Schedule</div>
                <div id="scheduleList"></div>
            </div>
        </div>

        <!-- Patient List + Prescription Writer -->
        <div class="grid-2 mt-4">
            <div class="card" id="patientsSection">
                <div class="section-heading"><i class="fa-solid fa-users"></i> Recent Patients <a href="#" class="view-all">View all →</a></div>
                <div id="recentPatientsList"></div>
            </div>
            <div class="card" id="prescriptionSection">
                <div class="section-heading"><i class="fa-solid fa-file-prescription"></i> Quick Prescription</div>
                <div class="form-group">
                    <label>Patient</label>
                    <select class="form-control" id="rxPatientSelect">
                        <option value="">Select a Patient...</option>
                        <?php foreach($allPatients as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= htmlspecialchars($pt['name']) ?> (PT-00<?= $pt['id'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Diagnosis</label>
                    <input type="text" class="form-control" placeholder="e.g. Acute Rhinitis..." id="rxDiagnosis">
                </div>
                <div id="drugList">
                    <div class="drug-row">
                        <input type="text" class="form-control" placeholder="Medicine name">
                        <input type="text" class="form-control" placeholder="Dose (e.g. 500mg)">
                        <select class="form-control">
                            <option>1x daily</option><option>2x daily</option><option>3x daily</option>
                        </select>
                        <input type="text" class="form-control" placeholder="Days" style="max-width:70px;">
                        <button class="remove-drug" onclick="removeDrug(this)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <button class="btn btn-outline btn-sm" style="margin-bottom:14px;" onclick="addDrug()"><i class="fa-solid fa-plus"></i> Add Medicine</button>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" class="form-control" placeholder="Additional instructions...">
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="btn btn-primary w-100" onclick="issuePrescription()"><i class="fa-solid fa-paper-plane"></i> Issue Prescription</button>
                    <button class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i></button>
                </div>
            </div>
        </div>

        <!-- Reviews Table -->
        <div class="card mt-4">
            <div class="section-heading"><i class="fa-solid fa-star"></i> Patient Reviews</div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Patient</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
                    <tbody>
                        <tr><td>John Doe</td><td>⭐⭐⭐⭐⭐</td><td>Excellent diagnosis and very caring.</td><td>May 14, 2026</td></tr>
                        <tr><td>Maria Garcia</td><td>⭐⭐⭐⭐⭐</td><td>Best doctor I've ever visited!</td><td>May 12, 2026</td></tr>
                        <tr><td>Ahmed Khan</td><td>⭐⭐⭐⭐</td><td>Very professional and thorough.</td><td>May 10, 2026</td></tr>
                        <tr><td>Lily Chen</td><td>⭐⭐⭐⭐⭐</td><td>Explained everything clearly.</td><td>May 8, 2026</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Prescription Modal -->
<div class="modal-overlay" id="rxModal">
    <div class="modal-box">
        <h3 style="margin-bottom:18px;"><i class="fa-solid fa-file-prescription" style="color:var(--primary-color);"></i> Write Prescription — John Doe</h3>
        <div class="form-group"><label>Diagnosis</label><input type="text" class="form-control" placeholder="Diagnosis..."></div>
        <div class="form-group"><label>Medicine</label><input type="text" class="form-control" placeholder="e.g. Paracetamol 500mg — 2x daily — 5 days"></div>
        <div class="form-group"><label>Notes</label><input type="text" class="form-control" placeholder="Rest, hydration..."></div>
        <div style="display:flex;gap:10px;margin-top:16px;">
            <button class="btn btn-primary w-100" onclick="alert('✅ Prescription issued!');document.getElementById('rxModal').classList.remove('open')"><i class="fa-solid fa-check"></i> Issue</button>
            <button class="btn btn-outline" onclick="document.getElementById('rxModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<button class="fab-sos" onclick="document.getElementById('rxModal').classList.add('open')" title="Quick Prescription" style="background:var(--primary-color);">
    <i class="fa-solid fa-file-prescription"></i><span style="font-size:.55rem;">RX</span>
</button>

<script>
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

const sidebar=document.getElementById('sidebar'),mainWrapper=document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click',()=>{
    if(window.innerWidth<=768){sidebar.classList.toggle('mobile-open');}
    else{sidebar.classList.toggle('collapsed');mainWrapper.classList.toggle('sidebar-collapsed');}
});
document.querySelectorAll('.modal-overlay').forEach(o=>{
    o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');});
});
document.querySelectorAll('.counter').forEach(el=>{
    const target=+el.dataset.target;let count=0;
    const step=Math.ceil(target/30);
    const iv=setInterval(()=>{count+=step;if(count>=target){count=target;clearInterval(iv);}el.textContent=count;},50);
});

const QUEUE=[
    <?php foreach ($doctorAppointments as $index => $appt): ?>
    {
        serial: <?= $index + 1 ?>,
        name: <?= json_encode($appt['patient_name']) ?>,
        age: 30,
        issue: <?= json_encode($appt['notes'] ?: 'Check-up') ?>,
        img: '../assets/images/default_avatar.svg'
    },
    <?php endforeach; ?>
];
if (QUEUE.length === 0) {
    document.getElementById('queueList').innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-secondary);"><i class="fa-solid fa-calendar-check" style="font-size:2rem;margin-bottom:8px;opacity:0.5;color:var(--primary-color);"></i><p>No appointments in queue today.</p></div>`;
} else {
    document.getElementById('queueList').innerHTML=QUEUE.map(p=>`
        <div class="patient-row">
            <div class="queue-number">${p.serial}</div>
            <img src="../assets/images/default_avatar.svg" class="patient-avatar" alt="${p.name}">
            <div class="patient-info"><h4>${p.name}</h4><p>Age ${p.age} · ${p.issue}</p></div>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('rxModal').classList.add('open')">See</button>
        </div>`).join('');
}

const SCHEDULE=[
    <?php foreach ($doctorAppointments as $appt): 
        $formattedTime = date('h:i A', strtotime($appt['appointment_time'] ?: $appt['appointment_date']));
        $type = 'Consultation';
        if (stripos($appt['notes'], 'follow') !== false) $type = 'Follow-up';
        elseif (stripos($appt['notes'], 'check') !== false) $type = 'Check-up';
        
        $statusLabel = match($appt['status']) {
            'completed' => 'Done',
            'confirmed' => 'Active',
            'pending' => 'Waiting',
            'cancelled' => 'Scheduled',
            default => 'Waiting'
        };
    ?>
    {
        time: <?= json_encode($formattedTime) ?>,
        name: <?= json_encode($appt['patient_name']) ?>,
        type: <?= json_encode($type) ?>,
        status: <?= json_encode($statusLabel) ?>
    },
    <?php endforeach; ?>
];
const statusColor={'Done':'success','Active':'info','Waiting':'warning','Scheduled':'danger'};
if (SCHEDULE.length === 0) {
    document.getElementById('scheduleList').innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-secondary);"><i class="fa-solid fa-clock" style="font-size:2rem;margin-bottom:8px;opacity:0.5;color:var(--primary-color);"></i><p>No scheduled appointments today.</p></div>`;
} else {
    document.getElementById('scheduleList').innerHTML=SCHEDULE.map(s=>`
        <div class="schedule-item">
            <div class="schedule-time">${s.time}</div>
            <div style="flex:1"><strong>${s.name}</strong><br><span style="font-size:.78rem;color:var(--text-secondary);">${s.type}</span></div>
            <span class="badge-pill ${statusColor[s.status]}">${s.status}</span>
        </div>`).join('');
}

const PATIENTS = <?= json_encode($recentPatientsData) ?>;
document.getElementById('recentPatientsList').innerHTML=PATIENTS.map(p=>`
    <div class="patient-row">
        <img src="../assets/images/default_avatar.svg" class="patient-avatar" alt="${p.name}">
        <div class="patient-info"><h4>${p.name}</h4><p>Age ${p.age} · Blood: ${p.blood} · Last visit: ${p.last}</p></div>
        <button class="btn btn-outline btn-sm" onclick="document.getElementById('rxModal').classList.add('open')"><i class="fa-solid fa-file-prescription"></i></button>
    </div>`).join('');

function searchPatient(){
    const v=document.getElementById('patientSearchInput').value.trim();
    if(v){document.getElementById('patientResult').style.display='block';}
}
document.getElementById('patientSearchInput').addEventListener('keypress',e=>{if(e.key==='Enter')searchPatient();});

function addDrug(){
    const row=document.createElement('div');
    row.className='drug-row';
    row.innerHTML=`<input type="text" class="form-control" placeholder="Medicine name"><input type="text" class="form-control" placeholder="Dose"><select class="form-control"><option>1x daily</option><option>2x daily</option><option>3x daily</option></select><input type="text" class="form-control" placeholder="Days" style="max-width:70px;"><button class="remove-drug" onclick="removeDrug(this)"><i class="fa-solid fa-trash"></i></button>`;
    document.getElementById('drugList').appendChild(row);
}
function removeDrug(btn){const row=btn.closest('.drug-row');if(document.querySelectorAll('.drug-row').length>1)row.remove();}
async function issuePrescription() {
    const patient_id = document.getElementById('rxPatientSelect').value;
    const diagnosis = document.getElementById('rxDiagnosis').value;
    
    // gather medicines from the dynamically added rows
    const medRows = document.querySelectorAll('#drugList .drug-row');
    let medicinesStr = [];
    medRows.forEach(row => {
        const inputs = row.querySelectorAll('input, select');
        if(inputs[0].value) {
            medicinesStr.push(`${inputs[0].value} (${inputs[1].value}, ${inputs[2].value}, ${inputs[3].value} days)`);
        }
    });
    
    if (!patient_id || !diagnosis) {
        alert("Patient and Diagnosis are required.");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'issue_prescription');
    formData.append('patient_id', patient_id);
    formData.append('diagnosis', diagnosis);
    formData.append('medicines', medicinesStr.join(' | '));
    formData.append('notes', 'Issued via Quick Prescription panel.');

    try {
        const res = await fetch('../api/crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message + '\nPatient notified via SMS.');
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
