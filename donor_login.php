<?php
session_start();
require_once 'includes/db.php';

// Already logged in → go to donor portal
if (isset($_SESSION['donor_id'])) {
    header('Location: donor_portal.php');
    exit;
}

$error = '';
$success = '';

// ── REGISTER ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'register') {
    $name  = trim($_POST['Name'] ?? '');
    $email = trim($_POST['Email'] ?? '');
    $phone = trim($_POST['Phone'] ?? '');
    $addr  = trim($_POST['Address'] ?? '');
    $pass  = trim($_POST['Password'] ?? '');
    $conf  = trim($_POST['Confirm'] ?? '');

    if (!$name || !$email || !$phone || !$pass) {
        $error = 'Please fill in all required fields.';
    } elseif ($pass !== $conf) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $emailEsc = $conn->real_escape_string($email);
        $exists = $conn->query("SELECT Donor_ID FROM Donor WHERE Email='$emailEsc'")->fetch_assoc();
        if ($exists) {
            $error = 'An account with this email already exists. Please log in.';
        } else {
            $id    = nextID($conn, 'Donor', 'Donor_ID', 'D', 3);
            $nameE = $conn->real_escape_string($name);
            $phoneE= $conn->real_escape_string($phone);
            $addrE = $conn->real_escape_string($addr);
            $hash  = md5($pass);
            $conn->query("INSERT INTO Donor VALUES ('$id','$nameE','$emailEsc','$phoneE','$addrE','$hash')");
            $success = "Account created successfully! Your Donor ID is <strong>$id</strong>. You can now log in.";
        }
    }
}

