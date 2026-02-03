<?php
$page_title = 'Gestionare Biserici';
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
        $id_biserica = $_POST['id_biserica'] ?? null;
        $id_tip_bis = !empty($_POST['id_tip_bis']) ? intval($_POST['id_tip_bis']) : null;
        $hram = !empty($_POST['hram']) ? trim($_POST['hram']) : null;
        $anul_constructie = !empty($_POST['anul_constructie']) ? intval($_POST['anul_constructie']) : null;
        $adresa = sanitize($_POST['adresa']);
        $localitate = sanitize($_POST['localitate']);
        $judetul = sanitize($_POST['judetul']);
        $obs_biserica = sanitize($_POST['obs_biserica']);
        $id_parohie = intval($_POST['id_parohie']);
        
        // Verifică permisiunile pentru parohie
        if (!hasAccessToParohie($id_parohie)) {
            $_SESSION['error_message'] = 'Nu aveți permisiunea să adăugați biserici în această parohie!';
            redirect('biserici.php');
        }
        
        try {
            // Convert hram text to id_hram
            $id_hram = null;
            if (!empty($hram)) {
                // Try to find existing hram in database
                $stmt = $pdo->prepare("SELECT id_hram FROM hramuri WHERE Hram = ?");
                $stmt->execute([$hram]);
                $existing_hram = $stmt->fetch();

                if ($existing_hram) {
                    $id_hram = $existing_hram['id_hram'];
                } else {
                    // Insert new hram if it doesn't exist
                    $stmt = $pdo->prepare("INSERT INTO hramuri (Hram) VALUES (?)");
                    $stmt->execute([$hram]);
                    $id_hram = $pdo->lastInsertId();
                }
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO biserici (id_tip_bis, id_hram, anul_constructie, adresa,
                                        localitate, judetul, obs_biserica, id_parohie)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_tip_bis, $id_hram, $anul_constructie, $adresa,
                              $localitate, $judetul, $obs_biserica, $id_parohie]);
                $_SESSION['success_message'] = 'Biserica a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE biserici SET id_tip_bis = ?, id_hram = ?, anul_constructie = ?,
                                      adresa = ?, localitate = ?, judetul = ?,
                                      obs_biserica = ?, id_parohie = ?
                    WHERE id_biserica = ?
                ");
                $stmt->execute([$id_tip_bis, $id_hram, $anul_constructie, $adresa,
                              $localitate, $judetul, $obs_biserica, $id_parohie, $id_biserica]);
                $_SESSION['success_message'] = 'Biserica a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('biserici.php');
    }
}

// Ștergere biserică
if (isset($_GET['delete'])) {
    $id_biserica = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM biserici WHERE id_biserica = ?");
        $stmt->execute([$id_biserica]);
        $_SESSION['success_message'] = 'Biserica a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea bisericii: ' . $e->getMessage();
    }
    
    redirect('biserici.php');
}

// Construiește query-ul pentru biserici în funcție de rol
$where_clause = '';
$params = [];

if (hasRole('paroh')) {
    $where_clause = 'WHERE b.id_parohie = ?';
    $params[] = $_SESSION['user_parohie'];
} elseif (hasRole('protopop')) {
    $where_clause = 'WHERE p.id_proterie = ?';
    $params[] = $_SESSION['user_proterie'];
}

