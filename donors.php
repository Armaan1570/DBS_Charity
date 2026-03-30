<?php
require_once '../includes/db.php';
$pageTitle = 'Donors';
$msg = '';

// INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'insert') {
        $id   = nextID($conn, 'Donor', 'Donor_ID', 'D', 3);
        $name = $conn->real_escape_string(trim($_POST['Name']));
        $email= $conn->real_escape_string(trim($_POST['Email']));
        $phone= $conn->real_escape_string(trim($_POST['Phone']));
        $addr = $conn->real_escape_string(trim($_POST['Address']));
        $pass = md5(trim($_POST['Password']));
        $sql  = "INSERT INTO Donor VALUES ('$id','$name','$email','$phone','$addr','$pass')";
        $msg  = $conn->query($sql) ? "✔ Donor <strong>$name</strong> added successfully (ID: $id)." : "❌ Error: " . $conn->error;
        $msgType = $conn->affected_rows > 0 ? 'success' : 'error';
    }

    if ($_POST['action'] === 'update') {
        $id   = $conn->real_escape_string($_POST['Donor_ID']);
        $name = $conn->real_escape_string(trim($_POST['Name']));
        $email= $conn->real_escape_string(trim($_POST['Email']));
        $phone= $conn->real_escape_string(trim($_POST['Phone']));
        $addr = $conn->real_escape_string(trim($_POST['Address']));
        $sql  = "UPDATE Donor SET Name='$name',Email='$email',Phone='$phone',Address='$addr' WHERE Donor_ID='$id'";
        $conn->query($sql);
        $msg = "✔ Donor <strong>$id</strong> updated.";
        $msgType = 'success';
    }

    if ($_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['Donor_ID']);
        $conn->query("DELETE FROM Donation WHERE Donor_ID='$id'");
        $conn->query("DELETE FROM Donor WHERE Donor_ID='$id'");
        $msg = "🗑 Donor <strong>$id</strong> deleted.";
        $msgType = 'success';
    }
}

$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "WHERE Donor_ID LIKE '%$search%' OR Name LIKE '%$search%' OR Email LIKE '%$search%' OR Phone LIKE '%$search%'" : '';
$donors = $conn->query("SELECT Donor_ID, Name, Email, Phone, Address FROM Donor $where ORDER BY Donor_ID");

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Donor <span>Management</span></h1>
            <p>View, add, edit and remove donor records</p>
        </div>
        <button class="btn btn-gold" onclick="openModal('addDonorModal')">+ Add Donor</button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?? 'success' ?>"><?= $msg ?></div>
    <?php endif; ?>

    <div class="card mb-0">
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1;flex-wrap:wrap">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Search by ID, name, email…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </div>
                <?php if ($search): ?><a href="donors.php" class="btn btn-outline">✕ Clear</a><?php endif; ?>
            </form>
        </div>

        <div class="table-wrap">
            <table id="donorTable">
                <thead>
                    <tr><th>Donor ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php while ($row = $donors->fetch_assoc()): ?>
                <tr>
                    <td class="monospace text-gold"><?= htmlspecialchars($row['Donor_ID']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['Name']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Phone']) ?></td>
                    <td><?= htmlspecialchars($row['Address']) ?></td>
                    <td class="td-actions">
                        <button class="btn btn-outline btn-sm" onclick="populateEdit('editDonorModal',<?= json_encode($row) ?>)">✏ Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this donor and their donations?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="Donor_ID" value="<?= $row['Donor_ID'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="nav-controls">
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('donorTable');tableNav.goFirst()">⏮ First</button>
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('donorTable');tableNav.goLast()">Last ⏭</button>
            <span><?= $donors->num_rows ?? '' ?> record(s)</span>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addDonorModal">
    <div class="modal">
        <h2>Add New Donor</h2>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Full Name *</label><input type="text" name="Name" required></div>
                <div class="form-group"><label>Email *</label><input type="email" name="Email" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Phone *</label><input type="text" name="Phone" required maxlength="15"></div>
                <div class="form-group"><label>Password *</label><input type="password" name="Password" required></div>
            </div>
            <div class="form-row single">
                <div class="form-group"><label>Address</label><input type="text" name="Address"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addDonorModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Add Donor</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editDonorModal">
    <div class="modal">
        <h2>Edit Donor</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="Donor_ID" id="edit_donor_id">
            <div class="form-row">
                <div class="form-group"><label>Donor ID</label><input type="text" name="Donor_ID" readonly style="opacity:0.5"></div>
                <div class="form-group"><label>Full Name *</label><input type="text" name="Name" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="Email" required></div>
                <div class="form-group"><label>Phone *</label><input type="text" name="Phone" required></div>
            </div>
            <div class="form-row single">
                <div class="form-group"><label>Address</label><input type="text" name="Address"></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editDonorModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
