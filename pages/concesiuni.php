<?php
$page_title = 'Gestionare Concesiuni';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
requireAnyRole('admin', 'paroh', 'cimitir');

// Obține ID-ul concesionarului din URL dacă există
$concesionar_id = $_GET['concesionar'] ?? null;

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id_concesiune = $_POST['id_concesiune'] ?? null;
        $id_cesionar = intval($_POST['id_cesionar']);
        $id_loc = intval($_POST['id_loc']);
        $data_inceput = $_POST['data_inceput'];
        $data_sfarsit = $_POST['data_sfarsit'];
        $suprafata = floatval($_POST['suprafata']);
        $pret_mp = floatval($_POST['pret_mp']);
        $observatii = sanitize($_POST['observatii'] ?? '');

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO concesiuni (id_cesionar, id_loc, data_inceput, data_sfarsit, suprafata, pret_mp, observatii)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_cesionar, $id_loc, $data_inceput, $data_sfarsit, $suprafata, $pret_mp, $observatii]);
                $_SESSION['success_message'] = 'Concesiunea a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE concesiuni SET id_cesionar = ?, id_loc = ?, data_inceput = ?, data_sfarsit = ?,
                                        suprafata = ?, pret_mp = ?, observatii = ?
                    WHERE id_concesiune = ?
                ");
                $stmt->execute([$id_cesionar, $id_loc, $data_inceput, $data_sfarsit, $suprafata, $pret_mp, $observatii, $id_concesiune]);
                $_SESSION['success_message'] = 'Concesiunea a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }

        redirect('concesiuni.php' . ($concesionar_id ? '?concesionar=' . $concesionar_id : ''));
    }
}

// Ștergere concesiune
if (isset($_GET['delete'])) {
    $id_concesiune = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM concesiuni WHERE id_concesiune = ?");
        $stmt->execute([$id_concesiune]);
        $_SESSION['success_message'] = 'Concesiunea a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea concesiunii: ' . $e->getMessage();
    }

    redirect('concesiuni.php' . ($concesionar_id ? '?concesionar=' . $concesionar_id : ''));
}

// Construiește query-ul pentru concesiuni în funcție de concesionar
$where_clause = '';
$params = [];

if ($concesionar_id) {
    $where_clause = 'WHERE c.id_cesionar = ?';
    $params[] = $concesionar_id;
}

$stmt = $pdo->prepare("SELECT c.*, cs.numele, cs.prenumele, l.zona, l.rand, cm.nume_cimitir FROM concesiuni c LEFT JOIN concesionari cs ON c.id_cesionar = cs.id_cesionar LEFT JOIN loc_cimitir l ON c.id_loc = l.id_loc LEFT JOIN cimitir cm ON l.id_cimitir = cm.id_cimitir $where_clause ORDER BY c.data_inceput DESC");
$stmt->execute($params);
$concesiuni = $stmt->fetchAll();

// Obține lista concesionarilor pentru dropdown
$concesionari = $pdo->query("SELECT id_cesionar, CONCAT(numele, ' ', prenumele) as nume_complet FROM concesionari ORDER BY numele, prenumele")->fetchAll();

// Obține lista locurilor disponibile pentru dropdown
$locuri = $pdo->query("SELECT l.id_loc, l.zona, l.rand, c.nume_cimitir FROM loc_cimitir l LEFT JOIN cimitir c ON l.id_cimitir = c.id_cimitir ORDER BY c.nume_cimitir, l.zona, l.rand")->fetchAll();

