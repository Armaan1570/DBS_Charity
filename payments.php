<?php
require_once '../includes/db.php';
$pageTitle = 'Payments';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'insert') {
        $id   = nextID($conn,'Payment','Payment_ID','P',3);
        $mode = $conn->real_escape_string($_POST['Payment_Mode']);
        $stat = $conn->real_escape_string($_POST['Transaction_Status']);
        $date = $conn->real_escape_string($_POST['Transaction_Date']);
        $conn->query("INSERT INTO Payment VALUES ('$id','$mode','$stat','$date')");
        $msg = "✔ Payment record added (ID: $id)."; $msgType='success';
    }

    if ($_POST['action'] === 'update') {
        $id   = $conn->real_escape_string($_POST['Payment_ID']);
        $mode = $conn->real_escape_string($_POST['Payment_Mode']);
        $stat = $conn->real_escape_string($_POST['Transaction_Status']);
        $date = $conn->real_escape_string($_POST['Transaction_Date']);
        $conn->query("UPDATE Payment SET Payment_Mode='$mode',Transaction_Status='$stat',Transaction_Date='$date' WHERE Payment_ID='$id'");
        $msg = "✔ Payment updated."; $msgType='success';
    }

    if ($_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['Payment_ID']);
        $conn->query("UPDATE Donation SET Payment_ID=NULL WHERE Payment_ID='$id'");
        $conn->query("DELETE FROM Payment WHERE Payment_ID='$id'");
        $msg = "🗑 Payment deleted."; $msgType='success';
    }
}

$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "WHERE Payment_ID LIKE '%$search%' OR Payment_Mode LIKE '%$search%' OR Transaction_Status LIKE '%$search%'" : '';
$payments = $conn->query("SELECT * FROM Payment $where ORDER BY Transaction_Date DESC");
$statusBadge = ['Success'=>'badge-green','Failed'=>'badge-red','Pending'=>'badge-muted'];

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Payment <span>Records</span></h1>
            <p>Track all payment transactions</p>
        </div>
        <button class="btn btn-gold" onclick="openModal('addPayModal')">+ Add Payment</button>
    </div>

    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="card mb-0">
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Search payments…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </div>
                <?php if ($search): ?><a href="payments.php" class="btn btn-outline">✕ Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <table id="payTable">
                <thead><tr><th>Payment ID</th><th>Mode</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $payments->fetch_assoc()):
                    $bc = $statusBadge[$row['Transaction_Status']] ?? 'badge-muted';
                ?>
                <tr>
                    <td class="monospace text-gold"><?= htmlspecialchars($row['Payment_ID']) ?></td>
                    <td><?= htmlspecialchars($row['Payment_Mode']) ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $row['Transaction_Status'] ?></span></td>
                    <td><?= $row['Transaction_Date'] ?></td>
                    <td class="td-actions">
                        <button class="btn btn-outline btn-sm" onclick="populateEdit('editPayModal',<?= json_encode($row) ?>)">✏ Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this payment?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="Payment_ID" value="<?= $row['Payment_ID'] ?>">
                            <button class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="nav-controls">
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('payTable');tableNav.goFirst()">⏮ First</button>
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('payTable');tableNav.goLast()">Last ⏭</button>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addPayModal">
    <div class="modal">
        <h2>Add Payment Record</h2>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Payment Mode *</label>
                    <select name="Payment_Mode" required>
                        <option value="UPI">UPI</option><option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option><option value="Net Banking">Net Banking</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div class="form-group"><label>Status *</label>
                    <select name="Transaction_Status" required>
                        <option value="Success">Success</option><option value="Pending">Pending</option><option value="Failed">Failed</option>
                    </select>
                </div>
            </div>
            <div class="form-row single"><div class="form-group"><label>Transaction Date *</label><input type="date" name="Transaction_Date" required></div></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addPayModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Add Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editPayModal">
    <div class="modal">
        <h2>Edit Payment</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-row">
                <div class="form-group"><label>Payment ID</label><input type="text" name="Payment_ID" readonly style="opacity:0.5"></div>
                <div class="form-group"><label>Payment Mode *</label>
                    <select name="Payment_Mode" required>
                        <option value="UPI">UPI</option><option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option><option value="Net Banking">Net Banking</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Status</label>
                    <select name="Transaction_Status">
                        <option value="Success">Success</option><option value="Pending">Pending</option><option value="Failed">Failed</option>
                    </select>
                </div>
                <div class="form-group"><label>Transaction Date</label><input type="date" name="Transaction_Date"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editPayModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
