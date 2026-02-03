<?php
$page_title = 'Gestionare Parohii';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
if (!hasRole('admin') && !hasRole('protopop')) {
    die("Acces interzis! Nu aveți permisiunile necesare pentru a accesa această pagină.");
}

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id_parohie = $_POST['id_parohie'] ?? null;
        $id_proterie = sanitize($_POST['id_proterie']);
        $id_tip_parohie = !empty($_POST['id_tip_parohie']) ? sanitize($_POST['id_tip_parohie']) : null;
        $nume_parohie = sanitize($_POST['nume_parohie']);
        $cui = sanitize($_POST['cui']);
        $adresa = sanitize($_POST['adresa']);
        $localitate = sanitize($_POST['localitate']);
        $judet = sanitize($_POST['judet']);
        $anul_infiintarii = !empty($_POST['anul_infiintarii']) ? intval($_POST['anul_infiintarii']) : null;
        $observatii = sanitize($_POST['observatii']);
        $email = sanitize($_POST['email']);
        $telefon = sanitize($_POST['telefon']);
        $site = sanitize($_POST['site']);
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO parohii (id_proterie, id_tip_parohie, nume_parohie, cui, adresa, 
                                       localitate, judet, anul_infiintarii, observatii, email, telefon, site)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_proterie, $id_tip_parohie, $nume_parohie, $cui, $adresa, 
                              $localitate, $judet, $anul_infiintarii, $observatii, $email, $telefon, $site]);
                $_SESSION['success_message'] = 'Parohia a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE parohii SET id_proterie = ?, id_tip_parohie = ?, nume_parohie = ?, 
                                     cui = ?, adresa = ?, localitate = ?, judet = ?, 
                                     anul_infiintarii = ?, observatii = ?, email = ?, telefon = ?, site = ?
                    WHERE id_parohie = ?
                ");
                $stmt->execute([$id_proterie, $id_tip_parohie, $nume_parohie, $cui, $adresa, 
                              $localitate, $judet, $anul_infiintarii, $observatii, $email, $telefon, $site, $id_parohie]);
                $_SESSION['success_message'] = 'Parohia a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('parohii.php');
    }
}

// Ștergere parohie
if (isset($_GET['delete'])) {
    $id_parohie = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM parohii WHERE id_parohie = ?");
        $stmt->execute([$id_parohie]);
        $_SESSION['success_message'] = 'Parohia a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea parohiei: ' . $e->getMessage();
    }
    
    redirect('parohii.php');
}

// Obține lista parohiilor
$where_clause = '';
$params = [];

if (hasRole('protopop')) {
    $where_clause = 'WHERE p.id_proterie = ?';
    $params[] = $_SESSION['user_proterie'];
}

