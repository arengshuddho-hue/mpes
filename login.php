<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPES – Login</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--body-bg); }
        .login-wrap { width:100%; max-width:440px; padding:20px; }
        .login-card { background:var(--card-bg); border-radius:18px; box-shadow:var(--shadow-md); padding:40px 36px; border:1px solid var(--input-border); }
        .login-header { text-align:center; margin-bottom:32px; }
        .login-header i { font-size:2.8rem; color:var(--primary-color); display:block; margin-bottom:10px; }
        .login-header h2 { font-size:1.55rem; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
        .login-header p { font-size:0.85rem; color:var(--text-secondary); margin:0; }
        .role-tabs { display:flex; background:var(--input-bg); border-radius:12px; padding:4px; margin-bottom:28px; gap:4px; }
        .role-tab { flex:1; text-align:center; padding:10px 6px; border-radius:9px; font-size:0.82rem; font-weight:600; cursor:pointer; color:var(--text-secondary); transition:all 0.2s; border:none; background:transparent; display:flex; align-items:center; justify-content:center; gap:6px; }
        .role-tab.active { background:var(--card-bg); color:var(--primary-color); box-shadow:var(--shadow-sm); }
        .role-panel { display:none; }
        .role-panel.active { display:block; animation:fadeUp 0.25s ease; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .panel-title { font-size:1.05rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .input-icon { position:relative; }
        .input-icon .il { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary); font-size:0.9rem; pointer-events:none; }
        .input-icon input { padding-left:40px !important; }
        .input-icon .ir { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:var(--text-secondary); cursor:pointer; font-size:0.9rem; }
        .forgot { font-size:0.8rem; color:var(--primary-color); text-align:right; display:block; margin-top:4px; text-decoration:none; }
        .forgot:hover { text-decoration:underline; }
        .btn-login { width:100%; margin-top:20px; padding:13px; font-size:0.95rem; font-weight:700; border-radius:10px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s; }
        .btn-login:hover:not(:disabled) { filter:brightness(1.08); transform:translateY(-1px); }
        .btn-login:disabled { opacity:0.7; cursor:not-allowed; }
        .btn-patient { background:var(--primary-color); color:#fff; }
        .btn-doctor  { background:#38a169; color:#fff; }
        .btn-admin   { background:#1a202c; color:#fff; }
        .divider { text-align:center; color:var(--text-secondary); font-size:0.8rem; margin:18px 0 14px; position:relative; }
        .divider::before,.divider::after { content:''; position:absolute; top:50%; width:42%; height:1px; background:var(--input-border); }
        .divider::before { left:0; } .divider::after { right:0; }
        .register-link { text-align:center; font-size:0.85rem; color:var(--text-secondary); }
        .register-link a { color:var(--primary-color); font-weight:600; text-decoration:none; }
        .register-link a:hover { text-decoration:underline; }
        .admin-note { background:rgba(26,32,44,0.07); border:1px solid rgba(26,32,44,0.15); border-radius:8px; padding:10px 14px; font-size:0.8rem; color:var(--text-secondary); display:flex; align-items:center; gap:8px; margin-bottom:16px; }
        [data-theme="dark"] .admin-note { background:rgba(255,255,255,0.05); border-color:rgba(255,255,255,0.1); }
        .theme-row { display:flex; justify-content:center; gap:8px; margin-top:22px; }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <i class="fa-solid fa-truck-medical"></i>
            <h2>Welcome to MPES</h2>
            <p>Medical Primary Emergency System</p>
        </div>

        <!-- Role Tabs -->
        <div class="role-tabs">
            <button class="role-tab active" onclick="switchRole('patient',this)">
                <i class="fa-solid fa-user-injured"></i> Patient
            </button>
            <button class="role-tab" onclick="switchRole('doctor',this)">
                <i class="fa-solid fa-user-doctor"></i> Doctor
            </button>
            <button class="role-tab" onclick="switchRole('admin',this)">
                <i class="fa-solid fa-user-shield"></i> Admin
            </button>
        </div>

        <!-- ── PATIENT LOGIN ── -->
        <div class="role-panel active" id="panel-patient">
            <div class="panel-title" style="color:var(--primary-color);">
                <i class="fa-solid fa-user-injured"></i> Patient Login
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope il"></i>
                    <input class="form-control" type="email" id="patientEmail" placeholder="patient@mpes.com">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock il"></i>
                    <input class="form-control" type="password" id="patientPass" placeholder="••••••••" style="padding-right:40px;">
                    <i class="fa-solid fa-eye ir" onclick="togglePass('patientPass',this)"></i>
                </div>
            </div>
            <a href="#" class="forgot">Forgot Password?</a>
            <button class="btn-login btn-patient" onclick="doLogin('patient')">
                <i class="fa-solid fa-right-to-bracket"></i> Login as Patient
            </button>
            <div class="divider">or</div>
            <div class="register-link">
                New patient? <a href="register.php?role=patient">Create an Account</a>
            </div>
        </div>

        <!-- ── DOCTOR LOGIN ── -->
        <div class="role-panel" id="panel-doctor">
            <div class="panel-title" style="color:#38a169;">
                <i class="fa-solid fa-user-doctor"></i> Doctor Login
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope il"></i>
                    <input class="form-control" type="email" id="doctorEmail" placeholder="doctor@mpes.com">
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock il"></i>
                    <input class="form-control" type="password" id="doctorPass" placeholder="••••••••" style="padding-right:40px;">
                    <i class="fa-solid fa-eye ir" onclick="togglePass('doctorPass',this)"></i>
                </div>
            </div>
            <a href="#" class="forgot">Forgot Password?</a>
            <button class="btn-login btn-doctor" onclick="doLogin('doctor')">
                <i class="fa-solid fa-right-to-bracket"></i> Login as Doctor
            </button>
            <div class="divider">or</div>
            <div class="register-link">
                New doctor? <a href="register.php?role=doctor">Apply for Access</a>
            </div>
        </div>

        <!-- ── ADMIN LOGIN ── -->
        <div class="role-panel" id="panel-admin">
            <div class="panel-title">
                <i class="fa-solid fa-user-shield"></i> Admin Secure Access
            </div>
            <div class="admin-note">
                <i class="fa-solid fa-shield-halved"></i>
                Authorized personnel only. All access is logged.
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope il"></i>
                    <input class="form-control" type="email" id="adminEmail" placeholder="admin@mpes.com">
                </div>
            </div>
            <div class="form-group">
                <label>Secure Password</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock il"></i>
                    <input class="form-control" type="password" id="adminPass" placeholder="••••••••" style="padding-right:40px;">
                    <i class="fa-solid fa-eye ir" onclick="togglePass('adminPass',this)"></i>
                </div>
            </div>
            <button class="btn-login btn-admin" onclick="doLogin('admin')">
                <i class="fa-solid fa-lock"></i> Secure Login
            </button>
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

// Login
async function doLogin(role) {
    const email    = document.getElementById(role + 'Email').value.trim();
    const password = document.getElementById(role + 'Pass').value;
    if (!email || !password) { alert('Please enter your email and password.'); return; }
    const btn  = event.currentTarget;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating…';
    btn.disabled  = true;
    try {
        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('role', role);
        fd.append('email', email);
        fd.append('password', password);
        const res  = await fetch('api/auth.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Login failed. Check your credentials.');
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