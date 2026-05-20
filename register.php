<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPES – Create Account</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--body-bg); padding:30px 0; }
        .reg-wrap  { width:100%; max-width:520px; padding:20px; }
        .reg-card  { background:var(--card-bg); border-radius:18px; box-shadow:var(--shadow-md); padding:40px 36px; border:1px solid var(--input-border); }
        .reg-header { text-align:center; margin-bottom:28px; }
        .reg-header i { font-size:2.6rem; color:var(--primary-color); display:block; margin-bottom:10px; }
        .reg-header h2 { font-size:1.5rem; font-weight:800; margin:0 0 4px; }
        .reg-header p  { font-size:0.85rem; color:var(--text-secondary); margin:0; }
        .role-tabs { display:flex; background:var(--input-bg); border-radius:12px; padding:4px; margin-bottom:28px; gap:4px; }
        .role-tab  { flex:1; text-align:center; padding:10px 4px; border-radius:9px; font-size:0.8rem; font-weight:600; cursor:pointer; color:var(--text-secondary); transition:all 0.2s; border:none; background:transparent; display:flex; align-items:center; justify-content:center; gap:5px; }
        .role-tab.active { background:var(--card-bg); color:var(--primary-color); box-shadow:var(--shadow-sm); }
        .role-panel { display:none; }
        .role-panel.active { display:block; animation:fadeUp 0.25s ease; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .panel-title { font-size:1rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:8px; padding-bottom:12px; border-bottom:1px solid var(--input-border); }
        .input-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .input-icon { position:relative; }
        .input-icon .il { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary); font-size:0.88rem; pointer-events:none; }
        .input-icon input,.input-icon select { padding-left:40px !important; }
        .input-icon .ir { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:0.88rem; }
        .btn-reg { width:100%; margin-top:22px; padding:13px; font-size:0.95rem; font-weight:700; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; background:var(--primary-color); color:#fff; }
        .btn-reg:hover:not(:disabled) { filter:brightness(1.08); transform:translateY(-1px); }
        .btn-reg:disabled { opacity:0.7; cursor:not-allowed; }
        .btn-reg.green { background:#38a169; }
        .divider { text-align:center; color:var(--text-secondary); font-size:0.8rem; margin:18px 0 14px; position:relative; }
        .divider::before,.divider::after { content:''; position:absolute; top:50%; width:42%; height:1px; background:var(--input-border); }
        .divider::before { left:0; } .divider::after { right:0; }
        .login-link { text-align:center; font-size:0.85rem; color:var(--text-secondary); }
        .login-link a { color:var(--primary-color); font-weight:600; text-decoration:none; }
        .login-link a:hover { text-decoration:underline; }
        .section-label { font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); margin:18px 0 10px; }
        .info-note { background:rgba(43,108,176,0.07); border:1px solid rgba(43,108,176,0.2); border-radius:8px; padding:10px 14px; font-size:0.8rem; color:var(--primary-color); display:flex; align-items:center; gap:8px; margin-bottom:16px; }
        .doctor-note { background:rgba(56,161,105,0.08); border:1px solid rgba(56,161,105,0.2); border-radius:8px; padding:10px 14px; font-size:0.8rem; color:#38a169; display:flex; align-items:center; gap:8px; margin-bottom:16px; }
        .checkbox-label { display:flex; align-items:center; gap:8px; font-size:0.85rem; color:var(--text-secondary); cursor:pointer; }
        .theme-row { display:flex; justify-content:center; gap:8px; margin-top:22px; }
        @media(max-width:480px) { .input-row { grid-template-columns:1fr; } .reg-card { padding:28px 20px; } }
    </style>
</head>
<body>

<div class="reg-wrap">
    <div class="reg-card">

        <!-- Header -->
        <div class="reg-header">
            <i class="fa-solid fa-truck-medical"></i>
            <h2>Create Your Account</h2>
            <p>Join the Medical Primary Emergency System</p>
        </div>

        <!-- Role Tabs -->
        <div class="role-tabs">
            <button class="role-tab active" onclick="switchRole('patient',this)">
                <i class="fa-solid fa-user-injured"></i> Patient
            </button>
            <button class="role-tab" onclick="switchRole('doctor',this)">
                <i class="fa-solid fa-user-doctor"></i> Doctor
            </button>
        </div>

        <!-- ══ PATIENT REGISTRATION ══ -->
        <div class="role-panel active" id="panel-patient">
            <div class="panel-title" style="color:var(--primary-color);">
                <i class="fa-solid fa-user-injured"></i> Patient Registration
            </div>

            <div class="info-note">
                <i class="fa-solid fa-circle-info"></i>
                All fields marked * are required. Blood group helps in emergencies.
            </div>

            <!-- Basic Info -->
            <div class="section-label">Basic Information</div>
            <div class="input-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-user il"></i>
                        <input class="form-control" type="text" id="p_name" placeholder="John Doe">
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-phone il"></i>
                        <input class="form-control" type="tel" id="p_phone" placeholder="+1-555-0100">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope il"></i>
                    <input class="form-control" type="email" id="p_email" placeholder="you@example.com">
                </div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock il"></i>
                        <input class="form-control" type="password" id="p_pass" placeholder="••••••••" style="padding-right:40px;">
                        <i class="fa-solid fa-eye ir" onclick="togglePass('p_pass',this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock il"></i>
                        <input class="form-control" type="password" id="p_pass2" placeholder="••••••••" style="padding-right:40px;">
                        <i class="fa-solid fa-eye ir" onclick="togglePass('p_pass2',this)"></i>
                    </div>
                </div>
            </div>

            <!-- Medical Info -->
            <div class="section-label">Medical Information</div>
            <div class="input-row">
                <div class="form-group">
                    <label>Blood Group</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-droplet il"></i>
                        <select class="form-control" id="p_blood">
                            <option value="">Select</option>
                            <option>A+</option><option>A-</option>
                            <option>B+</option><option>B-</option>
                            <option>O+</option><option>O-</option>
                            <option>AB+</option><option>AB-</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-calendar il"></i>
                        <input class="form-control" type="date" id="p_dob">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <div class="input-icon">
                    <i class="fa-solid fa-location-dot il"></i>
                    <input class="form-control" type="text" id="p_address" placeholder="Your home address">
                </div>
            </div>

            <!-- Profile Picture -->
            <div class="section-label">Profile & Preferences</div>
            <div class="form-group">
                <label>Profile Picture</label>
                <input class="form-control" type="file" id="p_picture" accept="image/*" style="padding:8px;">
            </div>
            <div class="form-group" style="margin-top:10px;">
                <label class="checkbox-label">
                    <input type="checkbox" id="p_2fa">
                    Enable 2-Factor Authentication (SMS/Email OTP)
                </label>
            </div>

            <button class="btn-reg" onclick="doRegister('patient')">
                <i class="fa-solid fa-user-plus"></i> Create Patient Account
            </button>
            <div class="divider">already have an account?</div>
            <div class="login-link"><a href="login.php">Sign in here</a></div>
        </div>

        <!-- ══ DOCTOR REGISTRATION ══ -->
        <div class="role-panel" id="panel-doctor">
            <div class="panel-title" style="color:#38a169;">
                <i class="fa-solid fa-user-doctor"></i> Doctor Registration
            </div>

            <div class="doctor-note">
                <i class="fa-solid fa-stethoscope"></i>
                Doctor accounts are reviewed by admins before activation (24–48 hrs).
            </div>

            <!-- Personal Info -->
            <div class="section-label">Personal Information</div>
            <div class="input-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-user il"></i>
                        <input class="form-control" type="text" id="d_name" placeholder="Dr. First Last">
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-phone il"></i>
                        <input class="form-control" type="tel" id="d_phone" placeholder="+1-555-0200">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope il"></i>
                    <input class="form-control" type="email" id="d_email" placeholder="doctor@hospital.com">
                </div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock il"></i>
                        <input class="form-control" type="password" id="d_pass" placeholder="••••••••" style="padding-right:40px;">
                        <i class="fa-solid fa-eye ir" onclick="togglePass('d_pass',this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock il"></i>
                        <input class="form-control" type="password" id="d_pass2" placeholder="••••••••" style="padding-right:40px;">
                        <i class="fa-solid fa-eye ir" onclick="togglePass('d_pass2',this)"></i>
                    </div>
                </div>
            </div>

            <!-- Professional Info -->
            <div class="section-label">Professional Information</div>
            <div class="form-group">
                <label>Medical License Number *</label>
                <div class="input-icon">
                    <i class="fa-solid fa-id-card il"></i>
                    <input class="form-control" type="text" id="d_license" placeholder="MED-2026-XXXXX">
                </div>
            </div>
            <div class="input-row">
                <div class="form-group">
                    <label>Specialization *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-stethoscope il"></i>
                        <select class="form-control" id="d_specialty">
                            <option value="">Select</option>
                            <option>Cardiologist</option>
                            <option>Neurologist</option>
                            <option>Dermatologist</option>
                            <option>General Physician</option>
                            <option>Orthopedic</option>
                            <option>Pediatrician</option>
                            <option>Gynecologist</option>
                            <option>Psychiatrist</option>
                            <option>Ophthalmologist</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Years of Experience *</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-clock il"></i>
                        <input class="form-control" type="number" id="d_exp" placeholder="e.g. 10" min="0" max="60">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Current Hospital / Clinic</label>
                <div class="input-icon">
                    <i class="fa-solid fa-hospital il"></i>
                    <input class="form-control" type="text" id="d_hospital" placeholder="e.g. City General Hospital">
                </div>
            </div>
            <div class="form-group">
                <label>Consultation Fee (USD)</label>
                <div class="input-icon">
                    <i class="fa-solid fa-dollar-sign il"></i>
                    <input class="form-control" type="number" id="d_fee" placeholder="e.g. 80" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Short Bio</label>
                <textarea class="form-control" id="d_bio" rows="3" placeholder="Brief professional summary…" style="resize:vertical;"></textarea>
            </div>

            <button class="btn-reg green" onclick="doRegister('doctor')">
                <i class="fa-solid fa-user-plus"></i> Submit Doctor Application
            </button>
            <div class="divider">already registered?</div>
            <div class="login-link"><a href="login.php">Sign in here</a></div>
        </div>

        <!-- Theme switcher -->
        <div class="theme-row">
            <button class="theme-btn" data-set-theme="light" title="Light"><i class="fa-solid fa-sun"></i></button>
            <button class="theme-btn" data-set-theme="dark" title="Dark"><i class="fa-solid fa-moon"></i></button>
            <button class="theme-btn" data-set-theme="colorblind" title="Colorblind"><i class="fa-solid fa-eye-low-vision"></i></button>
        </div>

    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
// Theme
const saved = localStorage.getItem('mpes-theme') || 'light';
document.documentElement.setAttribute('data-theme', saved);
document.querySelectorAll('.theme-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const t = btn.getAttribute('data-set-theme');
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('mpes-theme', t);
    });
});

