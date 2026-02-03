<?php
$page_title = 'Gestionare Cimitire';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
if (!hasRole('admin') && !hasRole('protopop') && !hasRole('paroh') && !hasRole('cimitir')) {
    die("Acces interzis! Nu aveți permisiunile necesare pentru a accesa această pagină.");
}

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_cimitir = $_POST['id_cimitir'] ?? null;
        $nume_cimitir = sanitize($_POST['nume_cimitir']);
        $adresa = sanitize($_POST['adresa']);
        $localitate = sanitize($_POST['localitate']);
        $judetul = sanitize($_POST['judetul']);
        $obs_cimitir = sanitize($_POST['obs_cimitir']);
        $id_parohie = intval($_POST['id_parohie']);
        
        // Verifică permisiunile pentru parohie
        if (!hasAccessToParohie($id_parohie) && !hasRole('cimitir')) {
            $_SESSION['error_message'] = 'Nu aveți permisiunea să adăugați cimitire în această parohie!';
            redirect('cimitire.php');
        }
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO cimitir (nume_cimitir, adresa, localitate, judetul, obs_cimitir, id_parohie)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nume_cimitir, $adresa, $localitate, $judetul, $obs_cimitir, $id_parohie]);
                $_SESSION['success_message'] = 'Cimitirul a fost adăugat cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE cimitir SET nume_cimitir = ?, adresa = ?, localitate = ?, 
                                     judetul = ?, obs_cimitir = ?, id_parohie = ?
                    WHERE id_cimitir = ?
                ");
                $stmt->execute([$nume_cimitir, $adresa, $localitate, $judetul, $obs_cimitir, $id_parohie, $id_cimitir]);
                $_SESSION['success_message'] = 'Cimitirul a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('cimitire.php');
    }
}

// Ștergere cimitir
if (isset($_GET['delete'])) {
    $id_cimitir = intval($_GET['delete']);
    
    try {
        // Verifică dacă cimitirul are locuri asociate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loc_cimitir WHERE id_cimitir = ?");
        $stmt->execute([$id_cimitir]);
        $count_locuri = $stmt->fetchColumn();
        
        if ($count_locuri > 0) {
            $_SESSION['error_message'] = 'Nu puteți șterge cimitirul deoarece are locuri asociate!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM cimitir WHERE id_cimitir = ?");
            $stmt->execute([$id_cimitir]);
            $_SESSION['success_message'] = 'Cimitirul a fost șters cu succes!';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea cimitirului: ' . $e->getMessage();
    }
    
    redirect('cimitire.php');
}

// Construiește query-ul pentru cimitire în funcție de rol
$where_clause = '';
$params = [];

if (hasRole('paroh')) {
    $where_clause = 'WHERE c.id_parohie = ?';
    $params[] = $_SESSION['user_parohie'];
} elseif (hasRole('protopop')) {
    $where_clause = 'WHERE p.id_proterie = ?';
    $params[] = $_SESSION['user_proterie'];
}

$stmt = $pdo->prepare("
    SELECT c.*, p.nume_parohie, COUNT(lc.id_loc) as nr_locuri
    FROM cimitir c
    LEFT JOIN parohii p ON c.id_parohie = p.id_parohie
    LEFT JOIN loc_cimitir lc ON c.id_cimitir = lc.id_cimitir
    $where_clause
    GROUP BY c.id_cimitir
    ORDER BY p.nume_parohie, c.nume_cimitir
");
$stmt->execute($params);
$cimitire = $stmt->fetchAll();

// Obține parohiile accesibile
$parohii_accesibile = getAccessibleParohii();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-cross me-2"></i>
                Gestionare Cimitire
            </h1>
            <div>
                <a href="locuri_cimitir.php" class="btn btn-info me-2">
                    <i class="fas fa-map-marker-alt me-2"></i>Locuri Cimitir
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cimitirModal">
                    <i class="fas fa-plus me-2"></i>Adaugă Cimitir
                </button>
            </div>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Cimitirelor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume Cimitir</th>
                                <th>Parohia</th>
                                <th>Adresa</th>
                                <th>Localitate</th>
                                <th>Județ</th>
                                <th>Locuri</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cimitire as $cimitir): ?>
                            <tr>
                                <td><?php echo $cimitir['id_cimitir']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cimitir['nume_cimitir']); ?></strong>
                                    <?php if (!empty($cimitir['obs_cimitir'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($cimitir['obs_cimitir']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($cimitir['nume_parohie']); ?></td>
                                <td><?php echo htmlspecialchars($cimitir['adresa']); ?></td>
                                <td><?php echo htmlspecialchars($cimitir['localitate']); ?></td>
                                <td><?php echo htmlspecialchars($cimitir['judetul']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $cimitir['nr_locuri']; ?> locuri
                                    </span>
                                </td>
                                <td class="table-actions">
                                    <a href="locuri_cimitir.php?cimitir=<?php echo $cimitir['id_cimitir']; ?>" 
                                       class="btn btn-sm btn-success" title="Vezi locurile">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editCimitir(<?php echo htmlspecialchars(json_encode($cimitir)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $cimitir['id_cimitir']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest cimitir?')">
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

<!-- Modal pentru adăugare/editare cimitir -->
<div class="modal fade" id="cimitirModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cimitirModalTitle">
                    <i class="fas fa-cross me-2"></i>
                    Adaugă Cimitir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cimitirForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_cimitir" id="cimitirId">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nume_cimitir" class="form-label">Nume Cimitir *</label>
                            <input type="text" class="form-control" name="nume_cimitir" id="nume_cimitir" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele cimitirului.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="id_parohie" class="form-label">Parohia *</label>
                            <select class="form-select" name="id_parohie" id="id_parohie" required>
                                <option value="">Selectați parohia</option>
                                <?php foreach ($parohii_accesibile as $parohie): ?>
                                    <option value="<?php echo $parohie['id_parohie']; ?>">
                                        <?php echo htmlspecialchars($parohie['nume_parohie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vă rugăm să selectați parohia.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresa" class="form-label">Adresa</label>
                        <input type="text" class="form-control" name="adresa" id="adresa">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="localitate" class="form-label">Localitate</label>
                            <input type="text" class="form-control" name="localitate" id="localitate">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="judetul" class="form-label">Județ</label>
                            <input type="text" class="form-control" name="judetul" id="judetul">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="obs_cimitir" class="form-label">Observații</label>
                        <textarea class="form-control" name="obs_cimitir" id="obs_cimitir" rows="3"></textarea>
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
function editCimitir(cimitir) {
    document.getElementById('cimitirModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Cimitir';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('cimitirId').value = cimitir.id_cimitir;
    document.getElementById('nume_cimitir').value = cimitir.nume_cimitir;
    document.getElementById('id_parohie').value = cimitir.id_parohie || '';
    document.getElementById('adresa').value = cimitir.adresa || '';
    document.getElementById('localitate').value = cimitir.localitate || '';
    document.getElementById('judetul').value = cimitir.judetul || '';
    document.getElementById('obs_cimitir').value = cimitir.obs_cimitir || '';
    
    new bootstrap.Modal(document.getElementById('cimitirModal')).show();
}

// Reset form când se închide modalul
document.getElementById('cimitirModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('cimitirForm').reset();
    document.getElementById('cimitirForm').classList.remove('was-validated');
    document.getElementById('cimitirModalTitle').innerHTML = '<i class="fas fa-cross me-2"></i>Adaugă Cimitir';
    document.getElementById('formAction').value = 'add';
    document.getElementById('cimitirId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>