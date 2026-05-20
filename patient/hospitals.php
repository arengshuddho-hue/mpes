<?php
// patient/hospitals.php
// Interactive hospital directory integrated with Leaflet.js maps.
// Fetches records dynamically from the database and calculates routes/distances.

require_once '../config/session.php'; // Session management
require_once '../config/db.php';      // Database connection
requireLogin('patient');              // Ensure user is a patient

$user_id = $_SESSION['user_id'];

// Retrieve patient details for personalization
$stmt = $pdo->prepare("SELECT name, email, blood_group FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

$patientName = $patient['name'] ?? 'Patient';

// ── DATABASE STRUCTURE MIGRATION ──
// Safely alter table to ensure latest columns are present
try {
    $pdo->exec("ALTER TABLE hospitals MODIFY COLUMN type VARCHAR(100) NOT NULL");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE hospitals ADD COLUMN emergency_24h BOOLEAN DEFAULT FALSE AFTER contact_number");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE hospitals ADD COLUMN total_beds INT DEFAULT 0 AFTER emergency_24h");
} catch (Exception $e) {}
try {
    $pdo->exec("ALTER TABLE hospitals ADD COLUMN is_open BOOLEAN DEFAULT TRUE AFTER total_beds");
} catch (Exception $e) {}

// ── SELF-MIGRATION CHECK ──
// Auto-update coordinates to Dhaka if they are still set to the old New York seed values
try {
    $checkCoords = $pdo->query("SELECT latitude FROM hospitals WHERE name = 'City General Hospital'")->fetchColumn();
    if ($checkCoords === null || abs($checkCoords - 40.7128) < 0.001 || $checkCoords == 0) {
        $pdo->query("UPDATE hospitals SET address = 'Dhanmondi, Dhaka', latitude = 23.75270000, longitude = 90.38160000, type = 'General', emergency_24h = TRUE, total_beds = 45, is_open = TRUE WHERE name = 'City General Hospital'");
        $pdo->query("UPDATE hospitals SET address = 'Bashundhara, Dhaka', latitude = 23.80410000, longitude = 90.41520000, type = 'Specialized', emergency_24h = TRUE, total_beds = 12, is_open = TRUE WHERE name = 'Apollo Medical Center'");
        $pdo->query("UPDATE hospitals SET address = 'Banani, Dhaka', latitude = 23.79370000, longitude = 90.40660000, type = 'Clinic', emergency_24h = FALSE, total_beds = 0, is_open = TRUE WHERE name = 'LifeCare Clinic'");
        $pdo->query("UPDATE hospitals SET address = 'Gulshan, Dhaka', latitude = 23.80490000, longitude = 90.42350000, type = 'Specialized', emergency_24h = TRUE, total_beds = 8, is_open = TRUE WHERE name = 'Metro Heart Institute'");
        $pdo->query("UPDATE hospitals SET address = 'Lalmatia, Dhaka', latitude = 23.77720000, longitude = 90.37000000, type = 'Clinic', emergency_24h = FALSE, total_beds = 0, is_open = FALSE WHERE name = 'Sunrise Pediatric Care'");
    }
} catch (Exception $e) {
    // Silently continue
}

// Fetch all hospitals from the database
$stmtH = $pdo->query("SELECT id, name, type, address, latitude AS lat, longitude AS lng, emergency_24h AS emergency, total_beds AS beds, is_open AS open FROM hospitals");
$hospitals = $stmtH->fetchAll(PDO::FETCH_ASSOC);

