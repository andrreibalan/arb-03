<?php
$page_title = 'Gestionare Protopopiate';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile - doar admin poate gestiona protopopiatele
requireRole('admin');

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_proterie = $_POST['id_proterie'] ?? null;
        $denumire = sanitize($_POST['denumire']);
        $cui = sanitize($_POST['cui']);
        $adresa = sanitize($_POST['adresa']);
        $localitate = sanitize($_POST['localitate']);
        $judet = sanitize($_POST['judet']);
        $anul_infiintarii = !empty($_POST['anul_infiintarii']) ? intval($_POST['anul_infiintarii']) : null;
        $cod_caen = sanitize($_POST['cod_caen']);
        $descr_caen = sanitize($_POST['descr_caen']);
        $observatii = sanitize($_POST['observatii']);
        $status = sanitize($_POST['status']);
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO protopopiate (denumire, cui, adresa, localitate, judet, 
                                            anul_infiintarii, cod_caen, descr_caen, observatii, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$denumire, $cui, $adresa, $localitate, $judet, 
                              $anul_infiintarii, $cod_caen, $descr_caen, $observatii, $status]);
                $_SESSION['success_message'] = 'Protopopiatul a fost adăugat cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE protopopiate SET denumire = ?, cui = ?, adresa = ?, localitate = ?, 
                                          judet = ?, anul_infiintarii = ?, cod_caen = ?, 
                                          descr_caen = ?, observatii = ?, status = ?
                    WHERE id_proterie = ?
                ");
                $stmt->execute([$denumire, $cui, $adresa, $localitate, $judet, 
                              $anul_infiintarii, $cod_caen, $descr_caen, $observatii, $status, $id_proterie]);
                $_SESSION['success_message'] = 'Protopopiatul a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('protopopiate.php');
    }
}

// Ștergere protopopiat
if (isset($_GET['delete'])) {
    $id_proterie = intval($_GET['delete']);
    
    try {
        // Verifică dacă protopopiatul are parohii asociate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM parohii WHERE id_proterie = ?");
        $stmt->execute([$id_proterie]);
        $count_parohii = $stmt->fetchColumn();
        
        if ($count_parohii > 0) {
            $_SESSION['error_message'] = 'Nu puteți șterge protopopiatul deoarece are parohii asociate!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM protopopiate WHERE id_proterie = ?");
            $stmt->execute([$id_proterie]);
            $_SESSION['success_message'] = 'Protopopiatul a fost șters cu succes!';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea protopopiatului: ' . $e->getMessage();
    }
    
    redirect('protopopiate.php');
}

// Obține lista protopopiatelor cu numărul de parohii
$stmt = $pdo->query("
    SELECT p.*, COUNT(pa.id_parohie) as nr_parohii
    FROM protopopiate p
    LEFT JOIN parohii pa ON p.id_proterie = pa.id_proterie
    GROUP BY p.id_proterie
    ORDER BY p.denumire
");
$protopopiate = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-crown me-2"></i>
                Gestionare Protopopiate
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#protopopiatModal">
                <i class="fas fa-plus me-2"></i>Adaugă Protopopiat
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Protopopiatelor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                               <!-- <th>ID</th> -->
                                <th>Denumire</th>
                                <th>CUI</th>
                                <th>Localitate</th>
                                <th>Județ</th>
                                <th>Anul Înființării</th>
                                <th>Parohii</th>
                                <th>Status</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($protopopiate as $protopopiat): ?>
                            <tr>
                                <!-- <td><?php echo $protopopiat['id_proterie']; ?></td> -->
                                <td>
                                    <strong><?php echo htmlspecialchars($protopopiat['denumire']); ?></strong>
                                    <?php if (!empty($protopopiat['cod_caen'])): ?>
                                        <br><small class="text-muted">CAEN: <?php echo htmlspecialchars($protopopiat['cod_caen']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($protopopiat['cui']); ?></td>
                                <td><?php echo htmlspecialchars($protopopiat['localitate']); ?></td>
                                <td><?php echo htmlspecialchars($protopopiat['judet']); ?></td>
                                <td>
                                    <?php if (!empty($protopopiat['anul_infiintarii'])): ?>
                                        <?php echo $protopopiat['anul_infiintarii']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $protopopiat['nr_parohii']; ?> parohii
                                    </span>
                                </td>
                                <td>
                                    <?php if ($protopopiat['status'] === 'activ'): ?>
                                        <span class="badge bg-success">Activ</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Dezactivat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editProtopopiat(<?php echo htmlspecialchars(json_encode($protopopiat)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $protopopiat['id_proterie']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest protopopiat?')">
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

<!-- Modal pentru adăugare/editare protopopiat -->
<div class="modal fade" id="protopopiatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="protopopiatModalTitle">
                    <i class="fas fa-crown me-2"></i>
                    Adaugă Protopopiat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="protopopiatForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_proterie" id="protopopiatId">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="denumire" class="form-label">Denumire *</label>
                            <input type="text" class="form-control" name="denumire" id="denumire" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți denumirea protopopiatului.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="cui" class="form-label">CUI *</label>
                            <input type="text" class="form-control" name="cui" id="cui" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți CUI-ul.</div>
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
                            <label for="judet" class="form-label">Județ</label>
                            <input type="text" class="form-control" name="judet" id="judet">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="anul_infiintarii" class="form-label">Anul Înființării</label>
                            <input type="number" class="form-control" name="anul_infiintarii" id="anul_infiintarii" 
                                   min="1000" max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="cod_caen" class="form-label">Cod CAEN</label>
                            <input type="text" class="form-control" name="cod_caen" id="cod_caen">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="">Selectați statusul</option>
                                <option value="activ">Activ</option>
                                <option value="dezactivat">Dezactivat</option>
                            </select>
                            <div class="invalid-feedback">Vă rugăm să selectați statusul.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descr_caen" class="form-label">Descriere CAEN</label>
                        <textarea class="form-control" name="descr_caen" id="descr_caen" rows="2"></textarea>
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
function editProtopopiat(protopopiat) {
    document.getElementById('protopopiatModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Protopopiat';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('protopopiatId').value = protopopiat.id_proterie;
    document.getElementById('denumire').value = protopopiat.denumire;
    document.getElementById('cui').value = protopopiat.cui;
    document.getElementById('adresa').value = protopopiat.adresa || '';
    document.getElementById('localitate').value = protopopiat.localitate || '';
    document.getElementById('judet').value = protopopiat.judet || '';
    document.getElementById('anul_infiintarii').value = protopopiat.anul_infiintarii || '';
    document.getElementById('cod_caen').value = protopopiat.cod_caen || '';
    document.getElementById('status').value = protopopiat.status || '';
    document.getElementById('descr_caen').value = protopopiat.descr_caen || '';
    document.getElementById('observatii').value = protopopiat.observatii || '';
    
    new bootstrap.Modal(document.getElementById('protopopiatModal')).show();
}

// Reset form când se închide modalul
document.getElementById('protopopiatModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('protopopiatForm').reset();
    document.getElementById('protopopiatForm').classList.remove('was-validated');
    document.getElementById('protopopiatModalTitle').innerHTML = '<i class="fas fa-crown me-2"></i>Adaugă Protopopiat';
    document.getElementById('formAction').value = 'add';
    document.getElementById('protopopiatId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>