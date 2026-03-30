<?php
session_start();
require_once 'includes/db.php';

// Auth guard
if (!isset($_SESSION['donor_id'])) {
    header('Location: donor_login.php');
    exit;
}

$donorId   = $_SESSION['donor_id'];
$donorName = $_SESSION['donor_name'];
$donorEmail= $_SESSION['donor_email'];

// Fetch donor full record
$donor = $conn->query("SELECT * FROM Donor WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'")->fetch_assoc();

$msg = '';

// ── UPDATE PROFILE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name  = $conn->real_escape_string(trim($_POST['Name']));
    $phone = $conn->real_escape_string(trim($_POST['Phone']));
    $addr  = $conn->real_escape_string(trim($_POST['Address']));
    $conn->query("UPDATE Donor SET Name='$name',Phone='$phone',Address='$addr' WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'");
    $_SESSION['donor_name'] = $name;
    $donorName = $name;
    $msg = "✔ Profile updated successfully."; $msgType = 'success';
    $donor = $conn->query("SELECT * FROM Donor WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'")->fetch_assoc();
}

// ── CHANGE PASSWORD ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $old  = md5(trim($_POST['OldPassword']));
    $new  = trim($_POST['NewPassword']);
    $conf = trim($_POST['ConfirmPassword']);
    if ($donor['Password'] !== $old) {
        $msg = 'Current password is incorrect.'; $msgType = 'error';
    } elseif (strlen($new) < 6) {
        $msg = 'New password must be at least 6 characters.'; $msgType = 'error';
    } elseif ($new !== $conf) {
        $msg = 'New passwords do not match.'; $msgType = 'error';
    } else {
        $hash = md5($new);
        $conn->query("UPDATE Donor SET Password='$hash' WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'");
        $msg = '✔ Password changed successfully.'; $msgType = 'success';
    }
}

// ── NEW DONATION ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'donate') {
    $campId = $conn->real_escape_string($_POST['Campaign_ID']);
    $amount = (float)$_POST['Amount'];
    $mode   = $conn->real_escape_string($_POST['Payment_Mode']);
    $date   = date('Y-m-d');
    if ($amount < 1) {
        $msg = 'Please enter a valid donation amount.'; $msgType = 'error';
    } else {
        $payId = nextID($conn, 'Payment', 'Payment_ID', 'P', 3);
        $donId = nextID($conn, 'Donation', 'Donation_ID', 'DN', 2);
        $conn->query("INSERT INTO Payment VALUES ('$payId','$mode','Success','$date')");
        $conn->query("INSERT INTO Donation VALUES ('$donId',$amount,'$date','" . $conn->real_escape_string($donorId) . "','$campId','$payId')");
        $msg = "✔ Donation <strong>₹" . number_format($amount) . "</strong> recorded! Donation ID: <strong>$donId</strong>."; $msgType = 'success';
    }
}

