<?php
require_once '../includes/db.php';
$pageTitle = 'Campaigns';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'insert') {
        $id    = nextID($conn,'Campaign','Campaign_ID','CP',2);
        $title = $conn->real_escape_string(trim($_POST['Title']));
        $desc  = $conn->real_escape_string(trim($_POST['Description']));
        $target= (float)$_POST['Target_Amount'];
        $start = $conn->real_escape_string($_POST['Start_Date']);
        $end   = $conn->real_escape_string($_POST['End_Date']);
        $stat  = $conn->real_escape_string($_POST['Status']);
        $cid   = $conn->real_escape_string($_POST['Charity_ID']);
        $conn->query("INSERT INTO Campaign VALUES ('$id','$title','$desc',$target,'$start','$end','$stat','$cid')");
        $msg = "✔ Campaign <strong>$title</strong> added (ID: $id)."; $msgType='success';
    }

    if ($_POST['action'] === 'update') {
        $id    = $conn->real_escape_string($_POST['Campaign_ID']);
        $title = $conn->real_escape_string(trim($_POST['Title']));
        $desc  = $conn->real_escape_string(trim($_POST['Description']));
        $target= (float)$_POST['Target_Amount'];
        $start = $conn->real_escape_string($_POST['Start_Date']);
        $end   = $conn->real_escape_string($_POST['End_Date']);
        $stat  = $conn->real_escape_string($_POST['Status']);
        $cid   = $conn->real_escape_string($_POST['Charity_ID']);
        $conn->query("UPDATE Campaign SET Title='$title',Description='$desc',Target_Amount=$target,Start_Date='$start',End_Date='$end',Status='$stat',Charity_ID='$cid' WHERE Campaign_ID='$id'");
        $msg = "✔ Campaign updated."; $msgType='success';
    }

    if ($_POST['action'] === 'delete') {
        $id = $conn->real_escape_string($_POST['Campaign_ID']);
        $conn->query("UPDATE Donation SET Campaign_ID=NULL WHERE Campaign_ID='$id'");
        $conn->query("DELETE FROM Campaign WHERE Campaign_ID='$id'");
        $msg = "🗑 Campaign deleted."; $msgType='success';
    }
}