// ── LOGIN ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'login') {
    $email = trim($_POST['Email'] ?? '');
    $pass  = md5(trim($_POST['Password'] ?? ''));
    $emailEsc = $conn->real_escape_string($email);
    $row = $conn->query("SELECT * FROM Donor WHERE Email='$emailEsc' AND Password='$pass'")->fetch_assoc();
    if ($row) {
        $_SESSION['donor_id']   = $row['Donor_ID'];
        $_SESSION['donor_name'] = $row['Name'];
        $_SESSION['donor_email']= $row['Email'];
        header('Location: donor_portal.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

$tab = $_GET['tab'] ?? 'login'; // login | register
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Login | CharityHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }

        .login-page {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }

        /* decorative background blobs */
        .login-page::before, .login-page::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            opacity: 0.12;
        }
        .login-page::before {
            width: 500px; height: 500px;
            background: var(--gold);
            top: -150px; left: -150px;
        }
        .login-page::after {
            width: 400px; height: 400px;
            background: var(--blue);
            bottom: -100px; right: -100px;
        }

        .login-container {
            width: 100%; max-width: 440px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 40px rgba(0,0,0,0.5);
            position: relative; z-index: 1;
            animation: fadeUp 0.35s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: none; }
        }

        .login-brand {
            text-align: center;
            margin-bottom: 1.8rem;
        }
        .login-brand .heart {
            font-size: 2.2rem;
            display: block;
            margin-bottom: 0.3rem;
        }
        .login-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem; color: var(--gold);
            font-weight: 700; margin-bottom: 2px;
        }
        .login-brand p { color: var(--text-muted); font-size: 0.88rem; }

        /* tabs */
        .tabs {
            display: flex;
            background: var(--bg2);
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 1.6rem;
            gap: 4px;
        }
        .tab-btn {
            flex: 1; padding: 0.55rem;
            background: transparent; border: none;
            border-radius: 6px;
            color: var(--text-muted);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem; font-weight: 500;
            cursor: pointer; transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--gold);
            color: #0e0e12;
            font-weight: 600;
        }

        /* form panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .divider {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.8rem;
            margin: 1rem 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute; top: 50%;
            width: 38%; height: 1px;
            background: var(--border);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .admin-link {
            display: block; text-align: center;
            margin-top: 1.2rem;
            color: var(--text-muted); font-size: 0.83rem;
        }
        .admin-link a { color: var(--gold); text-decoration: none; }
        .admin-link a:hover { text-decoration: underline; }

        .success-box {
            background: rgba(76,175,110,0.1);
            border: 1px solid rgba(76,175,110,0.3);
            border-radius: 8px;
            padding: 0.9rem 1rem;
            color: var(--green);
            font-size: 0.88rem;
            margin-bottom: 1rem;
        }

        .pw-toggle { position: relative; }
        .pw-toggle input { padding-right: 2.5rem; }
        .pw-toggle .eye {
            position: absolute; right: 0.75rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-muted); cursor: pointer;
            font-size: 1rem; padding: 0;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="brand" href="index.php">
        <span class="brand-icon">❤</span>
        <span>CharityHub</span>
    </a>
    <ul class="nav-links">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="pages/campaigns.php">Campaigns</a></li>
        <li><a href="donor_login.php" class="active">Donor Login</a></li>
    </ul>
</nav>

<div class="login-page">
    <div class="login-container">

        <div class="login-brand">
            <span class="heart">❤</span>
            <h1>CharityHub</h1>
            <p>Donor Portal — Make a Difference</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-box"><?= $success ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $tab==='login'?'active':'' ?>" onclick="switchTab('login')">Sign In</button>
            <button class="tab-btn <?= $tab==='register'?'active':'' ?>" onclick="switchTab('register')">Register</button>
        </div>

        <!-- LOGIN -->
        <div class="tab-panel <?= $tab==='login'?'active':'' ?>" id="panel-login">
            <form method="POST">
                <input type="hidden" name="form" value="login">
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="Email" required placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['Email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <div class="pw-toggle">
                        <input type="password" name="Password" id="loginPw" required placeholder="••••••••">
                        <button type="button" class="eye" onclick="togglePw('loginPw',this)">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center;padding:0.7rem;font-size:0.95rem;margin-top:0.5rem">
                    Sign In →
                </button>
            </form>
            <div class="divider">or</div>
            <p style="text-align:center;font-size:0.85rem;color:var(--text-muted)">
                Don't have an account?
                <a href="#" onclick="switchTab('register')" style="color:var(--gold);text-decoration:none">Register here</a>
            </p>
        </div>

        <!-- REGISTER -->
        <div class="tab-panel <?= $tab==='register'?'active':'' ?>" id="panel-register">
            <form method="POST">
                <input type="hidden" name="form" value="register">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="Name" required placeholder="Rahul Kumar"
                               value="<?= htmlspecialchars($_POST['Name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="text" name="Phone" required placeholder="9876543210" maxlength="15"
                               value="<?= htmlspecialchars($_POST['Phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="Email" required placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['Email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="Address" placeholder="City, State"
                           value="<?= htmlspecialchars($_POST['Address'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="pw-toggle">
                            <input type="password" name="Password" id="regPw" required placeholder="Min 6 chars">
                            <button type="button" class="eye" onclick="togglePw('regPw',this)">👁</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="pw-toggle">
                            <input type="password" name="Confirm" id="regPw2" required placeholder="Re-enter">
                            <button type="button" class="eye" onclick="togglePw('regPw2',this)">👁</button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center;padding:0.7rem;font-size:0.95rem;margin-top:0.5rem">
                    Create Account →
                </button>
            </form>
            <div class="divider">or</div>
            <p style="text-align:center;font-size:0.85rem;color:var(--text-muted)">
                Already have an account?
                <a href="#" onclick="switchTab('login')" style="color:var(--gold);text-decoration:none">Sign in here</a>
            </p>
        </div>

        <span class="admin-link">
            Are you an admin? <a href="index.php">Go to Admin Dashboard →</a>
        </span>

    </div>
</div>

<footer class="footer">
    <div class="footer-inner">
        <span class="brand-icon">❤</span>
        <span>CharityHub — Online Donation &amp; Charity Management System</span>
        <span class="footer-sub">BCSE302L &bull; DA-2 &bull; VIT</span>
    </div>
</footer>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelector('#panel-' + tab).classList.add('active');
    event.target.classList.add('active');
}
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
    else { inp.type = 'password'; btn.textContent = '👁'; }
}
</script>
</body>
</html>