// ── MY DONATIONS ──────────────────────────────────────────────────────
$myDonations = $conn->query("
    SELECT dn.Donation_ID, dn.Amount, dn.Donation_Date,
           cp.Title AS CampaignTitle, ch.Charity_Name,
           p.Payment_Mode, p.Transaction_Status
    FROM Donation dn
    LEFT JOIN Campaign cp ON dn.Campaign_ID = cp.Campaign_ID
    LEFT JOIN Charity ch  ON cp.Charity_ID  = ch.Charity_ID
    LEFT JOIN Payment p   ON dn.Payment_ID  = p.Payment_ID
    WHERE dn.Donor_ID = '" . $conn->real_escape_string($donorId) . "'
    ORDER BY dn.Donation_Date DESC
");

$totalGiven = $conn->query("SELECT COALESCE(SUM(Amount),0) s FROM Donation WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'")->fetch_assoc()['s'];
$donCount   = $conn->query("SELECT COUNT(*) c FROM Donation WHERE Donor_ID='" . $conn->real_escape_string($donorId) . "'")->fetch_assoc()['c'];

// Active campaigns for donate form
$campaigns = $conn->query("SELECT Campaign_ID, Title, Target_Amount FROM Campaign WHERE Status='Active' ORDER BY Title");

$sbadge = ['Success'=>'badge-green','Failed'=>'badge-red','Pending'=>'badge-muted'];
$pageTitle = 'My Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Portal | CharityHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .portal-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        .sidebar {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            position: sticky; top: 78px;
        }
        .donor-avatar {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--gold-dim);
            border: 2px solid var(--gold);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem; color: var(--gold);
            margin: 0 auto 0.75rem;
        }
        .sidebar-name {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem; color: var(--text);
            font-weight: 700;
        }
        .sidebar-id {
            text-align: center;
            font-size: 0.78rem; color: var(--text-muted);
            font-family: monospace; margin-top: 2px;
        }
        .sidebar-email {
            text-align: center;
            font-size: 0.8rem; color: var(--text-muted);
            margin-top: 4px; margin-bottom: 1.2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-stat {
            display: flex; justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }
        .sidebar-stat:last-of-type { border-bottom: none; }
        .sidebar-stat .label { color: var(--text-muted); }
        .sidebar-stat .value { color: var(--gold); font-weight: 600; }
        .sidebar-nav { margin-top: 1.2rem; display: flex; flex-direction: column; gap: 5px; }
        .sidebar-nav a {
            padding: 0.55rem 0.75rem;
            border-radius: 7px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: var(--gold-dim); color: var(--gold); }
        .logout-btn {
            width: 100%; margin-top: 1rem;
            background: rgba(224,82,82,0.08);
            border: 1px solid rgba(224,82,82,0.25);
            color: var(--red);
            border-radius: 8px;
            padding: 0.55rem;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(224,82,82,0.18); }

        .section { display: none; }
        .section.active { display: block; }

        @media (max-width: 768px) {
            .portal-grid { grid-template-columns: 1fr; }
            .sidebar { position: static; }
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
        <li><a href="donor_portal.php" class="active">My Portal</a></li>
        <li><a href="donor_logout.php" style="color:var(--red)">Sign Out</a></li>
    </ul>
</nav>

<div class="page-wrap">

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?? 'success' ?>" style="margin-bottom:1rem"><?= $msg ?></div>
    <?php endif; ?>

    <div class="portal-grid">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="donor-avatar"><?= strtoupper(substr($donorName, 0, 1)) ?></div>
            <div class="sidebar-name"><?= htmlspecialchars($donorName) ?></div>
            <div class="sidebar-id"><?= htmlspecialchars($donorId) ?></div>
            <div class="sidebar-email"><?= htmlspecialchars($donorEmail) ?></div>

            <div class="sidebar-stat">
                <span class="label">Total Donated</span>
                <span class="value">₹<?= number_format($totalGiven) ?></span>
            </div>
            <div class="sidebar-stat">
                <span class="label">Donations Made</span>
                <span class="value"><?= $donCount ?></span>
            </div>

            <nav class="sidebar-nav">
                <a href="#" onclick="showSection('my-donations')" class="active" id="nav-my-donations">📋 My Donations</a>
                <a href="#" onclick="showSection('donate-now')"  id="nav-donate-now">❤ Donate Now</a>
                <a href="#" onclick="showSection('my-profile')"  id="nav-my-profile">👤 My Profile</a>
                <a href="#" onclick="showSection('change-pw')"   id="nav-change-pw">🔒 Change Password</a>
            </nav>

            <form method="GET" action="donor_logout.php">
                <button class="logout-btn">Sign Out →</button>
            </form>
        </div>

        <!-- MAIN CONTENT -->
        <div>

            <!-- MY DONATIONS -->
            <div class="section active" id="my-donations">
                <div class="card mb-0">
                    <h2>📋 My Donation History</h2>
                    <?php if ($donCount == 0): ?>
                        <p class="text-muted" style="padding:1rem 0">You haven't made any donations yet. Use <strong>Donate Now</strong> to get started!</p>
                    <?php else: ?>
                    <div class="table-wrap">
                        <table id="myDonTable">
                            <thead><tr><th>ID</th><th>Campaign</th><th>Charity</th><th>Amount</th><th>Date</th><th>Mode</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php $myDonations->data_seek(0); while($row = $myDonations->fetch_assoc()):
                                $bc = $sbadge[$row['Transaction_Status']] ?? 'badge-muted';
                            ?>
                            <tr>
                                <td class="monospace text-gold"><?= htmlspecialchars($row['Donation_ID']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['CampaignTitle'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['Charity_Name'] ?? '—') ?></td>
                                <td class="text-green fw-bold">₹<?= number_format($row['Amount']) ?></td>
                                <td><?= $row['Donation_Date'] ?></td>
                                <td><?= htmlspecialchars($row['Payment_Mode'] ?? '—') ?></td>
                                <td><span class="badge <?= $bc ?>"><?= $row['Transaction_Status'] ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="nav-controls">
                        <button class="btn btn-nav btn-sm" onclick="tableNav.init('myDonTable');tableNav.goFirst()">⏮ First</button>
                        <button class="btn btn-nav btn-sm" onclick="tableNav.init('myDonTable');tableNav.goLast()">Last ⏭</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DONATE NOW -->
            <div class="section" id="donate-now">
                <div class="card mb-0">
                    <h2>❤ Make a Donation</h2>
                    <p class="text-muted" style="margin-bottom:1.2rem;font-size:0.9rem">Support an active campaign below. Your contribution makes a real difference.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="donate">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Campaign *</label>
                                <select name="Campaign_ID" required>
                                    <option value="">-- Select Active Campaign --</option>
                                    <?php $campaigns->data_seek(0); while($c=$campaigns->fetch_assoc()): ?>
                                    <option value="<?= $c['Campaign_ID'] ?>">
                                        <?= htmlspecialchars($c['Title']) ?> (Target: ₹<?= number_format($c['Target_Amount']) ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount (₹) *</label>
                                <input type="number" name="Amount" min="1" step="1" placeholder="e.g. 500" required>
                            </div>
                        </div>
                        <div class="form-row single">
                            <div class="form-group">
                                <label>Payment Mode *</label>
                                <select name="Payment_Mode" required>
                                    <option value="UPI">UPI</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="Net Banking">Net Banking</option>
                                    <option value="Cash">Cash</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quick amount buttons -->
                        <div style="margin-bottom:1rem">
                            <label style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Quick Amounts</label>
                            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:6px">
                                <?php foreach ([100,500,1000,5000,10000] as $amt): ?>
                                <button type="button" class="btn btn-outline btn-sm"
                                    onclick="document.querySelector('[name=Amount]').value=<?= $amt ?>">
                                    ₹<?= number_format($amt) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold" style="padding:0.65rem 2rem">Donate Now ❤</button>
                    </form>
                </div>
            </div>

            <!-- MY PROFILE -->
            <div class="section" id="my-profile">
                <div class="card mb-0">
                    <h2>👤 My Profile</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Donor ID</label>
                                <input type="text" value="<?= htmlspecialchars($donor['Donor_ID']) ?>" readonly style="opacity:0.5">
                            </div>
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="Name" required value="<?= htmlspecialchars($donor['Name']) ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email (read-only)</label>
                                <input type="email" value="<?= htmlspecialchars($donor['Email']) ?>" readonly style="opacity:0.5">
                            </div>
                            <div class="form-group">
                                <label>Phone *</label>
                                <input type="text" name="Phone" required value="<?= htmlspecialchars($donor['Phone']) ?>">
                            </div>
                        </div>
                        <div class="form-row single">
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="Address" value="<?= htmlspecialchars($donor['Address']) ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gold">Save Profile</button>
                    </form>
                </div>
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="section" id="change-pw">
                <div class="card mb-0">
                    <h2>🔒 Change Password</h2>
                    <form method="POST" style="max-width:380px">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="OldPassword" required>
                        </div>
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="NewPassword" required placeholder="Min 6 characters">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="ConfirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-gold">Update Password</button>
                    </form>
                </div>
            </div>

        </div><!-- /main -->
    </div><!-- /portal-grid -->
</div>

<footer class="footer">
    <div class="footer-inner">
        <span class="brand-icon">❤</span>
        <span>CharityHub — Online Donation &amp; Charity Management System</span>
        <span class="footer-sub">BCSE302L &bull; DA-2 &bull; VIT</span>
    </div>
</footer>

<script src="js/main.js"></script>
<script>
function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.getElementById('nav-' + id).classList.add('active');
}
</script>
</body>
</html>
