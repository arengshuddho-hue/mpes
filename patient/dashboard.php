<?php
// patient/dashboard.php
// Central hub for logged-in patients. Displays quick stats, upcoming appointments,
// recent prescriptions, and alerts like unread reports.

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

// Aggregate key statistics to display in the stat cards
$apptCount = $pdo->query("SELECT COUNT(*) FROM appointments WHERE patient_id = $user_id")->fetchColumn();
$rxCount = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE patient_id = $user_id")->fetchColumn();
$testCount = $pdo->query("SELECT COUNT(*) FROM test_reports WHERE patient_id = $user_id")->fetchColumn();

// Fetch dynamic upcoming appointments for patient dashboard
$stmtPatientAppts = $pdo->prepare("
    SELECT a.id, a.serial_number, a.appointment_date, a.appointment_time, a.status,
           u.name AS doctor_name, d.specialist AS doctor_specialty, h.name AS hospital_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    LEFT JOIN doctor_details d ON u.id = d.user_id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE a.patient_id = ? AND a.status != 'cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 3
");
$stmtPatientAppts->execute([$user_id]);
$patientAppointments = $stmtPatientAppts->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors for appointment booking
$allDoctors = $pdo->query("SELECT u.id, u.name, d.specialist FROM users u LEFT JOIN doctor_details d ON u.id = d.user_id WHERE u.role = 'doctor'")->fetchAll();

// Fetch doctors with hospital name dynamically for the quick list
$stmtDocs = $pdo->query("
    SELECT u.id, u.name, d.specialist, d.consultation_fee, h.name AS hospital_name, u.profile_picture
    FROM users u
    JOIN doctor_details d ON u.id = d.user_id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE u.role = 'doctor'
    LIMIT 3
");
$dashDoctors = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

// Fetch reviews dynamically from doctors
$stmtReviews = $pdo->query("
    SELECT u.name AS doctor_name, h.name AS hospital_name, d.rating
    FROM users u
    JOIN doctor_details d ON u.id = d.user_id
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE u.role = 'doctor'
    ORDER BY d.rating DESC
    LIMIT 2
");
$dashReviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

// Fetch all hospitals from the database for the map
$stmtH = $pdo->query("SELECT name, latitude AS lat, longitude AS lng FROM hospitals");
$dbHospitals = $stmtH->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        /* Page-specific extras */
        .nav-section { padding: 18px 0 4px 20px; font-size: 0.68rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; }
        .section-heading { font-size: 1.05rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .section-heading i { color: var(--primary-color); }
        .view-all { font-size: 0.82rem; color: var(--primary-color); text-decoration: none; margin-left: auto; }
        .view-all:hover { text-decoration: underline; }
        #hospitalMap { height: 360px; border-radius: 12px; border: 1px solid var(--input-border); }
    </style>
</head>
<body class="dashboard-body">

<!-- ════════════════════════════ SIDEBAR ════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <a class="sidebar-logo" href="dashboard.php">
        <i class="fa-solid fa-truck-medical logo-icon"></i>
        <span class="logo-text">MPES</span>
    </a>

    <p class="nav-section">MAIN</p>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="active"><i class="fa-solid fa-house-medical"></i> <span>Dashboard</span></a></li>
        <li><a href="profile.html"><i class="fa-solid fa-user-injured"></i> <span>My Profile</span></a></li>
        <li><a href="appointments.html"><i class="fa-solid fa-calendar-check"></i> <span>Appointments</span></a></li>
        <li><a href="prescriptions.html"><i class="fa-solid fa-file-prescription"></i> <span>Prescriptions</span></a></li>
        <li><a href="test_reports.html"><i class="fa-solid fa-flask-vial"></i> <span>Test Reports</span></a></li>
    </ul>

    <p class="nav-section">FIND</p>
    <ul class="sidebar-menu">
        <li><a href="find_doctors.html"><i class="fa-solid fa-user-doctor"></i> <span>Find Doctors</span></a></li>
        <li><a href="hospitals.php"><i class="fa-solid fa-hospital"></i> <span>Hospitals</span></a></li>
        <li><a href="medicines.php"><i class="fa-solid fa-pills"></i> <span>Medicines</span></a></li>
        <li><a href="blood_donors.php"><i class="fa-solid fa-droplet"></i> <span>Blood Donors</span></a></li>
    </ul>

    <p class="nav-section">HEALTH</p>
    <ul class="sidebar-menu">
        <li><a href="symptom_checker.html"><i class="fa-solid fa-robot"></i> <span>AI Symptom Checker</span></a></li>
        <li><a href="settings.html"><i class="fa-solid fa-gear"></i> <span>Settings</span></a></li>
        <li class="danger"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a></li>
    </ul>
</aside>

<!-- ════════════════════════════ MAIN WRAPPER ════════════════════════════ -->
<div class="main-wrapper" id="mainWrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="icon-btn" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Patient Portal / Dashboard</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-search">
                <i class="fa-solid fa-search"></i>
                <input type="text" placeholder="Search doctors, medicines...">
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
                        <a href="settings.html">Notification Settings</a>
                    </div>
                </div>
            </div>
            <button class="icon-btn" title="Messages"><i class="fa-solid fa-comment-medical"></i></button>
            <button class="sos-btn" onclick="document.getElementById('sosModal').classList.add('open')">
                <i class="fa-solid fa-phone-volume"></i> SOS
            </button>
            <div class="user-pill">
                <img src="https://i.pravatar.cc/40?img=11" alt="Patient">
                <span><?= htmlspecialchars($patientName) ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size:0.7rem; color:var(--text-secondary);"></i>
                <div class="dropdown-menu">
                    <a href="profile.html"><i class="fa-solid fa-user"></i> My Profile</a>
                    <a href="settings.html"><i class="fa-solid fa-gear"></i> Settings</a>
                    <a href="#"><i class="fa-solid fa-circle-question"></i> Help Center</a>
                    <a href="../logout.php" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

        <!-- Emergency Banner -->
        <div class="emergency-banner">
            <div>
                <h3><i class="fa-solid fa-truck-medical"></i> Emergency Ambulance</h3>
                <p>Get nearest government &amp; private ambulance instantly</p>
            </div>
            <button class="btn-emergency" onclick="document.getElementById('sosModal').classList.add('open')">
                <i class="fa-solid fa-phone-volume"></i> Call Ambulance
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $apptCount ?>">0</h2>
                    <p>Appointments</p>
                    <div class="stat-trend"><i class="fa-solid fa-arrow-up"></i> Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-file-prescription"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $rxCount ?>">0</h2>
                    <p>Prescriptions</p>
                    <div class="stat-trend" style="color:#38a169;"><i class="fa-solid fa-arrow-up"></i> Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-flask-vial"></i></div>
                <div class="stat-info">
                    <h2 class="counter" data-target="<?= $testCount ?>">0</h2>
                    <p>Test Reports</p>
                    <div class="stat-trend" style="color:#ed8936;">Live Sync</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fa-solid fa-droplet"></i></div>
                <div class="stat-info">
                    <h2><?= htmlspecialchars($bloodGroup) ?></h2>
                    <p>Blood Group</p>
                    <div class="stat-trend" style="color:var(--text-secondary);">From Profile</div>
                </div>
            </div>
        </div>

        <!-- Appointments + Prescriptions -->
        <div class="grid-2">
            <!-- Upcoming Appointments -->
            <div class="card">
                <div class="section-heading">
                    <i class="fa-solid fa-calendar-check"></i> Upcoming Appointments
                    <a href="appointments.html" class="view-all">View all →</a>
                </div>
                <div id="appointmentList">
                    <?php if (empty($patientAppointments)): ?>
                        <div style="text-align:center;padding:20px;color:var(--text-secondary);"><i class="fa-solid fa-calendar-check" style="font-size:2rem;margin-bottom:8px;opacity:0.5;color:var(--primary-color);"></i><p>No upcoming appointments.</p></div>
                    <?php else: ?>
                        <?php foreach ($patientAppointments as $appt): 
                            $timeObj = strtotime($appt['appointment_date']);
                            $day = date('d', $timeObj);
                            $mon = strtoupper(date('M', $timeObj));
                            $formattedTime = date('h:i A', strtotime($appt['appointment_time'] ?: $appt['appointment_date']));
                            $statusClass = match($appt['status']) {
                                'confirmed' => 'success',
                                'pending' => 'warning',
                                'completed' => 'info',
                                'cancelled' => 'danger',
                                default => 'warning'
                            };
                            $badgeColor = match($appt['status']) {
                                'confirmed' => '',
                                'pending' => 'style="background:#ed8936;"',
                                'completed' => 'style="background:#38a169;"',
                                'cancelled' => 'style="background:#e53e3e;"',
                                default => ''
                            };
                        ?>
                            <div class="appointment-item">
                                <div class="appt-date" <?= $badgeColor ?>><div class="day"><?= $day ?></div><div class="mon"><?= $mon ?></div></div>
                                <div class="appt-info">
                                    <h4><?= htmlspecialchars($appt['doctor_name']) ?></h4>
                                    <p><?= htmlspecialchars($appt['doctor_specialty'] ?: 'Specialist') ?> · <?= htmlspecialchars($appt['hospital_name'] ?: 'City Hospital') ?></p>
                                    <p>Serial <?= htmlspecialchars($appt['serial_number']) ?> | <?= $formattedTime ?></p>
                                </div>
                                <span class="badge-pill <?= $statusClass ?>"><?= ucfirst($appt['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary btn-sm" style="margin-top:16px;" onclick="document.getElementById('bookApptModal').classList.add('open')">
                    <i class="fa-solid fa-calendar-plus"></i> Book New Appointment
                </button>
            </div>

            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="section-heading">
                    <i class="fa-solid fa-file-prescription"></i> Recent Prescriptions
                    <a href="prescriptions.html" class="view-all">View all →</a>
                </div>
                <div class="rx-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong>Dr. Sarah Ahmed — Cardiologist</strong>
                            <p class="text-muted" style="margin:3px 0 0;">May 15, 2026 · City Hospital</p>
                        </div>
                        <span class="badge-pill success">Active</span>
                    </div>
                    <div class="rx-drug">Paracetamol 500mg <span class="dose">· 2x daily after meal · 7 days</span></div>
                    <div class="rx-drug">Amoxicillin 250mg <span class="dose">· 3x daily · 5 days</span></div>
                </div>
            </div>
        </div>

        <!-- AI Symptom Checker -->
        <div class="symptom-checker mt-4">
            <h3><i class="fa-solid fa-robot"></i> AI Symptom Checker</h3>
            <p>Describe your symptoms and get instant AI-powered health guidance</p>
            <div class="symptom-tags" id="symptomTags">
                <span class="symptom-tag" onclick="toggleTag(this)">Fever</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Headache</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Cough</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Fatigue</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Chest Pain</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Nausea</span>
                <span class="symptom-tag" onclick="toggleTag(this)">Dizziness</span>
            </div>
            <div class="symptom-input-row">
                <input type="text" id="symptomInput" placeholder="Describe additional symptoms...">
                <button class="btn btn-primary" onclick="checkSymptoms()">Check Now →</button>
            </div>
            <div id="symptomResult" style="margin-top:14px; background:rgba(255,255,255,0.1); border-radius:10px; padding:14px; display:none;">
                <strong>AI Assessment:</strong> Based on your symptoms, you may have a mild viral infection. Recommended: Rest, hydration, and paracetamol. If symptoms persist &gt;48 hrs, consult a doctor. <a href="find_doctors.php" style="color:#fff; text-decoration:underline;">Book Now →</a>
            </div>
        </div>

        <!-- Doctor Search + Live Map -->
        <div class="grid-2 mt-4">
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-user-doctor"></i> Find Doctors</div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
                    <input type="text" class="form-control" placeholder="Doctor name..." style="flex:1; min-width:140px;">
                    <select class="form-control" style="flex:1; min-width:140px;">
                        <option>All Specialists</option>
                        <option>Cardiologist</option>
                        <option>Neurologist</option>
                        <option>Dermatologist</option>
                    </select>
                </div>
                <div id="dashDoctorList">
                    <!-- Quick doctor list -->
                    <?php foreach ($dashDoctors as $index => $doc): 
                        $profilePic = $doc['profile_picture'] ?: "https://i.pravatar.cc/40?img=" . (10 + $index);
                        $shortHospital = explode('|', $doc['hospital_name'] ?? 'General Hospital')[0];
                    ?>
                        <div class="appointment-item">
                            <img src="<?= htmlspecialchars($profilePic) ?>" style="border-radius:50%; width:44px; height:44px; object-fit:cover;" alt="<?= htmlspecialchars($doc['name']) ?>">
                            <div class="appt-info">
                                <h4><?= htmlspecialchars($doc['name']) ?></h4>
                                <p><?= htmlspecialchars($doc['specialist']) ?> · <?= htmlspecialchars(trim($shortHospital)) ?> · $<?= number_format($doc['consultation_fee'], 0) ?>/visit</p>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="document.getElementById('bookApptModal').classList.add('open')">Book</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="find_doctors.html" class="btn btn-outline btn-sm" style="margin-top:14px; width:100%; justify-content:center;">View All Doctors</a>
            </div>

            <!-- Nearby Hospitals Map -->
            <div class="card">
                <div class="section-heading"><i class="fa-solid fa-location-dot"></i> Nearby Hospitals</div>
                <div id="hospitalMap"></div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="card mt-4">
            <div class="section-heading"><i class="fa-solid fa-star"></i> My Reviews</div>
            <table>
                <thead><tr><th>Doctor</th><th>Hospital</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($dashReviews as $rev): 
                        $stars = str_repeat('⭐', min(5, max(1, round($rev['rating']))));
                        $shortHospital = explode('|', $rev['hospital_name'] ?? 'General Hospital')[0];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($rev['doctor_name']) ?></td>
                            <td><?= htmlspecialchars(trim($shortHospital)) ?></td>
                            <td><?= $stars ?></td>
                            <td>Excellent consultation and care!</td>
                            <td>May 10, 2026</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div><!-- /main-wrapper -->

<!-- ══ SOS MODAL ══ -->
<div class="modal-overlay" id="sosModal">
    <div class="modal-box" style="text-align:center;">
        <div style="font-size:3rem; color:#e53e3e; animation:sosPulse 1.5s infinite;">🚑</div>
        <h2 style="color:#e53e3e; margin:12px 0 8px;">EMERGENCY SOS</h2>
        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:24px;">This will notify nearby hospitals and your emergency contacts with your live location.</p>
        <a href="tel:911" class="btn btn-danger w-100" style="justify-content:center; margin-bottom:10px;"><i class="fa-solid fa-phone-volume"></i> Call Ambulance (911)</a>
        <button class="btn btn-outline w-100" style="justify-content:center; margin-bottom:10px;" onclick="alert('Emergency alert sent to: Sarah Doe')"><i class="fa-solid fa-bell"></i> Notify Emergency Contacts</button>
        <button class="btn btn-outline w-100" style="justify-content:center;" onclick="document.getElementById('sosModal').classList.remove('open')">Close</button>
    </div>
</div>

<!-- ══ BOOK APPOINTMENT MODAL ══ -->
<div class="modal-overlay" id="bookApptModal">
    <div class="modal-box">
        <h3 style="margin-bottom:20px;"><i class="fa-solid fa-calendar-plus" style="color:var(--primary-color);"></i> Book Appointment</h3>
        <div class="form-group">
            <label>Select Doctor</label>
            <select class="form-control" id="apptDoctor">
                <option value="">Select a Doctor...</option>
                <?php foreach ($allDoctors as $doc): ?>
                    <option value="<?= $doc['id'] ?>"><?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialist'] ?? 'General') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Preferred Date</label>
            <input type="date" class="form-control" id="apptDate">
        </div>
        <div class="form-group">
            <label>Preferred Time</label>
            <select class="form-control" id="apptTime">
                <option value="09:00:00">09:00 AM</option>
                <option value="10:00:00">10:00 AM</option>
                <option value="11:00:00">11:00 AM</option>
                <option value="14:00:00">02:00 PM</option>
                <option value="15:00:00">03:00 PM</option>
            </select>
        </div>
        <div class="form-group">
            <label>Notes (optional)</label>
            <input type="text" class="form-control" id="apptNotes" placeholder="Brief reason for visit...">
        </div>
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button class="btn btn-primary w-100" onclick="bookAppt()"><i class="fa-solid fa-check"></i> Confirm Booking</button>
            <button class="btn btn-outline" onclick="document.getElementById('bookApptModal').classList.remove('open')">Cancel</button>
        </div>
    </div>
</div>

<!-- Floating SOS FAB -->
<button class="fab-sos" onclick="document.getElementById('sosModal').classList.add('open')">
    <i class="fa-solid fa-phone-volume"></i>
    <span>SOS</span>
</button>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/notifications.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/@mapbox/polyline"></script>
<script>
// ── Sidebar toggle ──────────────────────────────────────────
const sidebar = document.getElementById('sidebar');
const mainWrapper = document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        mainWrapper.classList.toggle('sidebar-collapsed');
    }
});

// ── Animated counters ───────────────────────────────────────
document.querySelectorAll('.counter').forEach(el => {
    const target = +el.getAttribute('data-target');
    let count = 0;
    const step = Math.ceil(target / 30);
    const interval = setInterval(() => {
        count += step;
        if (count >= target) { count = target; clearInterval(interval); }
        el.textContent = count;
    }, 50);
});

// ── Symptom checker ─────────────────────────────────────────
function toggleTag(el) { el.classList.toggle('active'); }
function checkSymptoms() {
    const result = document.getElementById('symptomResult');
    result.style.display = 'block';
}

// ── Book appointment ────────────────────────────────────────
async function bookAppt() {
    const doctor_id = document.getElementById('apptDoctor').value;
    const date = document.getElementById('apptDate').value;
    const time = document.getElementById('apptTime').value;
    const notes = document.getElementById('apptNotes').value;

    if (!doctor_id || !date) {
        alert("Please select a doctor and date.");
        return;
    }

    const formData = new FormData();
    formData.append('action', 'book_appointment');
    formData.append('doctor_id', doctor_id);
    formData.append('date', date);
    formData.append('time', time);
    formData.append('notes', notes);

    try {
        const res = await fetch('../api/crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(data.message + '\nSerial: ' + data.serial);
            document.getElementById('bookApptModal').classList.remove('open');
            window.location.reload(); // Reload to show new stats
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('Failed to connect to server.');
    }
}

window.addEventListener('load', () => {

    const map = L.map('hospitalMap').setView([23.8103, 90.4125], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Retrieve hospitals dynamically from database
    const hospitals = <?= json_encode($dbHospitals) ?>.map(h => ({
        name: h.name,
        lat: parseFloat(h.lat),
        lng: parseFloat(h.lng)
    }));

    // Haversine straight line distance calculator
    function calculateHaversine(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Default fallback coordinates (West Nurer Chala Rd, Vatara, Dhaka matching Google Maps blue dot)
    let userLat = 23.7994;
    let userLng = 90.4285;

    function initMapWithCoords(uLat, uLng) {
        // User marker
        L.marker([uLat, uLng])
            .addTo(map)
            .bindPopup("You are here")
            .openPopup();
        
        map.setView([uLat, uLng], 15);
        
        if (hospitals.length === 0) return;

        // Calculate straight line distance for sorting
        hospitals.forEach(h => {
            h.distanceVal = calculateHaversine(uLat, uLng, h.lat, h.lng);
        });

        // Sort closest first
        hospitals.sort((a, b) => a.distanceVal - b.distanceVal);

        // Best Choice (closest)
        const bestChoice = hospitals[0];

        // Render top 8 closest hospitals as Google-style 'H' pins to prevent heavy lag
        hospitals.slice(0, 8).forEach(h => {
            const isBest = h.name === bestChoice.name;
            const markerColor = isBest ? '#e53e3e' : '#f56565'; // Crimson red for best choice, coral red for others
            
            const customIcon = L.divIcon({
                html: `<div style="background:${markerColor};color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:16px;font-family:'Outfit', sans-serif;box-shadow:0 3px 8px rgba(0,0,0,0.3); border:2.5px solid #fff;">H</div>`,
                className: '',
                iconSize: [32, 32],
                iconAnchor: [16, 16],
                popupAnchor: [0, -16]
            });

            const marker = L.marker([h.lat, h.lng], {icon: customIcon}).addTo(map);
            
            if (isBest) {
                marker.bindPopup(`<strong>${h.name} (Best Choice)</strong><br>Distance: ${h.distanceVal.toFixed(2)} km (straight-line)`);
            } else {
                marker.bindPopup(`<strong>${h.name}</strong><br>Distance: ${h.distanceVal.toFixed(2)} km`);
            }
        });

        // Draw driving route ONLY for the best choice
        if (bestChoice) {
            drawRoute(uLat, uLng, bestChoice);
        }

        async function drawRoute(uLat, uLng, h) {
            try {
                const response = await fetch(
                    'https://api.openrouteservice.org/v2/directions/driving-car',
                    {
                        method: 'POST',
                        headers: {
                            'Authorization': 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6ImQxYjQ5NTIzZWU5MDQ2MDNiMDBhN2M4YjQwMzUwNzFiIiwiaCI6Im11cm11cjY0In0=',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            coordinates: [[uLng, uLat], [h.lng, h.lat]],
                            geometry_simplify: true,
                            instructions: false
                        })
                    }
                );
                const data = await response.json();
                if (data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const km = (route.summary.distance / 1000).toFixed(2);
                    const mins = Math.ceil(route.summary.duration / 60);
                    const routePoints = polyline.decode(route.geometry);

                    L.polyline(routePoints, {
                        color: '#e53e3e',
                        weight: 5,
                        opacity: 0.85
                    }).addTo(map);
                    
                    // Bind popup with routing data to closest hospital marker
                    L.popup()
                        .setLatLng([h.lat, h.lng])
                        .setContent(`<strong>${h.name} (Best Choice)</strong><br>Driving Distance: ${km} km<br>ETA: ${mins} mins`)
                        .openOn(map);
                } else {
                    // Straight fallback line
                    L.polyline([[uLat, uLng], [h.lat, h.lng]], {color: '#e53e3e', dashArray: '5, 10', weight: 3}).addTo(map);
                }
            } catch (err) {
                console.error("Routing error:", err);
                L.polyline([[uLat, uLng], [h.lat, h.lng]], {color: '#e53e3e', dashArray: '5, 10', weight: 3}).addTo(map);
            }
        }
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;
            initMapWithCoords(userLat, userLng);
        }, error => {
            console.warn("Geolocation failed or denied. Using default location (Nurer Chala).");
            initMapWithCoords(userLat, userLng);
        });
    } else {
        initMapWithCoords(userLat, userLng);
    }

});
</script>
</body>
</html>