$stmt = $pdo->prepare("
    SELECT b.*, p.nume_parohie, h.Hram, tb.tip_biserica
    FROM biserici b
    LEFT JOIN parohii p ON b.id_parohie = p.id_parohie
    LEFT JOIN hramuri h ON b.id_hram = h.id_hram
    LEFT JOIN tipuri_biserici tb ON b.id_tip_bis = tb.id_tip_bis
    $where_clause
    ORDER BY p.nume_parohie, b.localitate
");
$stmt->execute($params);
$biserici = $stmt->fetchAll();

// Obține parohiile accesibile
$parohii_accesibile = getAccessibleParohii();

// Obține hramurile
$stmt = $pdo->query("SELECT * FROM hramuri ORDER BY Hram");
$hramuri = $stmt->fetchAll();

// Obține tipurile de biserici
$stmt = $pdo->query("SELECT * FROM tipuri_biserici ORDER BY tip_biserica");
$tipuri_biserici = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-church me-2"></i>
                Gestionare Biserici
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bisericaModal">
                <i class="fas fa-plus me-2"></i>Adaugă Biserică
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Bisericilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                               <!-- <th>ID</th> -->
                                <th>Parohia</th>
                                <th>Hram</th>
                                <th>Localitate</th>
                                <th>Județ</th>
                                <th>Anul Construcției</th>
                                <th>Tip Biserică</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($biserici as $biserica): ?>
                            <tr>
                                <!-- <td><?php echo $biserica['id_biserica']; ?></td> -->
                                <td>
                                    <strong><?php echo htmlspecialchars($biserica['nume_parohie']); ?></strong>
                                    <?php if (!empty($biserica['adresa'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($biserica['adresa']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($biserica['Hram'])): ?>
                                        <?php
                                        $hramuri = explode(',', $biserica['Hram']);
                                        foreach ($hramuri as $hram):
                                        ?>
                                            <span class="badge bg-primary me-1"><?php echo htmlspecialchars(trim($hram)); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nespecificat</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($biserica['localitate']); ?></td>
                                <td><?php echo htmlspecialchars($biserica['judetul']); ?></td>
                                <td>
                                    <?php if (!empty($biserica['anul_constructie'])): ?>
                                        <?php echo $biserica['anul_constructie']; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($biserica['tip_biserica'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($biserica['tip_biserica']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Nespecificat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editBiserica(<?php echo htmlspecialchars(json_encode($biserica)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $biserica['id_biserica']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această biserică?')">
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

<!-- Modal pentru adăugare/editare biserică -->
<div class="modal fade" id="bisericaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bisericaModalTitle">
                    <i class="fas fa-church me-2"></i>
                    Adaugă Biserică
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bisericaForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_biserica" id="bisericaId">
                    
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
                            <label for="hram" class="form-label">Hram</label>
                            <input type="text" class="form-control" name="hram" id="hram"
                                   placeholder="Introduceți hramul (ex: Sf. Nicolae, Sf. Dumitru)">
                            <div class="form-text">Puteți introduce mai multe hramuri separate prin virgulă</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_tip_bis" class="form-label">Tip Biserică</label>
                            <select class="form-select" name="id_tip_bis" id="id_tip_bis">
                                <option value="">Selectați tipul</option>
                                <?php foreach ($tipuri_biserici as $tip): ?>
                                    <option value="<?php echo $tip['id_tip_bis']; ?>">
                                        <?php echo htmlspecialchars($tip['tip_biserica']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="anul_constructie" class="form-label">Anul Construcției</label>
                            <input type="number" class="form-control" name="anul_constructie" id="anul_constructie" 
                                   min="1000" max="<?php echo date('Y'); ?>">
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
                        <label for="obs_biserica" class="form-label">Observații</label>
                        <textarea class="form-control" name="obs_biserica" id="obs_biserica" rows="3"></textarea>
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
function editBiserica(biserica) {
    document.getElementById('bisericaModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Biserică';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('bisericaId').value = biserica.id_biserica;
    document.getElementById('id_parohie').value = biserica.id_parohie || '';
    document.getElementById('hram').value = biserica.Hram || '';
    document.getElementById('id_tip_bis').value = biserica.id_tip_bis || '';
    document.getElementById('anul_constructie').value = biserica.anul_constructie || '';
    document.getElementById('adresa').value = biserica.adresa || '';
    document.getElementById('localitate').value = biserica.localitate || '';
    document.getElementById('judetul').value = biserica.judetul || '';
    document.getElementById('obs_biserica').value = biserica.obs_biserica || '';
    
    new bootstrap.Modal(document.getElementById('bisericaModal')).show();
}

// Reset form când se închide modalul
document.getElementById('bisericaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('bisericaForm').reset();
    document.getElementById('bisericaForm').classList.remove('was-validated');
    document.getElementById('bisericaModalTitle').innerHTML = '<i class="fas fa-church me-2"></i>Adaugă Biserică';
    document.getElementById('formAction').value = 'add';
    document.getElementById('bisericaId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>