// Pre-select tab from URL ?role=patient|doctor
const urlRole = new URLSearchParams(location.search).get('role');
if (urlRole === 'doctor') {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('active'));
    document.querySelector('[onclick*="doctor"]').classList.add('active');
    document.getElementById('panel-doctor').classList.add('active');
}

// Switch role tab
function switchRole(role, btn) {
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.role-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + role).classList.add('active');
}

// Password toggle
function togglePass(id, icon) {
    const inp = document.getElementById(id);
    const showing = inp.type === 'text';
    inp.type = showing ? 'password' : 'text';
    icon.className = 'fa-solid ir ' + (showing ? 'fa-eye' : 'fa-eye-slash');
}

// Register
async function doRegister(role) {
    const btn  = event.currentTarget;
    const orig = btn.innerHTML;

    if (role === 'patient') {
        const name  = document.getElementById('p_name').value.trim();
        const email = document.getElementById('p_email').value.trim();
        const pass  = document.getElementById('p_pass').value;
        const pass2 = document.getElementById('p_pass2').value;
        if (!name || !email || !pass) { alert('Please fill in all required fields.'); return; }
        if (pass !== pass2) { alert('Passwords do not match.'); return; }
    } else {
        const name    = document.getElementById('d_name').value.trim();
        const email   = document.getElementById('d_email').value.trim();
        const pass    = document.getElementById('d_pass').value;
        const pass2   = document.getElementById('d_pass2').value;
        const license = document.getElementById('d_license').value.trim();
        const spec    = document.getElementById('d_specialty').value;
        if (!name || !email || !pass || !license || !spec) { alert('Please fill in all required fields.'); return; }
        if (pass !== pass2) { alert('Passwords do not match.'); return; }
    }

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating account…';
    btn.disabled  = true;

    try {
        const fd = new FormData();
        fd.append('action', 'register');
        fd.append('role', role);

        if (role === 'patient') {
            fd.append('name',    document.getElementById('p_name').value.trim());
            fd.append('email',   document.getElementById('p_email').value.trim());
            fd.append('password',document.getElementById('p_pass').value);
            fd.append('phone',   document.getElementById('p_phone').value.trim());
            fd.append('blood_group', document.getElementById('p_blood').value);
            fd.append('address', document.getElementById('p_address').value.trim());
            fd.append('two_factor', document.getElementById('p_2fa').checked ? '1' : '0');
            const pic = document.getElementById('p_picture').files[0];
            if (pic) fd.append('profile_picture', pic);
        } else {
            fd.append('name',     document.getElementById('d_name').value.trim());
            fd.append('email',    document.getElementById('d_email').value.trim());
            fd.append('password', document.getElementById('d_pass').value);
            fd.append('phone',    document.getElementById('d_phone').value.trim());
            fd.append('license',  document.getElementById('d_license').value.trim());
            fd.append('specialty',document.getElementById('d_specialty').value);
            fd.append('experience', document.getElementById('d_exp').value);
            fd.append('hospital', document.getElementById('d_hospital').value.trim());
            fd.append('fee',      document.getElementById('d_fee').value);
            fd.append('bio',      document.getElementById('d_bio').value.trim());
        }

        const res  = await fetch('api/auth.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message || 'Account created! You can now log in.');
            window.location.href = 'login.php';
        } else {
            alert(data.message || 'Registration failed. Please try again.');
            btn.innerHTML = orig;
            btn.disabled  = false;
        }
    } catch (e) {
        alert('Cannot reach server. Is Apache/MySQL running?');
        btn.innerHTML = orig;
        btn.disabled  = false;
    }
}
</script>
</body>
</html>
