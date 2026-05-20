<?php
// doctor/login.php
// A dedicated login interface specifically styled or purposed for doctors,
// though currently it might act as a unified entry point.
session_start();

// Guard: If the user is already authenticated, redirect them to their respective dashboard
// based on their assigned role (mock implementation, routes to 'doctor/dashboard.php' etc.)
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPES - Unified Login Portal</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 20px;
        }
        .login-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            position: relative;
            padding: 40px;
            border: 1px solid var(--input-border);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .login-header h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
        }
        .login-step {
            display: none;
            transition: all var(--transition-speed);
        }
        .login-step.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--input-border);
            z-index: 1;
            transform: translateY(-50%);
        }
        .step-dot {
            width: 30px;
            height: 30px;
            background: var(--card-bg);
            border: 2px solid var(--input-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            color: var(--text-secondary);
            position: relative;
            z-index: 2;
        }
        .step-dot.active {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(43, 108, 176, 0.1);
        }
        .flex-row {
            display: flex;
            gap: 15px;
        }
        .flex-row .form-group {
            flex: 1;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        .btn-outline:hover {
            background: var(--primary-color);
            color: #fff;
        }
        .w-100 { width: 100%; }
        .text-center { text-align: center; }
        .text-small { font-size: 0.85rem; color: var(--text-secondary); margin-top: 10px; }
    </style>
</head>
<body>

    <div class="theme-switcher">
        <button class="theme-btn" data-set-theme="light"><i class="fa-solid fa-sun"></i> Light</button>
        <button class="theme-btn" data-set-theme="dark"><i class="fa-solid fa-moon"></i> Dark</button>
        <button class="theme-btn" data-set-theme="colorblind"><i class="fa-solid fa-eye-low-vision"></i> Color-Blind</button>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fa-solid fa-truck-medical"></i>
                <h2>MPES Login Portal</h2>
            </div>

            <div class="step-indicator">
                <div class="step-dot active">1</div>
                <div class="step-dot">2</div>
                <div class="step-dot">3</div>
            </div>

            <form action="auth.php" method="POST" enctype="multipart/form-data">
                
                <!-- STEP 1: PATIENT LOGIN -->
                <div class="login-step active">
                    <h3 style="margin-bottom:20px; color:var(--primary-color);"><i class="fa-solid fa-user-injured"></i> Patient Access</h3>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="patient_email" class="form-control" placeholder="patient@example.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="patient_password" class="form-control" placeholder="••••••••">
                    </div>
                    
                    <div class="flex-row">
                        <div class="form-group">
                            <label>Blood Group</label>
                            <select name="blood_group" class="form-control">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="B+">B+</option>
                                <option value="O+">O+</option>
                                <option value="AB+">AB+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Profile Picture</label>
                            <input type="file" name="profile_pic" class="form-control" style="padding: 9px;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Social Media Link (Optional)</label>
                        <input type="text" name="social_link" class="form-control" placeholder="https://facebook.com/...">
                    </div>
                    
                    <div class="form-group">
                        <label><input type="checkbox" name="2fa_enable"> Enable 2-Factor Auth (SMS/Email OTP)</label>
                    </div>

                    <div class="text-small" style="margin-bottom:15px;">
                        <a href="#" style="color:var(--primary-color);">Forgot Password?</a>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="login_patient" class="btn btn-primary w-100">Login as Patient</button>
                    </div>
                    <div class="text-center" style="margin-top:15px;">
                        <button type="button" class="btn btn-outline w-100 next-step">Are you a Doctor? Next &rarr;</button>
                    </div>
                </div>

                <!-- STEP 2: DOCTOR LOGIN -->
                <div class="login-step">
                    <h3 style="margin-bottom:20px; color:var(--secondary-color);"><i class="fa-solid fa-user-doctor"></i> Doctor Access</h3>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="doctor_email" class="form-control" placeholder="doctor@mpes.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="doctor_password" class="form-control" placeholder="••••••••">
                    </div>
                    
                    <div class="form-group">
                        <label>License Verification No.</label>
                        <input type="text" name="license_no" class="form-control" placeholder="e.g. MED-2026-XYZ">
                    </div>

                    <div class="flex-row">
                        <div class="form-group">
                            <label>Specialist In</label>
                            <input type="text" name="specialist" class="form-control" placeholder="e.g. Cardiology">
                        </div>
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience" class="form-control" placeholder="e.g. 10">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Current Hospital Info</label>
                        <input type="text" name="hospital_info" class="form-control" placeholder="e.g. City General Hospital">
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-outline prev-step">&larr; Back</button>
                        <button type="submit" name="login_doctor" class="btn btn-primary" style="background:var(--secondary-color);">Login as Doctor</button>
                    </div>
                    <div class="text-center" style="margin-top:15px;">
                        <button type="button" class="btn btn-outline w-100 next-step" style="border-color:var(--secondary-color); color:var(--secondary-color);">Admin Access? Next &rarr;</button>
                    </div>
                </div>

                <!-- STEP 3: ADMIN LOGIN -->
                <div class="login-step">
                    <h3 style="margin-bottom:20px; color:var(--text-primary);"><i class="fa-solid fa-user-shield"></i> Admin Access</h3>
                    
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@mpes.com">
                    </div>
                    <div class="form-group">
                        <label>Secure Password</label>
                        <input type="password" name="admin_password" class="form-control" placeholder="••••••••">
                    </div>
                    
                    <div class="text-small" style="margin-bottom: 20px;">
                        <i class="fa-solid fa-lock" style="color:green;"></i> Full access to all data and analytics.
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn btn-outline prev-step">&larr; Back</button>
                        <button type="submit" name="login_admin" class="btn btn-primary" style="background:#1a202c;">Secure Login</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Update Step Indicators
        const stepsArray = document.querySelectorAll('.login-step');
        const stepDots = document.querySelectorAll('.step-dot');
        const nextBtns = document.querySelectorAll('.next-step');
        const prevBtns = document.querySelectorAll('.prev-step');
        
        let cStep = 0;

        function updateDots(index) {
            stepDots.forEach((dot, i) => {
                if(i <= index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        nextBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (cStep < stepsArray.length - 1) {
                    cStep++;
                    updateDots(cStep);
                }
            });
        });

        prevBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (cStep > 0) {
                    cStep--;
                    updateDots(cStep);
                }
            });
        });
    </script>
</body>
</html>