// Map database fields to standard JSON for JS consumption
$hospitalsJson = json_encode($hospitals);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals Map | MPES</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@mapbox/polyline"></script>
    <style>
        .nav-section { padding:18px 0 4px 20px; font-size:.68rem; font-weight:700; color:var(--text-secondary); text-transform:uppercase; letter-spacing:1px }
        
        /* Full-height layout for the integrated map experience */
        .page-content { display:flex; flex-direction:column; height:calc(100vh - 65px); padding:0; overflow:hidden; }
        
        .map-header { padding:16px 24px; background:var(--card-bg); border-bottom:1px solid var(--input-border); display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; z-index:10; box-shadow:var(--shadow-sm) }
        .map-search { flex:1; min-width:280px; position:relative }
        .map-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary) }
        .map-search input { width:100%; padding:10px 14px 10px 40px; border:1px solid var(--input-border); border-radius:24px; background:var(--input-bg); color:var(--text-primary); font-size:0.9rem; outline:none; transition:all 0.2s; }
        .map-search input:focus { border-color:var(--primary-color); box-shadow:0 0 0 3px rgba(43,108,176,0.1) }
        
        .map-filters { display:flex; gap:10px; flex-wrap:wrap }
        .map-filter-btn { padding:8px 16px; border:1px solid var(--input-border); border-radius:20px; background:var(--input-bg); color:var(--text-primary); font-size:0.82rem; cursor:pointer; display:flex; align-items:center; gap:8px; font-weight:600; transition:all 0.2s }
        .map-filter-btn:hover, .map-filter-btn.active { border-color:var(--primary-color); color:var(--primary-color); background:rgba(43,108,176,0.05) }
        .map-filter-btn.active { background:var(--primary-color) !important; color:#fff !important; border-color:var(--primary-color) !important; }
        
        .map-container-wrapper { flex:1; display:flex; position:relative; overflow:hidden; }
        #hospitalMap { flex:1; height:100%; z-index:1 }
        
        /* Dark mode map tiles filter */
        [data-theme="dark"] .leaflet-layer,
        [data-theme="dark"] .leaflet-control-zoom-in,
        [data-theme="dark"] .leaflet-control-zoom-out,
        [data-theme="dark"] .leaflet-control-attribution {
            filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
        }
        
        /* Custom Glowing User Location Marker */
        .user-location-marker {
            width: 18px;
            height: 18px;
            background: #2b6cb0;
            border: 3px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(43, 108, 176, 0.4), 0 0 10px rgba(0, 0, 0, 0.25);
            animation: userPulse 1.8s infinite;
        }
        @keyframes userPulse {
            0% { box-shadow: 0 0 0 0 rgba(43, 108, 176, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(43, 108, 176, 0); }
            100% { box-shadow: 0 0 0 0 rgba(43, 108, 176, 0); }
        }

        /* Professional custom SVG markers for hospitals */
        .custom-hospital-marker {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 14px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
        }
        .custom-hospital-marker:hover {
            transform: scale(1.15) translateY(-2px);
            box-shadow: 0 5px 12px rgba(0,0,0,0.4);
        }
        
        .hospital-list-panel { width:360px; background:var(--card-bg); border-left:1px solid var(--input-border); overflow-y:auto; display:flex; flex-direction:column }
        @media(max-width:900px){
            .map-container-wrapper { flex-direction:column }
            .hospital-list-panel { width:100%; height:300px; border-left:none; border-top:1px solid var(--input-border) }
            .page-content { height: auto; min-height: calc(100vh - 65px); }
            #hospitalMap { min-height: 400px; }
        }
        
        .h-list-header { padding:16px 20px; border-bottom:1px solid var(--input-border); font-weight:700; position:sticky; top:0; background:var(--card-bg); z-index:2; display:flex; justify-content:space-between; align-items:center }
        .h-card { padding:18px 20px; border-bottom:1px solid var(--input-border); cursor:pointer; transition:all 0.2s; position:relative; }
        .h-card:hover { background:var(--input-bg) }
        .h-card.active { background:rgba(43,108,176,0.06); border-left:4px solid var(--primary-color); }
        .h-card h4 { margin:0 0 6px; font-size:0.95rem; color:var(--text-primary); font-weight: 700; }
        .h-card p { margin:0 0 10px; font-size:0.82rem; color:var(--text-secondary); display:flex; align-items:center; gap:6px }
        .h-meta-row { display:flex; justify-content:space-between; align-items:center; font-size:0.78rem; color:var(--text-secondary); margin-bottom:8px; }
        .h-tags { display:flex; gap:6px; flex-wrap:wrap }
        .h-tag { font-size:0.7rem; padding:3px 8px; border-radius:12px; background:var(--input-border); color:var(--text-secondary); font-weight:600 }
        .h-tag.emergency { background:rgba(229,62,62,0.1); color:#e53e3e }
        .h-tag.open { background:rgba(56,161,105,0.1); color:#38a169 }
        .h-tag.type { background:rgba(43,108,176,0.1); color:var(--primary-color) }

        /* Premium Popups */
        .leaflet-popup-content-wrapper { background:var(--card-bg); color:var(--text-primary); border-radius:14px; box-shadow:var(--shadow-md); padding:0; overflow:hidden; }
        .leaflet-popup-content { margin:0 !important; width:220px !important; }
        .leaflet-popup-tip { background:var(--card-bg) }
        .popup-custom { padding:16px; }
        .popup-custom h3 { margin:0 0 6px; font-size:1rem; font-weight:700; color:var(--text-primary) }
        .popup-custom p { margin:0 0 12px; font-size:0.82rem; color:var(--text-secondary); line-height:1.4 }
        .popup-custom .popup-meta { display:flex; gap:12px; font-size:0.78rem; color:var(--text-secondary); margin-bottom:12px; border-top:1px solid var(--input-border); padding-top:8px; }
        .popup-btn { background:var(--primary-color); color:#fff; border:none; padding:8px 12px; border-radius:8px; width:100%; font-weight:600; cursor:pointer; font-size:0.82rem; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s }
        .popup-btn:hover { background:var(--primary-hover); }
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
        <li><a href="prescriptions.html"><i class="fa-solid fa-file-prescription"></i><span>Prescriptions</span></a></li>
        <li><a href="test_reports.html"><i class="fa-solid fa-flask-vial"></i><span>Test Reports</span></a></li>
    </ul>
    <p class="nav-section">FIND</p>
    <ul class="sidebar-menu">
        <li><a href="find_doctors.html"><i class="fa-solid fa-user-doctor"></i><span>Find Doctors</span></a></li>
        <li><a href="hospitals.php" class="active"><i class="fa-solid fa-hospital"></i><span>Hospitals</span></a></li>
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
            <div class="page-title"><h1>Hospitals Directory</h1><p>Patient Portal / Map View</p></div>
        </div>
        <div class="topbar-right">
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
        <div class="map-header">
            <div class="map-search">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="hSearch" placeholder="Search hospitals by name, area or specialty...">
            </div>
            <div class="map-filters">
                <button class="map-filter-btn active" data-filter="nearby"><i class="fa-solid fa-location-crosshairs"></i> Nearby Hospitals</button>
                <button class="map-filter-btn" data-filter="all"><i class="fa-solid fa-building"></i> All Hospitals</button>
                <button class="map-filter-btn" data-filter="emergency"><i class="fa-solid fa-truck-medical"></i> Emergency 24/7</button>
                <button class="map-filter-btn" data-filter="specialized"><i class="fa-solid fa-star"></i> Specialized Clinics</button>
            </div>
        </div>

        <div class="map-container-wrapper">
            <div id="hospitalMap"></div>
            
            <div class="hospital-list-panel">
                <div class="h-list-header">
                    <span>Nearby Facilities</span>
                    <span class="badge-pill info" id="listCount">0 Found</span>
                </div>
                <div id="hospitalListContent">
                    <!-- Dynamic List -->
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Theme switcher logic
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

// Sidebar toggle logic with map resize handler
const sidebar=document.getElementById('sidebar'),mw=document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click',()=>{
    if(window.innerWidth<=768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        mw.classList.toggle('sidebar-collapsed');
        setTimeout(()=> { if(typeof map !== 'undefined') map.invalidateSize(); }, 300);
    }
});

// ── Hospital Data from Database ──
const DB_HOSPITALS = <?= $hospitalsJson ?>;
const HOSPITALS = DB_HOSPITALS.map(h => ({
    id: parseInt(h.id),
    name: h.name,
    lat: parseFloat(h.lat),
    lng: parseFloat(h.lng),
    address: h.address,
    type: h.type,
    emergency: h.emergency == 1,
    open: h.open == 1,
    beds: parseInt(h.beds),
    distanceVal: 99999, // default large sorting distance
    distance: "Calculating...",
    duration: "Calculating..."
}));

// Center on Dhaka initially
const defaultCenter = [23.8103, 90.4125];
const map = L.map('hospitalMap').setView(defaultCenter, 13);

// Initialize standard OSM tile layer (reliably works offline/online)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Professional Color-coded Markers
function getHospitalMarkerClass(type) {
    if (type.toLowerCase() === 'general') return 'custom-hospital-marker'; // blue
    if (type.toLowerCase() === 'specialized') return 'custom-hospital-marker'; // orange-red
    return 'custom-hospital-marker'; // clinic -> teal
}

function getHospitalColor(type) {
    if (type.toLowerCase() === 'general') return '#dd6b20';     // Google orange
    if (type.toLowerCase() === 'specialized') return '#e53e3e'; // Google crimson
    return '#f6ad55';                                           // Google yellow-orange
}

// Markers & Route Lines layers
let markers = [];
let userMarker = null;
let currentRouteLine = null;

// Haversine fallback distance calculator
function calculateHaversineDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Draw directions on map and highlight card
async function showDirections(userLat, userLng, h) {
    // Remove previous route
    if (currentRouteLine) {
        map.removeLayer(currentRouteLine);
        currentRouteLine = null;
    }

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
                    coordinates: [[userLng, userLat], [h.lng, h.lat]],
                    geometry_simplify: false,
                    instructions: false
                })
            }
        );
        
        const data = await response.json();
        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            const routePoints = polyline.decode(route.geometry);
            currentRouteLine = L.polyline(routePoints, {
                color: '#2b6cb0',
                weight: 5,
                opacity: 0.85
            }).addTo(map);
            
            // Zoom to fit bounds of route
            map.fitBounds(currentRouteLine.getBounds(), { padding: [50, 50] });
        } else {
            // Draw straight line fallback on map
            drawStraightFallbackRoute(userLat, userLng, h);
        }
    } catch (err) {
        console.warn("OpenRouteService failure, drawing fallback straight line:", err);
        drawStraightFallbackRoute(userLat, userLng, h);
    }
}

