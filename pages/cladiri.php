<?php
$page_title = 'Gestionare Clădiri';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
if (!hasRole('admin') && !hasRole('protopop') && !hasRole('paroh')) {
    die("Acces interzis! Nu aveți permisiunile necesare pentru a accesa această pagină.");
}

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_cladire = $_POST['id_cladire'] ?? null;
        $nume_cladire = sanitize($_POST['nume_cladire']);
        $anul_constructie = !empty($_POST['anul_constructie']) ? intval($_POST['anul_constructie']) : null;
        $stare = sanitize($_POST['stare']);
        $obs_cladire = sanitize($_POST['obs_cladire']);
        $id_parohie = intval($_POST['id_parohie']);
        
        // Verifică permisiunile pentru parohie
        if (!hasAccessToParohie($id_parohie)) {
            $_SESSION['error_message'] = 'Nu aveți permisiunea să adăugați clădiri în această parohie!';
            redirect('cladiri.php');
        }
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO cladiri (nume_cladire, anul_constructie, stare, obs_cladire, id_parohie)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nume_cladire, $anul_constructie, $stare, $obs_cladire, $id_parohie]);
                $_SESSION['success_message'] = 'Clădirea a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE cladiri SET nume_cladire = ?, anul_constructie = ?, stare = ?, 
                                     obs_cladire = ?, id_parohie = ?
                    WHERE id_cladire = ?
                ");
                $stmt->execute([$nume_cladire, $anul_constructie, $stare, $obs_cladire, $id_parohie, $id_cladire]);
                $_SESSION['success_message'] = 'Clădirea a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('cladiri.php');
    }
}

// Ștergere clădire
if (isset($_GET['delete'])) {
    $id_cladire = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cladiri WHERE id_cladire = ?");
        $stmt->execute([$id_cladire]);
        $_SESSION['success_message'] = 'Clădirea a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea clădirii: ' . $e->getMessage();
    }
    
    redirect('cladiri.php');
}

// Construiește query-ul pentru clădiri în funcție de rol
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
    SELECT c.*, p.nume_parohie
    FROM cladiri c
    LEFT JOIN parohii p ON c.id_parohie = p.id_parohie
    $where_clause
    ORDER BY p.nume_parohie, c.nume_cladire
");
$stmt->execute($params);
$cladiri = $stmt->fetchAll();

// Obține parohiile accesibile
$parohii_accesibile = getAccessibleParohii();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-building me-2"></i>
                Gestionare Clădiri
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cladireModal">
                <i class="fas fa-plus me-2"></i>Adaugă Clădire
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Clădirilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume Clădire</th>
                                <th>Parohia</th>
                                <th>Anul Construcției</th>
                                <th>Starea</th>
                                <th>Observații</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cladiri as $cladire): ?>
                            <tr>
                                <td><?php echo $cladire['id_cladire']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cladire['nume_cladire']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($cladire['nume_parohie']); ?></td>
                                <td>
                                    <?php if (!empty($cladire['anul_constructie'])): ?>
                                        <?php echo $cladire['anul_constructie']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cladire['stare'])): ?>
                                        <?php 
                                        $stare_class = '';
                                        switch(strtolower($cladire['stare'])) {
                                            case 'bună': case 'buna': case 'excelentă': case 'excelenta': 
                                                $stare_class = 'bg-success'; break;
                                            case 'satisfăcătoare': case 'satisfacatoare': case 'bună parțial': 
                                                $stare_class = 'bg-warning'; break;
                                            case 'proastă': case 'proasta': case 'deteriorată': case 'deteriorata': 
                                                $stare_class = 'bg-danger'; break;
                                            default: $stare_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $stare_class; ?>"><?php echo htmlspecialchars($cladire['stare']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Nespecificat</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cladire['obs_cladire'])): ?>
                                        <?php echo htmlspecialchars(substr($cladire['obs_cladire'], 0, 50)); ?>
                                        <?php if (strlen($cladire['obs_cladire']) > 50): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editCladire(<?php echo htmlspecialchars(json_encode($cladire)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $cladire['id_cladire']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această clădire?')">
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

<!-- Modal pentru adăugare/editare clădire -->
<div class="modal fade" id="cladireModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cladireModalTitle">
                    <i class="fas fa-building me-2"></i>
                    Adaugă Clădire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cladireForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_cladire" id="cladireId">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nume_cladire" class="form-label">Nume Clădire *</label>
                            <input type="text" class="form-control" name="nume_cladire" id="nume_cladire" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele clădirii.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="anul_constructie" class="form-label">Anul Construcției</label>
                            <input type="number" class="form-control" name="anul_constructie" id="anul_constructie" 
                                   min="1000" max="<?php echo date('Y'); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
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
                        
                        <div class="col-md-6 mb-3">
                            <label for="stare" class="form-label">Starea Clădirii</label>
                            <select class="form-select" name="stare" id="stare">
                                <option value="">Selectați starea</option>
                                <option value="Excelentă">Excelentă</option>
                                <option value="Bună">Bună</option>
                                <option value="Satisfăcătoare">Satisfăcătoare</option>
                                <option value="Necesită reparații minore">Necesită reparații minore</option>
                                <option value="Necesită reparații majore">Necesită reparații majore</option>
                                <option value="Deteriorată">Deteriorată</option>
                                <option value="Ruină">Ruină</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="obs_cladire" class="form-label">Observații</label>
                        <textarea class="form-control" name="obs_cladire" id="obs_cladire" rows="4" 
                                  placeholder="Descrieți starea clădirii, reparațiile necesare, istoricul, etc."></textarea>
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
function editCladire(cladire) {
    document.getElementById('cladireModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Clădire';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('cladireId').value = cladire.id_cladire;
    document.getElementById('nume_cladire').value = cladire.nume_cladire;
    document.getElementById('anul_constructie').value = cladire.anul_constructie || '';
    document.getElementById('id_parohie').value = cladire.id_parohie || '';
    document.getElementById('stare').value = cladire.stare || '';
    document.getElementById('obs_cladire').value = cladire.obs_cladire || '';
    
    new bootstrap.Modal(document.getElementById('cladireModal')).show();
}

// Reset form când se închide modalul
document.getElementById('cladireModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('cladireForm').reset();
    document.getElementById('cladireForm').classList.remove('was-validated');
    document.getElementById('cladireModalTitle').innerHTML = '<i class="fas fa-building me-2"></i>Adaugă Clădire';
    document.getElementById('formAction').value = 'add';
    document.getElementById('cladireId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>