// Calculează statistici pentru anul curent
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) as numar_concesiuni, SUM(suprafata) as suprafata_totala, SUM(suprafata * pret_mp) as valoare_totala FROM concesiuni WHERE YEAR(data_inceput) <= ? AND (YEAR(data_sfarsit) >= ? OR data_sfarsit IS NULL)");
$stmt->execute([$current_year, $current_year]);
$stats = $stmt->fetch();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-contract me-2"></i>
                Gestionare Concesiuni
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#concesiuneModal">
                <i class="fas fa-plus me-2"></i>Adaugă Concesiune
            </button>
        </div>

        <!-- Statistici -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Concesiuni Active
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $stats['numar_concesiuni'] ?? 0; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Suprafață Totală (m²)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['suprafata_totala'] ?? 0, 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-ruler-combined fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Valoare Totală (RON)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['valoare_totala'] ?? 0, 2); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Concesiunilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Concesionar</th>
                                <th>Cimitir</th>
                                <th>Locație</th>
                                <th>Perioada</th>
                                <th>Suprafață</th>
                                <th>Preț/m²</th>
                                <th>Valoare Totală</th>
                                <th>Observații</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concesiuni as $concesiune): ?>
                            <tr>
                                <td><?php echo $concesiune['id_concesiune']; ?></td>
                                <td><?php echo htmlspecialchars(($concesiune['numele'] ?? '') . ' ' . ($concesiune['prenumele'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($concesiune['nume_cimitir'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars('Zona ' . ($concesiune['zona'] ?? '') . ', Rand ' . ($concesiune['rand'] ?? '')); ?></td>
                                <td><?php echo $concesiune['data_inceput'] ?? ''; ?> - <?php echo $concesiune['data_sfarsit'] ?? 'Nelimitat'; ?></td>
                                <td><?php echo number_format($concesiune['suprafata'] ?? 0, 2); ?> m²</td>
                                <td><?php echo number_format($concesiune['pret_mp'] ?? 0, 2); ?> RON</td>
                                <td><?php echo number_format(($concesiune['suprafata'] ?? 0) * ($concesiune['pret_mp'] ?? 0), 2); ?> RON</td>
                                <td><?php echo htmlspecialchars($concesiune['observatii'] ?? ''); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editConcesiune(<?php echo htmlspecialchars(json_encode($concesiune)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $concesiune['id_concesiune']; ?><?php echo $concesionar_id ? '&concesionar=' . $concesionar_id : ''; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această concesiune?')">
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

<!-- Modal pentru adăugare/editare concesiune -->
<div class="modal fade" id="concesiuneModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="concesiuneModalTitle">
                    <i class="fas fa-file-contract me-2"></i>
                    Adaugă Concesiune
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="concesiuneForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_concesiune" id="concesiuneId">

                    <div class="mb-3">
                        <label for="id_cesionar" class="form-label">Concesionar *</label>
                        <select class="form-select" name="id_cesionar" id="id_cesionar" required>
                            <option value="">Selectați concesionarul</option>
                            <?php foreach ($concesionari as $concesionar): ?>
                                <option value="<?php echo $concesionar['id_cesionar']; ?>"
                                    <?php echo ($concesionar_id == $concesionar['id_cesionar']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($concesionar['nume_complet']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Vă rugăm să selectați concesionarul.</div>
                    </div>

                    <div class="mb-3">
                        <label for="id_loc" class="form-label">Locație *</label>
                        <select class="form-select" name="id_loc" id="id_loc" required>
                            <option value="">Selectați locația</option>
                            <?php foreach ($locuri as $loc): ?>
                                <option value="<?php echo $loc['id_loc']; ?>">
                                    <?php echo htmlspecialchars($loc['nume_cimitir'] . ' - Zona ' . $loc['zona'] . ', Rand ' . $loc['rand']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Vă rugăm să selectați locația.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="data_inceput" class="form-label">Data Început *</label>
                            <input type="date" class="form-control" name="data_inceput" id="data_inceput" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți data de început.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="data_sfarsit" class="form-label">Data Sfârșit</label>
                            <input type="date" class="form-control" name="data_sfarsit" id="data_sfarsit">
                            <div class="small text-muted">Lăsați gol pentru concesiune nelimitată</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="suprafata" class="form-label">Suprafață (m²) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="suprafata" id="suprafata" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți suprafața.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="pret_mp" class="form-label">Preț/m² (RON) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="pret_mp" id="pret_mp" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți prețul pe metru pătrat.</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valoare Totală</label>
                            <div class="form-control" id="valoare_totala">0.00 RON</div>
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
function editConcesiune(concesiune) {
    document.getElementById('concesiuneModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Concesiune';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('concesiuneId').value = concesiune.id_concesiune;
    document.getElementById('id_cesionar').value = concesiune.id_cesionar || '';
    document.getElementById('id_loc').value = concesiune.id_loc || '';
    document.getElementById('data_inceput').value = concesiune.data_inceput || '';
    document.getElementById('data_sfarsit').value = concesiune.data_sfarsit || '';
    document.getElementById('suprafata').value = concesiune.suprafata || '';
    document.getElementById('pret_mp').value = concesiune.pret_mp || '';
    document.getElementById('observatii').value = concesiune.observatii || '';

    calculateTotal();
    new bootstrap.Modal(document.getElementById('concesiuneModal')).show();
}

function calculateTotal() {
    const suprafata = parseFloat(document.getElementById('suprafata').value) || 0;
    const pret_mp = parseFloat(document.getElementById('pret_mp').value) || 0;
    const total = suprafata * pret_mp;
    document.getElementById('valoare_totala').textContent = total.toFixed(2) + ' RON';
}

// Calculează totalul când se schimbă valorile
document.getElementById('suprafata').addEventListener('input', calculateTotal);
document.getElementById('pret_mp').addEventListener('input', calculateTotal);

// Reset form când se închide modalul
document.getElementById('concesiuneModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('concesiuneForm').reset();
    document.getElementById('concesiuneForm').classList.remove('was-validated');
    document.getElementById('concesiuneModalTitle').innerHTML = '<i class="fas fa-file-contract me-2"></i>Adaugă Concesiune';
    document.getElementById('formAction').value = 'add';
    document.getElementById('concesiuneId').value = '';
    document.getElementById('valoare_totala').textContent = '0.00 RON';
});
</script>

<?php require_once '../includes/footer.php'; ?>
