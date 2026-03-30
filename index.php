<?php
require_once 'includes/db.php';
$pageTitle = 'Dashboard';

// Stats
$stats = [];
foreach (['Donor'=>'Donors','Charity'=>'Charities','Campaign'=>'Campaigns','Donation'=>'Donations','Payment'=>'Payments','Category'=>'Categories'] as $tbl => $label) {
    $r = $conn->query("SELECT COUNT(*) c FROM $tbl")->fetch_assoc();
    $stats[$label] = $r['c'];
}

// Total donations amount
$totalDon = $conn->query("SELECT SUM(Amount) s FROM Donation WHERE 1")->fetch_assoc()['s'] ?? 0;

// Active campaigns
$activeCamp = $conn->query("SELECT COUNT(*) c FROM Campaign WHERE Status='Active'")->fetch_assoc()['c'];

// Recent donations
$recentDon = $conn->query("
    SELECT dn.Donation_ID, dn.Amount, dn.Donation_Date, d.Name AS DonorName,
           cp.Title AS CampaignTitle, p.Transaction_Status
    FROM Donation dn
    LEFT JOIN Donor d ON dn.Donor_ID = d.Donor_ID
    LEFT JOIN Campaign cp ON dn.Campaign_ID = cp.Campaign_ID
    LEFT JOIN Payment p ON dn.Payment_ID = p.Payment_ID
    ORDER BY dn.Donation_Date DESC LIMIT 5
");

// Campaign progress
$campaigns = $conn->query("
    SELECT cp.Campaign_ID, cp.Title, cp.Target_Amount,
           COALESCE(SUM(dn.Amount),0) AS Raised
    FROM Campaign cp
    LEFT JOIN Donation dn ON cp.Campaign_ID = dn.Campaign_ID
    GROUP BY cp.Campaign_ID
    LIMIT 4
");

include 'includes/header.php';
?>

<div class="page-wrap">

    <div class="hero">
        <h1>Welcome to <em>CharityHub</em></h1>
        <p>Online Donation &amp; Charity Management System — track donors, campaigns, and donations in one place.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $stats['Donors'] ?></div>
            <div class="stat-label">Total Donors</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏛️</div>
            <div class="stat-value"><?= $stats['Charities'] ?></div>
            <div class="stat-label">Charities</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-value"><?= $activeCamp ?></div>
            <div class="stat-label">Active Campaigns</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">₹<?= number_format($totalDon/1000,0) ?>K</div>
            <div class="stat-label">Total Raised</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= $stats['Donations'] ?></div>
            <div class="stat-label">Donations</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💳</div>
            <div class="stat-value"><?= $stats['Payments'] ?></div>
            <div class="stat-label">Payments</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.25rem;flex-wrap:wrap">

        <div class="card">
            <h2>Recent Donations</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Donor</th><th>Amount</th><th>Campaign</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $recentDon->fetch_assoc()): 
                    $badgeClass = $row['Transaction_Status'] === 'Success' ? 'badge-green' : ($row['Transaction_Status'] === 'Failed' ? 'badge-red' : 'badge-muted');
                ?>
                <tr>
                    <td class="monospace text-muted"><?= htmlspecialchars($row['Donation_ID']) ?></td>
                    <td><?= htmlspecialchars($row['DonorName'] ?? '—') ?></td>
                    <td class="text-gold fw-bold">₹<?= number_format($row['Amount']) ?></td>
                    <td><?= htmlspecialchars($row['CampaignTitle'] ?? '—') ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $row['Transaction_Status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Campaign Progress</h2>
            <?php while($cp = $campaigns->fetch_assoc()):
                $pct = $cp['Target_Amount'] > 0 ? min(100, round($cp['Raised']/$cp['Target_Amount']*100)) : 0;
            ?>
            <div class="progress-bar-wrap mt-1">
                <div class="progress-label">
                    <span><?= htmlspecialchars($cp['Title']) ?></span>
                    <span class="text-gold"><?= $pct ?>%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                </div>
                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:3px">
                    ₹<?= number_format($cp['Raised']) ?> of ₹<?= number_format($cp['Target_Amount']) ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
