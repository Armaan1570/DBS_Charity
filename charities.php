<?php
require_once '../includes/db.php';
$pageTitle = 'Charities';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'insert') {
        $id   = nextID($conn,'Charity','Charity_ID','CH',2);
        $name = $conn->real_escape_string(trim($_POST['Charity_Name']));
        $reg  = $conn->real_escape_string(trim($_POST['Registration_No']));
        $email= $conn->real_escape_string(trim($_POST['Email']));
        $phone= $conn->real_escape_string(trim($_POST['Phone']));
        $addr = $conn->real_escape_string(trim($_POST['Address']));
        $desc = $conn->real_escape_string(trim($_POST['Description']));
        $cat  = $conn->real_escape_string($_POST['Category_ID']);
        $conn->query("INSERT INTO Charity VALUES ('$id','$name','$reg','$email','$phone','$addr','$desc','$cat')");
        $msg = "✔ Charity <strong>$name</strong> added (ID: $id)."; $msgType='success';
    }

    if ($_POST['action'] === 'update') {
        $id   = $conn->real_escape_string($_POST['Charity_ID']);
        $name = $conn->real_escape_string(trim($_POST['Charity_Name']));
        $email= $conn->real_escape_string(trim($_POST['Email']));
        $phone= $conn->real_escape_string(trim($_POST['Phone']));
        $addr = $conn->real_escape_string(trim($_POST['Address']));
        $desc = $conn->real_escape_string(trim($_POST['Description']));
        $cat  = $conn->real_escape_string($_POST['Category_ID']);
        $conn->query("UPDATE Charity SET Charity_Name='$name',Email='$email',Phone='$phone',Address='$addr',Description='$desc',Category_ID='$cat' WHERE Charity_ID='$id'");
        $msg = "✔ Charity updated."; $msgType='success';
    }

    if ($_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['Charity_ID']);
        $conn->query("UPDATE Campaign SET Charity_ID=NULL WHERE Charity_ID='$id'");
        $conn->query("DELETE FROM Charity WHERE Charity_ID='$id'");
        $msg = "🗑 Charity deleted."; $msgType='success';
    }
}

$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "WHERE ch.Charity_ID LIKE '%$search%' OR ch.Charity_Name LIKE '%$search%' OR ch.Email LIKE '%$search%'" : '';
$charities = $conn->query("SELECT ch.*, cat.Category_Name FROM Charity ch LEFT JOIN Category cat ON ch.Category_ID=cat.Category_ID $where ORDER BY ch.Charity_ID");
$categories = $conn->query("SELECT * FROM Category ORDER BY Category_Name");

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Charity <span>Management</span></h1>
            <p>Manage registered charities and their categories</p>
        </div>
        <button class="btn btn-gold" onclick="openModal('addCharityModal')">+ Add Charity</button>
    </div>

    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="card mb-0">
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1;flex-wrap:wrap">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Search charities…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </div>
                <?php if ($search): ?><a href="charities.php" class="btn btn-outline">✕ Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <table id="charTable">
                <thead><tr><th>ID</th><th>Name</th><th>Reg No</th><th>Email</th><th>Phone</th><th>Category</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $charities->fetch_assoc()): ?>
                <tr>
                    <td class="monospace text-gold"><?= htmlspecialchars($row['Charity_ID']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['Charity_Name']) ?></td>
                    <td class="monospace"><?= htmlspecialchars($row['Registration_No']) ?></td>
                    <td><?= htmlspecialchars($row['Email']) ?></td>
                    <td><?= htmlspecialchars($row['Phone']) ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($row['Category_Name'] ?? '—') ?></span></td>
                    <td class="td-actions">
                        <button class="btn btn-outline btn-sm" onclick="populateEdit('editCharityModal',<?= json_encode($row) ?>)">✏ Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this charity?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="Charity_ID" value="<?= $row['Charity_ID'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="nav-controls">
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('charTable');tableNav.goFirst()">⏮ First</button>
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('charTable');tableNav.goLast()">Last ⏭</button>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addCharityModal">
    <div class="modal">
        <h2>Add New Charity</h2>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row">
                <div class="form-group"><label>Charity Name *</label><input type="text" name="Charity_Name" required></div>
                <div class="form-group"><label>Registration No *</label><input type="text" name="Registration_No" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="Email" required></div>
                <div class="form-group"><label>Phone *</label><input type="text" name="Phone" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Address</label><input type="text" name="Address"></div>
                <div class="form-group"><label>Category</label>
                    <select name="Category_ID">
                        <option value="">-- Select --</option>
                        <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
                        <option value="<?= $c['Category_ID'] ?>"><?= htmlspecialchars($c['Category_Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row single">
                <div class="form-group"><label>Description</label><textarea name="Description"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCharityModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Add Charity</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editCharityModal">
    <div class="modal">
        <h2>Edit Charity</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-row">
                <div class="form-group"><label>Charity ID</label><input type="text" name="Charity_ID" readonly style="opacity:0.5"></div>
                <div class="form-group"><label>Charity Name *</label><input type="text" name="Charity_Name" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="Email" required></div>
                <div class="form-group"><label>Phone *</label><input type="text" name="Phone" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Address</label><input type="text" name="Address"></div>
                <div class="form-group"><label>Category</label>
                    <select name="Category_ID">
                        <option value="">-- Select --</option>
                        <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
                        <option value="<?= $c['Category_ID'] ?>"><?= htmlspecialchars($c['Category_Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row single">
                <div class="form-group"><label>Description</label><textarea name="Description"></textarea></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCharityModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