$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = $search ? "WHERE cp.Campaign_ID LIKE '%$search%' OR cp.Title LIKE '%$search%' OR cp.Status LIKE '%$search%'" : '';
$camps = $conn->query("
    SELECT cp.*, ch.Charity_Name,
           COALESCE((SELECT SUM(Amount) FROM Donation WHERE Campaign_ID=cp.Campaign_ID),0) AS Raised
    FROM Campaign cp LEFT JOIN Charity ch ON cp.Charity_ID=ch.Charity_ID $where ORDER BY cp.Campaign_ID
");
$charities = $conn->query("SELECT Charity_ID, Charity_Name FROM Charity ORDER BY Charity_Name");

$statusBadge = ['Active'=>'badge-green','Completed'=>'badge-gold','Paused'=>'badge-blue','Cancelled'=>'badge-red'];

include '../includes/header.php';
?>

<div class="page-wrap">
    <div class="page-header">
        <div>
            <h1>Campaign <span>Management</span></h1>
            <p>Track fundraising campaigns and their progress</p>
        </div>
        <button class="btn btn-gold" onclick="openModal('addCampModal')">+ Add Campaign</button>
    </div>

    <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="card mb-0">
        <div class="toolbar">
            <form method="GET" style="display:flex;gap:0.75rem;flex:1;flex-wrap:wrap">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Search campaigns…" value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">🔍</button>
                </div>
                <?php if ($search): ?><a href="campaigns.php" class="btn btn-outline">✕ Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-wrap">
            <table id="campTable">
                <thead><tr><th>ID</th><th>Title</th><th>Charity</th><th>Target</th><th>Raised</th><th>Progress</th><th>Status</th><th>Dates</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $camps->fetch_assoc()):
                    $pct = $row['Target_Amount'] > 0 ? min(100, round($row['Raised']/$row['Target_Amount']*100)) : 0;
                    $bc  = $statusBadge[$row['Status']] ?? 'badge-muted';
                ?>
                <tr>
                    <td class="monospace text-gold"><?= htmlspecialchars($row['Campaign_ID']) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['Title']) ?></td>
                    <td><?= htmlspecialchars($row['Charity_Name'] ?? '—') ?></td>
                    <td>₹<?= number_format($row['Target_Amount']) ?></td>
                    <td class="text-green">₹<?= number_format($row['Raised']) ?></td>
                    <td style="min-width:90px">
                        <div class="progress-track" style="height:6px">
                            <div class="progress-fill" data-width="<?= $pct ?>" style="width:0"></div>
                        </div>
                        <span style="font-size:0.75rem;color:var(--text-muted)"><?= $pct ?>%</span>
                    </td>
                    <td><span class="badge <?= $bc ?>"><?= $row['Status'] ?></span></td>
                    <td style="font-size:0.8rem;color:var(--text-muted)"><?= $row['Start_Date'] ?> – <?= $row['End_Date'] ?></td>
                    <td class="td-actions">
                        <button class="btn btn-outline btn-sm" onclick="populateEdit('editCampModal',<?= json_encode($row) ?>)">✏</button>
                        <form method="POST" onsubmit="return confirm('Delete this campaign?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="Campaign_ID" value="<?= $row['Campaign_ID'] ?>">
                            <button class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="nav-controls">
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('campTable');tableNav.goFirst()">⏮ First</button>
            <button class="btn btn-nav btn-sm" onclick="tableNav.init('campTable');tableNav.goLast()">Last ⏭</button>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addCampModal">
    <div class="modal">
        <h2>Add New Campaign</h2>
        <form method="POST">
            <input type="hidden" name="action" value="insert">
            <div class="form-row single"><div class="form-group"><label>Title *</label><input type="text" name="Title" required></div></div>
            <div class="form-row">
                <div class="form-group"><label>Charity *</label>
                    <select name="Charity_ID" required>
                        <option value="">-- Select Charity --</option>
                        <?php $charities->data_seek(0); while($c=$charities->fetch_assoc()): ?>
                        <option value="<?= $c['Charity_ID'] ?>"><?= htmlspecialchars($c['Charity_Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Target Amount (₹) *</label><input type="number" name="Target_Amount" min="1" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Start Date *</label><input type="date" name="Start_Date" required></div>
                <div class="form-group"><label>End Date *</label><input type="date" name="End_Date" required></div>
            </div>
            <div class="form-row single"><div class="form-group"><label>Status</label>
                <select name="Status">
                    <option value="Active">Active</option><option value="Completed">Completed</option>
                    <option value="Paused">Paused</option><option value="Cancelled">Cancelled</option>
                </select>
            </div></div>
            <div class="form-row single"><div class="form-group"><label>Description</label><textarea name="Description"></textarea></div></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCampModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Add Campaign</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editCampModal">
    <div class="modal">
        <h2>Edit Campaign</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-row">
                <div class="form-group"><label>Campaign ID</label><input type="text" name="Campaign_ID" readonly style="opacity:0.5"></div>
                <div class="form-group"><label>Title *</label><input type="text" name="Title" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Charity</label>
                    <select name="Charity_ID">
                        <option value="">-- Select --</option>
                        <?php $charities->data_seek(0); while($c=$charities->fetch_assoc()): ?>
                        <option value="<?= $c['Charity_ID'] ?>"><?= htmlspecialchars($c['Charity_Name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Target Amount (₹)</label><input type="number" name="Target_Amount" min="1"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="Start_Date"></div>
                <div class="form-group"><label>End Date</label><input type="date" name="End_Date"></div>
            </div>
            <div class="form-row single"><div class="form-group"><label>Status</label>
                <select name="Status">
                    <option value="Active">Active</option><option value="Completed">Completed</option>
                    <option value="Paused">Paused</option><option value="Cancelled">Cancelled</option>
                </select>
            </div></div>
            <div class="form-row single"><div class="form-group"><label>Description</label><textarea name="Description"></textarea></div></div>
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCampModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
