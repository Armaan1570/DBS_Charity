<?php
require_once '../includes/db.php';
$pageTitle = 'Donations';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'insert') {
        $id     = nextID($conn,'Donation','Donation_ID','DN',2);
        $amount = (float)$_POST['Amount'];
        $date   = $conn->real_escape_string($_POST['Donation_Date']);
        $donor  = $conn->real_escape_string($_POST['Donor_ID']);
        $camp   = $conn->real_escape_string($_POST['Campaign_ID']);
        // Auto-create payment record
        $payId  = nextID($conn,'Payment','Payment_ID','P',3);
        $mode   = $conn->real_escape_string($_POST['Payment_Mode']);
        $conn->query("INSERT INTO Payment VALUES ('$payId','$mode','Success','$date')");
        $conn->query("INSERT INTO Donation VALUES ('$id',$amount,'$date','$donor','$camp','$payId')");
        $msg = "✔ Donation <strong>$id</strong> recorded. Payment <strong>$payId</strong> auto-created."; $msgType='success';
    }

    if ($_POST['action'] === 'update') {
        $id     = $conn->real_escape_string($_POST['Donation_ID']);
        $amount = (float)$_POST['Amount'];
        $date   = $conn->real_escape_string($_POST['Donation_Date']);
        $donor  = $conn->real_escape_string($_POST['Donor_ID']);
        $camp   = $conn->real_escape_string($_POST['Campaign_ID']);
        $conn->query("UPDATE Donation SET Amount=$amount,Donation_Date='$date',Donor_ID='$donor',Campaign_ID='$camp' WHERE Donation_ID='$id'");
        $msg = "✔ Donation updated."; $msgType='success';
    }

    if ($_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['Donation_ID']);
        $prow = $conn->query("SELECT Payment_ID FROM Donation WHERE Donation_ID='$id'")->fetch_assoc();
        $conn->query("DELETE FROM Donation WHERE Donation_ID='$id'");
        if ($prow) $conn->query("DELETE FROM Payment WHERE Payment_ID='".$prow['Payment_ID']."'");
        $msg = "🗑 Donation deleted."; $msgType='success';
    }
}

$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "AND (dn.Donation_ID LIKE '%$search%' OR d.Name LIKE '%$search%' OR cp.Title LIKE '%$search%')" : '';
$donations = $conn->query("
    SELECT dn.Donation_ID, dn.Amount, dn.Donation_Date,
           d.Donor_ID, d.Name AS DonorName,
           cp.Campaign_ID, cp.Title AS CampaignTitle,
           p.Payment_ID, p.Payment_Mode, p.Transaction_Status
    FROM Donation dn
    LEFT JOIN Donor d ON dn.Donor_ID=d.Donor_ID
    LEFT JOIN Campaign cp ON dn.Campaign_ID=cp.Campaign_ID
    LEFT JOIN Payment p ON dn.Payment_ID=p.Payment_ID
    WHERE 1=1 $where
    ORDER BY dn.Donation_Date DESC
");

$donors    = $conn->query("SELECT Donor_ID, Name FROM Donor ORDER BY Name");
$campaigns = $conn->query("SELECT Campaign_ID, Title FROM Campaign ORDER BY Title");
$sbadge    = ['Success'=>'badge-green','Failed'=>'badge-red','Pending'=>'badge-muted'];

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Donation <span>Records</span></h1>
            <p>Complete log of all donations made through the platform</p>
        </div>
        <button class="btn btn-gold" onclick="openModal('addDonModal')">+ Record Donation</button>
    </div>

    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="card mb-0">
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Search by donor, campaign…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </div>
                <?php if ($search): ?><a href="donations.php" class="btn btn-outline">✕ Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <table id="donTable">
                <thead><tr><th>ID</th><th>Donor</th><th>Campaign</th><th>Amount</th><th>Date</th><th>Mode</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $donations->fetch_assoc()):
                    $bc = $sbadge[$row['Transaction_Status']] ?? 'badge-muted';
                ?>
                <tr>
                    <td class="monospace text-gold"><?= htmlspecialchars($row['Donation_ID']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['DonorName'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['CampaignTitle'] ?? '—') ?></td>
                    <td class="text-green fw-bold">₹<?= number_format($row['Amount']) ?></td>
                    <td><?= $row['Donation_Date'] ?></td>
                    <td><?= htmlspecialchars($row['Payment_Mode'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $row['Transaction_Status'] ?? '—' ?></span></td>
                    <td class="td-actions">
                        <button class="btn btn-outline btn-sm" onclick="populateEdit('editDonModal',<?= json_encode($row) ?>)">✏</button>
                        <form method="POST" onsubmit="return confirm('Delete donation + payment?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="Donation_ID" value="<?= $row['Donation_ID'] ?>">
                            <button class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="nav-controls">
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('donTable');tableNav.goFirst()">⏮ First</button>
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('donTable');tableNav.goLast()">Last ⏭</button>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addDonModal">
    <div class="modal">
        <h2>Record New Donation</h2>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Donor *</label>
                    <select name="Donor_ID" required>
                        <option value="">-- Select Donor --</option>
                        <?php $donors->data_seek(0); while($d=$donors->fetch_assoc()): ?>
                        <option value="<?= $d['Donor_ID'] ?>"><?= htmlspecialchars($d['Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Campaign *</label>
                    <select name="Campaign_ID" required>
                        <option value="">-- Select Campaign --</option>
                        <?php $campaigns->data_seek(0); while($c=$campaigns->fetch_assoc()): ?>
                        <option value="<?= $c['Campaign_ID'] ?>"><?= htmlspecialchars($c['Title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Amount (₹) *</label><input type="number" name="Amount" min="1" step="0.01" required></div>
                <div class="form-group"><label>Donation Date *</label><input type="date" name="Donation_Date" required></div>
            </div>
            <div class="form-row single"><div class="form-group"><label>Payment Mode *</label>
                <select name="Payment_Mode" required>
                    <option value="UPI">UPI</option><option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option><option value="Net Banking">Net Banking</option>
                    <option value="Cash">Cash</option>
                </select>
            </div></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDonModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Record Donation</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editDonModal">
    <div class="modal">
        <h2>Edit Donation</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-row">
                <div class="form-group"><label>Donation ID</label><input type="text" name="Donation_ID" readonly style="opacity:0.5"></div>
                <div class="form-group"><label>Amount (₹)</label><input type="number" name="Amount" min="1" step="0.01"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Donor</label>
                    <select name="Donor_ID">
                        <option value="">-- Select --</option>
                        <?php $donors->data_seek(0); while($d=$donors->fetch_assoc()): ?>
                        <option value="<?= $d['Donor_ID'] ?>"><?= htmlspecialchars($d['Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Campaign</label>
                    <select name="Campaign_ID">
                        <option value="">-- Select --</option>
                        <?php $campaigns->data_seek(0); while($c=$campaigns->fetch_assoc()): ?>
                        <option value="<?= $c['Campaign_ID'] ?>"><?= htmlspecialchars($c['Title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row single"><div class="form-group"><label>Donation Date</label><input type="date" name="Donation_Date"></div></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editDonModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
