<?php
$page_title = 'Gestionare Locuri Cimitir';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
requireAnyRole('admin', 'paroh', 'cimitir');

// Obține ID-ul cimitirului din URL dacă există
$cimitir_id = $_GET['cimitir'] ?? null;

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id_loc = $_POST['id_loc'] ?? null;
        $id_cimitir = intval($_POST['id_cimitir']);
        $zona = sanitize($_POST['zona']);
        $rand = sanitize($_POST['rand']);
        $observatii = sanitize($_POST['observatii'] ?? '');

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO loc_cimitir (id_cimitir, zona, rand, observatii)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id_cimitir, $zona, $rand, $observatii]);
                $_SESSION['success_message'] = 'Locul din cimitir a fost adăugat cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE loc_cimitir SET id_cimitir = ?, zona = ?, rand = ?, observatii = ?
                    WHERE id_loc = ?
                ");
                $stmt->execute([$id_cimitir, $zona, $rand, $observatii, $id_loc]);
                $_SESSION['success_message'] = 'Locul din cimitir a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }

        redirect('locuri_cimitir.php' . ($cimitir_id ? '?cimitir=' . $cimitir_id : ''));
    }
}

// Ștergere loc
if (isset($_GET['delete'])) {
    $id_loc = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM loc_cimitir WHERE id_loc = ?");
        $stmt->execute([$id_loc]);
        $_SESSION['success_message'] = 'Locul din cimitir a fost șters cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea locului: ' . $e->getMessage();
    }

    redirect('locuri_cimitir.php' . ($cimitir_id ? '?cimitir=' . $cimitir_id : ''));
}

// Construiește query-ul pentru locuri în funcție de cimitir
$where_clause = '';
$params = [];

if ($cimitir_id) {
    $where_clause = 'WHERE lc.id_cimitir = ?';
    $params[] = $cimitir_id;
}

$stmt = $pdo->prepare("SELECT lc.*, c.nume_cimitir FROM loc_cimitir lc LEFT JOIN cimitir c ON lc.id_cimitir = c.id_cimitir $where_clause ORDER BY lc.id_cimitir, lc.zona, lc.rand");
$stmt->execute($params);
$locuri = $stmt->fetchAll();

// Obține lista cimitirelor pentru dropdown
$cimitire = $pdo->query("SELECT id_cimitir, nume_cimitir FROM cimitir ORDER BY nume_cimitir")->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-map-marker-alt me-2"></i>
                Gestionare Locuri Cimitir
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#locModal">
                <i class="fas fa-plus me-2"></i>Adaugă Loc
            </button>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Locurilor din Cimitir
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cimitir</th>
                                <th>Zona</th>
                                <th>Rand</th>
                                <th>Observații</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($locuri as $loc): ?>
                            <tr>
                                <td><?php echo $loc['id_loc']; ?></td>
                                <td><?php echo htmlspecialchars($loc['nume_cimitir'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($loc['zona'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($loc['rand'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($loc['observatii'] ?? ''); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editLoc(<?php echo htmlspecialchars(json_encode($loc)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $loc['id_loc']; ?><?php echo $cimitir_id ? '&cimitir=' . $cimitir_id : ''; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest loc?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru adăugare/editare loc -->
<div class="modal fade" id="locModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locModalTitle">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Adaugă Loc
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="locForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_loc" id="locId">

                    <div class="mb-3">
                        <label for="id_cimitir" class="form-label">Cimitir *</label>
                        <select class="form-select" name="id_cimitir" id="id_cimitir" required>
                            <option value="">Selectați cimitirul</option>
                            <?php foreach ($cimitire as $cimitir): ?>
                                <option value="<?php echo $cimitir['id_cimitir']; ?>"
                                    <?php echo ($cimitir_id == $cimitir['id_cimitir']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cimitir['nume_cimitir']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Vă rugăm să selectați cimitirul.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="zona" class="form-label">Zona *</label>
                            <input type="text" class="form-control" name="zona" id="zona" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți zona.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="rand" class="form-label">Rand *</label>
                            <input type="text" class="form-control" name="rand" id="rand" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți rândul.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="observatii" class="form-label">Observații</label>
                        <textarea class="form-control" name="observatii" id="observatii" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvează
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLoc(loc) {
    document.getElementById('locModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Loc';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('locId').value = loc.id_loc;
    document.getElementById('id_cimitir').value = loc.id_cimitir || '';
    document.getElementById('zona').value = loc.zona || '';
    document.getElementById('rand').value = loc.rand || '';
    document.getElementById('observatii').value = loc.observatii || '';

    new bootstrap.Modal(document.getElementById('locModal')).show();
}

// Reset form când se închide modalul
document.getElementById('locModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('locForm').reset();
    document.getElementById('locForm').classList.remove('was-validated');
    document.getElementById('locModalTitle').innerHTML = '<i class="fas fa-map-marker-alt me-2"></i>Adaugă Loc';
    document.getElementById('formAction').value = 'add';
    document.getElementById('locId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