function drawStraightFallbackRoute(userLat, userLng, h) {
    currentRouteLine = L.polyline([[userLat, userLng], [h.lat, h.lng]], {
        color: '#e53e3e',
        dashArray: '5, 10',
        weight: 3,
        opacity: 0.8
    }).addTo(map);
    map.fitBounds(currentRouteLine.getBounds(), { padding: [50, 50] });
}

function renderMapAndList(data, userLocation = null) {
    // Clear existing hospital markers
    markers.forEach(m => map.removeLayer(m.marker));
    markers = [];
    
    // Clear route line if filtering
    if (currentRouteLine) {
        map.removeLayer(currentRouteLine);
        currentRouteLine = null;
    }

    const listContainer = document.getElementById('hospitalListContent');
    listContainer.innerHTML = '';
    
    document.getElementById('listCount').textContent = `${data.length} Found`;

    if(data.length === 0) {
        listContainer.innerHTML = `
            <div style="padding:40px 20px;text-align:center;color:var(--text-secondary);">
                <i class="fa-solid fa-hospital-slash" style="font-size:2rem;margin-bottom:10px;opacity:0.5;"></i>
                <p>No hospitals found.</p>
            </div>`;
        return;
    }

    // Render Markers & Cards
    data.forEach(h => {
        const markerColor = getHospitalColor(h.type);
        const customIcon = L.divIcon({
            html: `<div class="custom-hospital-marker" style="background:${markerColor};font-weight:900;font-size:16px;font-family:'Outfit', sans-serif;display:flex;align-items:center;justify-content:center;color:#fff;border-radius:50%;border:2.5px solid #fff;box-shadow:0 3px 8px rgba(0,0,0,0.3);width:32px;height:32px;">H</div>`,
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

        // Add Marker to map
        const marker = L.marker([h.lat, h.lng], {icon: customIcon}).addTo(map);
        
        // Premium Popup Layout
        const popupContent = `
            <div class="popup-custom">
                <h3>${h.name}</h3>
                <p><i class="fa-solid fa-location-dot" style="color:var(--text-secondary);"></i> ${h.address}</p>
                <div class="popup-meta">
                    <span><i class="fa-solid fa-bed"></i> ${h.beds} Beds</span>
                    <span style="margin-left:auto;"><i class="fa-solid fa-clock"></i> ${h.open ? 'Open' : 'Closed'}</span>
                </div>
                ${userLocation ? `
                <div style="font-size:0.78rem; margin-bottom:10px; font-weight:600; color:var(--text-secondary);">
                    Distance: ${h.distance}
                </div>
                ` : ''}
                <button class="popup-btn" id="pop-btn-${h.id}"><i class="fa-solid fa-location-arrow"></i> Get Directions</button>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        
        marker.on('popupopen', () => {
            document.getElementById(`pop-btn-${h.id}`).addEventListener('click', () => {
                if (userLocation) {
                    showDirections(userLocation[0], userLocation[1], h);
                } else {
                    alert(`To get directions to ${h.name}, please allow location access.`);
                }
            });
        });

        markers.push({id: h.id, marker: marker, data: h});

        // Create Card Item
        const item = document.createElement('div');
        item.className = 'h-card';
        item.innerHTML = `
            <h4>${h.name}</h4>
            <p><i class="fa-solid fa-location-dot"></i> ${h.address}</p>
            <div class="h-meta-row">
                <span><i class="fa-solid fa-bed"></i> Available Beds: <strong>${h.beds}</strong></span>
                <span>${userLocation ? `<i class="fa-solid fa-road"></i> ${h.distance}` : ''}</span>
            </div>
            <div class="h-tags">
                <span class="h-tag ${h.open ? 'open' : ''}">${h.open ? 'Open Now' : 'Closed'}</span>
                ${h.emergency ? '<span class="h-tag emergency"><i class="fa-solid fa-truck-medical"></i> 24/7 ER</span>' : ''}
                <span class="h-tag type">${h.type}</span>
            </div>
        `;
        
        item.addEventListener('click', () => {
            document.querySelectorAll('.h-card').forEach(c=>c.classList.remove('active'));
            item.classList.add('active');
            map.flyTo([h.lat, h.lng], 15);
            marker.openPopup();
            if (userLocation) {
                showDirections(userLocation[0], userLocation[1], h);
            }
        });

        listContainer.appendChild(item);
    });
}

// ── Geolocation & Distance Calculation ──
function initGeolocation() {
    // Default fallback coordinates (West Nurer Chala Rd, Vatara, Dhaka matching Google Maps blue dot)
    let userLat = 23.7994;
    let userLng = 90.4285;

    async function initHospitalsMap(uLat, uLng) {
        const userLocation = [uLat, uLng];

        // Center map to user
        map.setView(userLocation, 14);

        // Put glowing blue pulse user marker
        if (userMarker) map.removeLayer(userMarker);
        userMarker = L.marker(userLocation, {
            icon: L.divIcon({
                className: '',
                html: '<div class="user-location-marker"></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            })
        }).addTo(map).bindPopup("<strong>You are here</strong>");

        // Calculate straight-line distances instantly using Haversine
        HOSPITALS.forEach(h => {
            const hDist = calculateHaversineDistance(uLat, uLng, h.lat, h.lng);
            h.distanceVal = hDist;
            h.distance = hDist.toFixed(1) + " km";
            h.duration = "N/A";
        });

        // Sort closest first
        HOSPITALS.sort((a,b) => a.distanceVal - b.distanceVal);
        applyFilters(userLocation);

        // Auto-draw route to the single best choice (closest hospital) on page load
        if (HOSPITALS.length > 0) {
            setTimeout(() => {
                const bestChoice = HOSPITALS[0];
                showDirections(uLat, uLng, bestChoice);
                
                // Highlight the first card in the list
                const firstCard = document.querySelector('.h-card');
                if (firstCard) firstCard.classList.add('active');
            }, 800);
        }
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            userLat = position.coords.latitude;
            userLng = position.coords.longitude;
            initHospitalsMap(userLat, userLng);
        }, error => {
            console.warn("Location permission denied or unavailable. Using default location (Nurer Chala).");
            initHospitalsMap(userLat, userLng);
        });
    } else {
        initHospitalsMap(userLat, userLng);
    }
}

// ── Filtering Logic ──
let currentFilter = 'nearby'; // Default is 'nearby'
let searchQuery = '';

function applyFilters(userLocation = null) {
    let filtered = HOSPITALS.filter(h => {
        const matchesSearch = h.name.toLowerCase().includes(searchQuery) || 
                              h.address.toLowerCase().includes(searchQuery) ||
                              h.type.toLowerCase().includes(searchQuery);
        
        let matchesFilter = true;
        if (currentFilter === 'nearby') {
            matchesFilter = h.distanceVal <= 5.0; // Nearby within 5 km
        } else if (currentFilter === 'emergency') {
            matchesFilter = h.emergency;
        } else if (currentFilter === 'specialized') {
            matchesFilter = h.type.toLowerCase() === 'specialized';
        } else {
            matchesFilter = true; // 'all' displays everything
        }
        
        return matchesSearch && matchesFilter;
    });
    
    // Sort filtered results by closest
    filtered.sort((a,b) => a.distanceVal - b.distanceVal);
    
    // Limit to top 20 closest for nearby/filtered to keep it fast, but allow up to 250 for All Hospitals
    const sliceLimit = currentFilter === 'all' ? 250 : 20;
    const limitedResults = filtered.slice(0, sliceLimit);
    renderMapAndList(limitedResults, userLocation);
}

document.getElementById('hSearch').addEventListener('input', (e) => {
    searchQuery = e.target.value.toLowerCase();
    
    // Retrieve user position if marker exists
    const userLocation = userMarker ? [userMarker.getLatLng().lat, userMarker.getLatLng().lng] : null;
    applyFilters(userLocation);
});

document.querySelectorAll('.map-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.map-filter-btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.getAttribute('data-filter');
        
        const userLocation = userMarker ? [userMarker.getLatLng().lat, userMarker.getLatLng().lng] : null;
        applyFilters(userLocation);
    });
});

// Run Initial Geolocation and display
initGeolocation();

// Fix map height initialization layout bugs inside flex container
setTimeout(()=>map.invalidateSize(), 500);
</script>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
