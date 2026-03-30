<?php
require_once '../includes/db.php';
$pageTitle = 'Reports & Analytics';

// ── Aggregated stats ──────────────────────────────────────────────────
$totalDon   = $conn->query("SELECT COALESCE(SUM(Amount),0) s FROM Donation")->fetch_assoc()['s'];
$totalCount = $conn->query("SELECT COUNT(*) c FROM Donation")->fetch_assoc()['c'];
$avgDon     = $totalCount > 0 ? round($totalDon/$totalCount, 2) : 0;

// Top donors
$topDonors = $conn->query("
    SELECT d.Name, COUNT(*) AS cnt, SUM(dn.Amount) AS total
    FROM Donation dn JOIN Donor d ON dn.Donor_ID=d.Donor_ID
    GROUP BY dn.Donor_ID ORDER BY total DESC LIMIT 5
");

// Campaign performance
$campPerf = $conn->query("
    SELECT cp.Title, cp.Target_Amount,
           COALESCE(SUM(dn.Amount),0) AS Raised,
           COUNT(dn.Donation_ID) AS DonCount,
           cp.Status
    FROM Campaign cp LEFT JOIN Donation dn ON cp.Campaign_ID=dn.Campaign_ID
    GROUP BY cp.Campaign_ID ORDER BY Raised DESC
");

// Payment mode breakdown
$payModes = $conn->query("
    SELECT p.Payment_Mode, COUNT(*) AS cnt, COALESCE(SUM(dn.Amount),0) AS total
    FROM Payment p JOIN Donation dn ON p.Payment_ID=dn.Payment_ID
    WHERE p.Transaction_Status='Success'
    GROUP BY p.Payment_Mode ORDER BY total DESC
");

// Category-wise collection
$catWise = $conn->query("
    SELECT cat.Category_Name, COALESCE(SUM(dn.Amount),0) AS total, COUNT(dn.Donation_ID) AS cnt
    FROM Category cat
    JOIN Charity ch ON cat.Category_ID=ch.Category_ID
    JOIN Campaign cp ON ch.Charity_ID=cp.Charity_ID
    LEFT JOIN Donation dn ON cp.Campaign_ID=dn.Campaign_ID
    GROUP BY cat.Category_ID ORDER BY total DESC
");

// ── Receipt generator ─────────────────────────────────────────────────
$receipt = null;
if (isset($_GET['receipt']) && !empty($_GET['receipt'])) {
    $rid = $conn->real_escape_string(trim($_GET['receipt']));
    $receipt = $conn->query("
        SELECT dn.Donation_ID, dn.Amount, dn.Donation_Date,
               d.Name AS DonorName, d.Email AS DonorEmail, d.Phone AS DonorPhone,
               cp.Title AS CampaignTitle, cp.Description AS CampDesc,
               ch.Charity_Name, ch.Registration_No,
               p.Payment_ID, p.Payment_Mode, p.Transaction_Status, p.Transaction_Date
        FROM Donation dn
        LEFT JOIN Donor d ON dn.Donor_ID=d.Donor_ID
        LEFT JOIN Campaign cp ON dn.Campaign_ID=cp.Campaign_ID
        LEFT JOIN Charity ch ON cp.Charity_ID=ch.Charity_ID
        LEFT JOIN Payment p ON dn.Payment_ID=p.Payment_ID
        WHERE dn.Donation_ID='$rid'
    ")->fetch_assoc();
}

// All donation IDs for receipt dropdown
$allDonIDs = $conn->query("SELECT Donation_ID FROM Donation ORDER BY Donation_Date DESC");

$statusBadge = ['Active'=>'badge-green','Completed'=>'badge-gold','Paused'=>'badge-blue','Cancelled'=>'badge-red'];

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Reports &amp; <span>Analytics</span></h1>
            <p>Donation insights, campaign performance, and receipt generation</p>
        </div>
    </div>

    <!-- SUMMARY STATS -->
    <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:600px;margin-bottom:2rem">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">₹<?= number_format($totalDon) ?></div>
            <div class="stat-label">Total Collected</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= $totalCount ?></div>
            <div class="stat-label">Total Donations</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value">₹<?= number_format($avgDon) ?></div>
            <div class="stat-label">Average Donation</div>
        </div>
    </div>

    <div class="report-grid">

        <!-- TOP DONORS -->
        <div class="card">
            <h2>🏆 Top Donors</h2>
            <table>
                <thead><tr><th>Donor</th><th>Donations</th><th>Total Given</th></tr></thead>
                <tbody>
                <?php while($row = $topDonors->fetch_assoc()): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($row['Name']) ?></td>
                    <td><?= $row['cnt'] ?></td>
                    <td class="text-gold fw-bold">₹<?= number_format($row['total']) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- PAYMENT MODES -->
        <div class="card">
            <h2>💳 Payment Mode Breakdown</h2>
            <?php $payModes->data_seek(0); $payTotal = array_sum(array_column($conn->query("SELECT COALESCE(SUM(dn.Amount),0) t FROM Payment p JOIN Donation dn ON p.Payment_ID=dn.Payment_ID WHERE p.Transaction_Status='Success' GROUP BY p.Payment_Mode")->fetch_all(),'t'));
            $payModes->data_seek(0);
            while($row = $payModes->fetch_assoc()):
                $pct = $payTotal > 0 ? round($row['total']/$payTotal*100) : 0;
            ?>
            <div class="progress-bar-wrap mt-1">
                <div class="progress-label">
                    <span><?= htmlspecialchars($row['Payment_Mode']) ?> (<?= $row['cnt'] ?>)</span>
                    <span class="text-gold">₹<?= number_format($row['total']) ?></span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- CAMPAIGN PERFORMANCE -->
        <div class="card" style="grid-column: 1 / -1">
            <h2>🎯 Campaign Performance</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Campaign</th><th>Target</th><th>Raised</th><th>Donations</th><th>Progress</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while($row = $campPerf->fetch_assoc()):
                        $pct = $row['Target_Amount'] > 0 ? min(100,round($row['Raised']/$row['Target_Amount']*100)) : 0;
                        $bc  = $statusBadge[$row['Status']] ?? 'badge-muted';
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($row['Title']) ?></td>
                        <td>₹<?= number_format($row['Target_Amount']) ?></td>
                        <td class="text-green fw-bold">₹<?= number_format($row['Raised']) ?></td>
                        <td><?= $row['DonCount'] ?></td>
                        <td style="min-width:120px">
                            <div class="progress-track"><div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div></div>
                            <span style="font-size:0.75rem;color:var(--text-muted)"><?= $pct ?>%</span>
                        </td>
                        <td><span class="badge <?= $bc ?>"><?= $row['Status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CATEGORY ANALYSIS -->
        <div class="card">
            <h2>📁 Category-wise Collection</h2>
            <?php
            $catRows = []; while($r=$catWise->fetch_assoc()) $catRows[] = $r;
            $catMax  = max(array_column($catRows,'total') ?: [1]);
            foreach($catRows as $row):
                $pct = $catMax > 0 ? round($row['total']/$catMax*100) : 0;
            ?>
            <div class="progress-bar-wrap mt-1">
                <div class="progress-label">
                    <span><?= htmlspecialchars($row['Category_Name']) ?></span>
                    <span class="text-gold">₹<?= number_format($row['total']) ?></span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- DONATION RECEIPT GENERATOR (Application-Based Processing) -->
        <div class="card">
            <h2>🧾 Donation Receipt Generator</h2>
            <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:1rem">
                Generate an official donation receipt for any recorded donation.
            </p>
            <form method="GET" style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap">
                <div class="form-group" style="flex:1;margin:0">
                    <label>Select Donation ID</label>
                    <select name="receipt" required>
                        <option value="">-- Choose Donation --</option>
                        <?php while($d=$allDonIDs->fetch_assoc()): ?>
                        <option value="<?= $d['Donation_ID'] ?>" <?= (($_GET['receipt']??'')===$d['Donation_ID'])?'selected':'' ?>>
                            <?= $d['Donation_ID'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-gold">Generate Receipt</button>
            </form>

            <?php if ($receipt): ?>
            <div id="receiptBox" style="
                margin-top:1.5rem;
                background:var(--bg2);
                border:1px solid var(--border);
                border-radius:var(--radius-sm);
                padding:1.5rem;
                font-size:0.88rem;
            ">
                <div style="text-align:center;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:1rem">
                    <div style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--gold);font-weight:700">❤ CharityHub</div>
                    <div style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.08em">Official Donation Receipt</div>
                </div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    $fields = [
                        'Receipt / Donation ID' => $receipt['Donation_ID'],
                        'Donor Name'            => $receipt['DonorName'],
                        'Donor Email'           => $receipt['DonorEmail'],
                        'Donor Phone'           => $receipt['DonorPhone'],
                        'Campaign'              => $receipt['CampaignTitle'],
                        'Charity'               => $receipt['Charity_Name'],
                        'Charity Reg No'        => $receipt['Registration_No'],
                        'Donation Amount'       => '₹' . number_format($receipt['Amount'], 2),
                        'Donation Date'         => $receipt['Donation_Date'],
                        'Payment ID'            => $receipt['Payment_ID'],
                        'Payment Mode'          => $receipt['Payment_Mode'],
                        'Transaction Status'    => $receipt['Transaction_Status'],
                    ];
                    foreach ($fields as $k => $v):
                    ?>
                    <tr>
                        <td style="padding:5px 0;color:var(--text-muted);width:45%"><?= $k ?></td>
                        <td style="padding:5px 0;color:var(--text);font-weight:500"><?= htmlspecialchars($v ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <div style="margin-top:1rem;padding-top:0.75rem;border-top:1px solid var(--border);text-align:center;color:var(--text-muted);font-size:0.78rem">
                    This is an auto-generated receipt. Thank you for your generous contribution.<br>
                    <strong style="color:var(--gold)">CharityHub — Making a Difference Together</strong>
                </div>
            </div>
            <div style="margin-top:0.75rem;display:flex;gap:0.75rem">
                <button class="btn btn-outline btn-sm" onclick="window.print()">🖨 Print Receipt</button>
                <button class="btn btn-outline btn-sm" onclick="copyReceipt()">📋 Copy</button>
            </div>
            <script>
            function copyReceipt() {
                const text = document.getElementById('receiptBox').innerText;
                navigator.clipboard.writeText(text).then(() => alert('Receipt copied to clipboard!'));
            }
            </script>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
