<?php
// patient/prescriptions.php
// Securely fetches and displays prescriptions ONLY for the logged-in patient.
require_once '../config/session.php';
require_once '../config/db.php';
requireLogin('patient');

$patient_id = $_SESSION['user_id'];
$patientName = $_SESSION['name'] ?? 'Patient';

// Fetch ONLY prescriptions for this patient, joined with doctor name
$stmt = $pdo->prepare("
    SELECT p.id, p.prescription_code, p.notes, p.created_at,
           u.name AS doctor_name
    FROM prescriptions p
    JOIN users u ON u.id = p.doctor_id
    WHERE p.patient_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-section{padding:18px 0 4px 20px;font-size:.68rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px}
        .rx-card{background:var(--card-bg);border:1px solid var(--input-border);border-radius:14px;padding:22px 24px;margin-bottom:16px;box-shadow:var(--shadow-sm);border-left:4px solid var(--primary-color);transition:transform .25s,box-shadow .25s}
        .rx-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md)}
        .rx-header{display:flex;align-items:flex-start;gap:16px;margin-bottom:14px;flex-wrap:wrap}
        .rx-icon{width:52px;height:52px;border-radius:14px;background:rgba(56,161,105,.1);color:#38a169;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
        .rx-meta{flex:1}
        .rx-meta h3{margin:0 0 4px;font-size:1rem}
        .rx-meta p{margin:0;font-size:.82rem;color:var(--text-secondary)}
        .rx-date{font-size:.8rem;color:var(--text-secondary);white-space:nowrap}
        .rx-notes-box{background:var(--input-bg);border-radius:10px;padding:14px;margin-bottom:14px;white-space:pre-line;font-size:.88rem;line-height:1.6;color:var(--text-primary)}
        .rx-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding-top:12px;border-top:1px solid var(--input-border)}
        .rx-code{font-size:.8rem;font-weight:700;color:var(--primary-color);background:rgba(56,161,105,.08);padding:4px 10px;border-radius:6px}
        .empty-state{text-align:center;padding:60px 20px;color:var(--text-secondary)}
        .empty-state i{font-size:3rem;margin-bottom:14px;display:block;opacity:.3}
        .filter-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:20px}
        .filter-bar input{padding:9px 14px;border:1px solid var(--input-border);border-radius:8px;background:var(--input-bg);color:var(--text-primary);font-size:.88rem;outline:none;flex:1;min-width:200px}
        .filter-bar input:focus{border-color:var(--primary-color)}
        /* Modal */
        .rx-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);z-index:10000;display:none;align-items:center;justify-content:center}
        .rx-modal-overlay.open{display:flex}
        .rx-modal-box{background:var(--card-bg);border-radius:16px;padding:32px;width:90%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,.25);max-height:90vh;overflow-y:auto}
        .rx-print-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid var(--primary-color);padding-bottom:14px;margin-bottom:18px}
        .rx-print-logo{font-size:1.2rem;font-weight:700;color:var(--primary-color)}
        .rx-print-patient{background:var(--input-bg);border-radius:10px;padding:14px 16px;margin-bottom:16px}
        .rx-drug-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px dashed var(--input-border)}
        .rx-drug-row:last-of-type{border-bottom:none}
        .stat-card::before{background:var(--primary-color)}
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
        <li><a href="prescriptions.php" class="active"><i class="fa-solid fa-file-prescription"></i><span>Prescriptions</span></a></li>
        <li><a href="test_reports.html"><i class="fa-solid fa-flask-vial"></i><span>Test Reports</span></a></li>
    </ul>
    <p class="nav-section">FIND</p>
    <ul class="sidebar-menu">
        <li><a href="find_doctors.html"><i class="fa-solid fa-user-doctor"></i><span>Find Doctors</span></a></li>
        <li><a href="hospitals.php"><i class="fa-solid fa-hospital"></i><span>Hospitals</span></a></li>
        <li><a href="medicines.php"><i class="fa-solid fa-pills"></i><span>Medicines</span></a></li>
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
            <div class="page-title"><h1>My Prescriptions</h1><p>Patient Portal / Prescriptions</p></div>
        </div>
        <div class="topbar-right">
            <div class="theme-switcher-row">
                <button class="theme-btn" data-set-theme="light"><i class="fa-solid fa-sun"></i></button>
                <button class="theme-btn" data-set-theme="dark"><i class="fa-solid fa-moon"></i></button>
                <button class="theme-btn" data-set-theme="colorblind"><i class="fa-solid fa-eye-low-vision"></i></button>
            </div>
            <div class="notification-container">
                <button class="icon-btn" id="notificationBtn"><i class="fa-solid fa-bell"></i>
                    <span class="badge" id="notificationBadge" style="display:none;">0</span></button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header"><h3>Notifications</h3><button id="markAllReadBtn" class="mark-all-read">Mark all as read</button></div>
                    <div class="notification-list" id="notificationList"><div class="notification-empty"><i class="fa-solid fa-bell-slash"></i><p>Loading...</p></div></div>
                    <div class="notification-footer"><a href="settings.html">Notification Settings</a></div>
                </div>
            </div>
            <div class="user-pill">
                <img src="../assets/images/default_avatar.svg" alt="Patient" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
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
        <!-- Stats -->
        <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:24px;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-file-prescription"></i></div>
                <div class="stat-info"><h2><?= count($prescriptions) ?></h2><p>Total Prescriptions</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-user-doctor"></i></div>
                <div class="stat-info"><h2><?= count(array_unique(array_column($prescriptions, 'doctor_name'))) ?></h2><p>Doctors</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-calendar"></i></div>
                <div class="stat-info">
                    <h2><?= $prescriptions ? date('M d', strtotime($prescriptions[0]['created_at'])) : '—' ?></h2>
                    <p>Latest Rx</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="filter-bar">
            <input type="text" id="rxSearch" placeholder="🔍  Search by doctor name or diagnosis...">
        </div>

        <!-- Prescriptions List -->
        <div id="rxList">
            <?php if (empty($prescriptions)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-file-medical"></i>
                    <p>No prescriptions found. Your doctor hasn't sent you any prescriptions yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($prescriptions as $rx): ?>
                    <?php
                        $code = $rx['prescription_code'] ?? ('RX-' . $rx['id']);
                        $date = date('M d, Y · h:i A', strtotime($rx['created_at']));
                        $notes = htmlspecialchars($rx['notes'] ?? '');
                        $doctor = htmlspecialchars($rx['doctor_name']);
                        $notesJs = json_encode($rx['notes'] ?? '');
                    ?>
                    <div class="rx-card" data-doctor="<?= strtolower($doctor) ?>" data-notes="<?= strtolower($rx['notes']) ?>">
                        <div class="rx-header">
                            <div class="rx-icon"><i class="fa-solid fa-file-prescription"></i></div>
                            <div class="rx-meta">
                                <h3><i class="fa-solid fa-user-doctor" style="color:var(--primary-color);margin-right:6px;"></i>Dr. <?= $doctor ?></h3>
                                <p><i class="fa-solid fa-clock" style="margin-right:4px;"></i><?= $date ?></p>
                            </div>
                            <span class="rx-code"><?= htmlspecialchars($code) ?></span>
                        </div>
                        <div class="rx-notes-box"><?= $notes ?></div>
                        <div class="rx-footer">
                            <span style="font-size:.8rem;color:var(--text-secondary);">
                                <i class="fa-solid fa-shield-halved" style="color:#38a169;"></i>
                                Private — Only visible to you
                            </span>
                            <div style="display:flex;gap:8px;">
                                <button class="btn btn-outline btn-sm" onclick="previewRx(<?= $rx['id'] ?>, 'Dr. <?= addslashes($doctor) ?>', '<?= addslashes($date) ?>', '<?= addslashes($code) ?>', <?= $notesJs ?>)">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="window.print()">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Prescription Detail Modal -->
<div class="rx-modal-overlay" id="rxModal">
    <div class="rx-modal-box">
        <div class="rx-print-header">
            <div>
                <div class="rx-print-logo">🏥 MPES Healthcare</div>
                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:3px;">Medical Primary Emergency System</div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;color:var(--primary-color);" id="modalCode"></div>
                <div style="font-size:.75rem;color:var(--text-secondary);" id="modalDate"></div>
            </div>
        </div>
        <div class="rx-print-patient" style="margin-bottom:16px;">
            <div style="font-size:.82rem;color:var(--text-secondary);margin-bottom:6px;">Prescribed by</div>
            <div style="font-weight:700;font-size:1rem;" id="modalDoctor"></div>
            <div style="font-size:.82rem;color:var(--text-secondary);margin-top:6px;">Patient: <strong><?= htmlspecialchars($patientName) ?></strong></div>
        </div>
        <div style="font-weight:700;margin-bottom:10px;color:var(--primary-color);"><i class="fa-solid fa-notes-medical"></i> Prescription Details</div>
        <div id="modalNotes" style="white-space:pre-line;font-size:.9rem;line-height:1.7;padding:14px;background:var(--input-bg);border-radius:10px;margin-bottom:16px;"></div>
        <div style="display:flex;gap:10px;margin-top:14px;">
            <button class="btn btn-primary w-100" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
            <button class="btn btn-outline w-100" onclick="document.getElementById('rxModal').classList.remove('open')">Close</button>
        </div>
    </div>
</div>

<script>
const html = document.documentElement;
const saved = localStorage.getItem('mpes-theme') || 'light';
html.setAttribute('data-theme', saved);
document.querySelectorAll('.theme-btn').forEach(btn => {
    if (btn.dataset.setTheme === saved) btn.classList.add('active');
    btn.addEventListener('click', () => {
        const t = btn.dataset.setTheme;
        html.setAttribute('data-theme', t);
        localStorage.setItem('mpes-theme', t);
        document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    });
});
const sidebar = document.getElementById('sidebar'), mw = document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    if (window.innerWidth <= 768) sidebar.classList.toggle('mobile-open');
    else { sidebar.classList.toggle('collapsed'); mw.classList.toggle('sidebar-collapsed'); }
});
document.querySelectorAll('.rx-modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

// Live search filter
document.getElementById('rxSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.rx-card').forEach(card => {
        const doctor = card.dataset.doctor || '';
        const notes = card.dataset.notes || '';
        card.style.display = (doctor.includes(q) || notes.includes(q)) ? '' : 'none';
    });
});

function previewRx(id, doctor, date, code, notes) {
    document.getElementById('modalCode').textContent = code;
    document.getElementById('modalDate').textContent = date;
    document.getElementById('modalDoctor').textContent = doctor;
    document.getElementById('modalNotes').textContent = notes || 'No details provided.';
    document.getElementById('rxModal').classList.add('open');
}
</script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