$stmt = $pdo->prepare("
    SELECT p.*, pr.denumire as nume_proterie, tp.nume_tip_parohie
    FROM parohii p
    LEFT JOIN protopopiate pr ON p.id_proterie = pr.id_proterie
    LEFT JOIN tipuri_parohie tp ON p.id_tip_parohie = tp.id_tip_parohie
    $where_clause
    ORDER BY p.nume_parohie
");
$stmt->execute($params);
$parohii = $stmt->fetchAll();

// Obține lista protopopiatelor pentru dropdown
$protopopiate_query = "SELECT * FROM protopopiate WHERE status = 'activ' ORDER BY denumire";
if (hasRole('protopop')) {
    $protopopiate_query = "SELECT * FROM protopopiate WHERE id_proterie = ? AND status = 'activ'";
    $stmt = $pdo->prepare($protopopiate_query);
    $stmt->execute([$_SESSION['user_proterie']]);
} else {
    $stmt = $pdo->query($protopopiate_query);
}
$protopopiate = $stmt->fetchAll();

// Obține tipurile de parohii
$stmt = $pdo->query("SELECT * FROM tipuri_parohie ORDER BY nume_tip_parohie");
$tipuri_parohie = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-church me-2"></i>
                Gestionare Parohii
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#parohieModal">
                <i class="fas fa-plus me-2"></i>Adaugă Parohie
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Parohiilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                              <!--  <th>ID</th> -->
                                <th>Nume Parohie</th>
                                <th>Protopopiat</th>
                                <th>CUI</th>
                                <th>Localitate</th>
                                <th>Județ</th>
                                <th>Anul Înființării</th>
                                <th>Contact</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parohii as $parohie): ?>
                            <tr>
                                <!-- <td><?php echo $parohie['id_parohie']; ?></td> -->
                                <td>
                                    <strong><?php echo htmlspecialchars($parohie['nume_parohie']); ?></strong>
                                    <?php if (!empty($parohie['nume_tip_parohie'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($parohie['nume_tip_parohie']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><strong>
                                     <span class="badge bg-danger"><?php echo htmlspecialchars($parohie['nume_proterie']); ?></span></strong></td>
                                <td><?php echo htmlspecialchars($parohie['cui']); ?></td>
                                <td><?php echo htmlspecialchars($parohie['localitate']); ?></td>
                                <td><?php echo htmlspecialchars($parohie['judet']); ?></td>
                                <td><?php echo $parohie['anul_infiintarii']; ?></td>
                                <td>
                                    <?php if (!empty($parohie['telefon'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($parohie['telefon']); ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($parohie['email'])): ?>
                                        <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($parohie['email']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="view_parohie.php?id=<?php echo $parohie['id_parohie']; ?>"
                                       class="btn btn-sm btn-success me-1"
                                       title="Vizualizează">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editParohie(<?php echo htmlspecialchars(json_encode($parohie)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $parohie['id_parohie']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această parohie?')">
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

<!-- Modal pentru adăugare/editare parohie -->
<div class="modal fade" id="parohieModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="parohieModalTitle">
                    <i class="fas fa-church me-2"></i>
                    Adaugă Parohie
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="parohieForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_parohie" id="parohieId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_proterie" class="form-label">Protopopiat *</label>
                            <select class="form-select" name="id_proterie" id="id_proterie" required>
                                <option value="">Selectați protopopiatul</option>
                                <?php foreach ($protopopiate as $protopopiat): ?>
                                    <option value="<?php echo $protopopiat['id_proterie']; ?>">
                                        <?php echo htmlspecialchars($protopopiat['denumire']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vă rugăm să selectați protopopiatul.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="id_tip_parohie" class="form-label">Tip Parohie</label>
                            <select class="form-select" name="id_tip_parohie" id="id_tip_parohie">
                                <option value="">Selectați tipul</option>
                                <?php foreach ($tipuri_parohie as $tip): ?>
                                    <option value="<?php echo $tip['id_tip_parohie']; ?>">
                                        <?php echo htmlspecialchars($tip['nume_tip_parohie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="nume_parohie" class="form-label">Nume Parohie *</label>
                            <input type="text" class="form-control" name="nume_parohie" id="nume_parohie" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele parohiei.</div>
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
                            <label for="telefon" class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="telefon" id="telefon">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site" class="form-label">Site Web</label>
                        <input type="url" class="form-control" name="site" id="site">
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
function editParohie(parohie) {
    document.getElementById('parohieModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Parohie';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('parohieId').value = parohie.id_parohie;
    document.getElementById('id_proterie').value = parohie.id_proterie;
    document.getElementById('id_tip_parohie').value = parohie.id_tip_parohie || '';
    document.getElementById('nume_parohie').value = parohie.nume_parohie;
    document.getElementById('cui').value = parohie.cui;
    document.getElementById('adresa').value = parohie.adresa || '';
    document.getElementById('localitate').value = parohie.localitate || '';
    document.getElementById('judet').value = parohie.judet || '';
    document.getElementById('anul_infiintarii').value = parohie.anul_infiintarii || '';
    document.getElementById('telefon').value = parohie.telefon || '';
    document.getElementById('email').value = parohie.email || '';
    document.getElementById('site').value = parohie.site || '';
    document.getElementById('observatii').value = parohie.observatii || '';
    
    new bootstrap.Modal(document.getElementById('parohieModal')).show();
}

// Reset form când se închide modalul
document.getElementById('parohieModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('parohieForm').reset();
    document.getElementById('parohieForm').classList.remove('was-validated');
    document.getElementById('parohieModalTitle').innerHTML = '<i class="fas fa-church me-2"></i>Adaugă Parohie';
    document.getElementById('formAction').value = 'add';
    document.getElementById('parohieId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>