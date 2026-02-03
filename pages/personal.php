<?php
$page_title = 'Gestionare Personal';
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
        $id_pers = $_POST['id_pers'] ?? null;
        $numele = sanitize($_POST['numele']);
        $prenumele = sanitize($_POST['prenumele']);
        $data_nasterii = $_POST['data_nasterii'] ?? null;
        $cnp = sanitize($_POST['cnp']);
        $adresa = sanitize($_POST['adresa']);
        $localitate = sanitize($_POST['localitate']);
        $judet = sanitize($_POST['judet']);
        $loc_nastere = sanitize($_POST['loc_nastere']);
        $jud_nastere = sanitize($_POST['jud_nastere']);
        $id_stare_civila = !empty($_POST['id_stare_civila']) ? intval($_POST['id_stare_civila']) : null;
        $id_functie = !empty($_POST['id_functie']) ? intval($_POST['id_functie']) : null;
        $data_angajarii = $_POST['data_angajarii'] ?? null;
        $nr_decizie = sanitize($_POST['nr_decizie']);
        $data_incetarii_job = $_POST['data_incetarii_job'] ?? null;
        $observatii = sanitize($_POST['Observatii']);
        $id_parohie = intval($_POST['id_parohie']);
        
        // Verifică permisiunile pentru parohie
        if (!hasAccessToParohie($id_parohie)) {
            $_SESSION['error_message'] = 'Nu aveți permisiunea să adăugați personal în această parohie!';
            redirect('personal.php');
        }
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO personal (numele, prenumele, data_nasterii, cnp, adresa, localitate, 
                                        judet, loc_nastere, jud_nastere, id_stare_civila, id_functie, 
                                        data_angajarii, nr_decizie, data_incetarii_job, Observatii, id_parohie)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$numele, $prenumele, $data_nasterii, $cnp, $adresa, $localitate, 
                              $judet, $loc_nastere, $jud_nastere, $id_stare_civila, $id_functie, 
                              $data_angajarii, $nr_decizie, $data_incetarii_job, $observatii, $id_parohie]);
                $_SESSION['success_message'] = 'Angajatul a fost adăugat cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE personal SET numele = ?, prenumele = ?, data_nasterii = ?, cnp = ?, 
                                      adresa = ?, localitate = ?, judet = ?, loc_nastere = ?, 
                                      jud_nastere = ?, id_stare_civila = ?, id_functie = ?, 
                                      data_angajarii = ?, nr_decizie = ?, data_incetarii_job = ?, 
                                      Observatii = ?, id_parohie = ?
                    WHERE id_pers = ?
                ");
                $stmt->execute([$numele, $prenumele, $data_nasterii, $cnp, $adresa, $localitate, 
                              $judet, $loc_nastere, $jud_nastere, $id_stare_civila, $id_functie, 
                              $data_angajarii, $nr_decizie, $data_incetarii_job, $observatii, $id_parohie, $id_pers]);
                $_SESSION['success_message'] = 'Angajatul a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('personal.php');
    }
}

// Ștergere angajat
if (isset($_GET['delete'])) {
    $id_pers = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM personal WHERE id_pers = ?");
        $stmt->execute([$id_pers]);
        $_SESSION['success_message'] = 'Angajatul a fost șters cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea angajatului: ' . $e->getMessage();
    }
    
    redirect('personal.php');
}

// Construiește query-ul pentru personal în funcție de rol
$where_clause = '';
$params = [];

if (hasRole('paroh')) {
    $where_clause = 'WHERE pe.id_parohie = ?';
    $params[] = $_SESSION['user_parohie'];
} elseif (hasRole('protopop')) {
    $where_clause = 'WHERE p.id_proterie = ?';
    $params[] = $_SESSION['user_proterie'];
}

$stmt = $pdo->prepare("
    SELECT pe.*, p.nume_parohie, f.nume_functie, sc.nume_stare_civila
    FROM personal pe
    LEFT JOIN parohii p ON pe.id_parohie = p.id_parohie
    LEFT JOIN functii f ON pe.id_functie = f.id_functie
    LEFT JOIN stari_civile sc ON pe.id_stare_civila = sc.id_stare_civila
    $where_clause
    ORDER BY pe.numele, pe.prenumele
");
$stmt->execute($params);
$personal = $stmt->fetchAll();

// Obține parohiile accesibile
$parohii_accesibile = getAccessibleParohii();

// Obține funcțiile
$stmt = $pdo->query("SELECT * FROM functii ORDER BY nume_functie");
$functii = $stmt->fetchAll();

// Obține stările civile
$stmt = $pdo->query("SELECT * FROM stari_civile ORDER BY nume_stare_civila");
$stari_civile = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>
                Gestionare Personal
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#personalModal">
                <i class="fas fa-plus me-2"></i>Adaugă Angajat
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Angajaților
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                               <!-- <th>ID</th> -->
                                <th>Nume Complet</th>
                                <th>CNP</th>
                                <th>Funcția</th>
                                <th>Parohia</th>
                                <th>Data Angajării</th>
                                <th>Contact</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personal as $angajat): ?>
                            <tr>
                                <!-- <td><?php echo $angajat['id_pers']; ?></td> -->
                                <td>
                                    <strong><?php echo htmlspecialchars($angajat['numele'] . ' ' . $angajat['prenumele']); ?></strong>
                                    <?php if (!empty($angajat['data_nasterii'])): ?>
                                        <br><small class="text-muted">Născut: <?php echo date('d.m.Y', strtotime($angajat['data_nasterii'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($angajat['cnp']); ?></td>
                                <td>
                                    <?php if (!empty($angajat['nume_functie'])): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($angajat['nume_functie']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Neasignat</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($angajat['nume_parohie']); ?> </strong></td>
                                <td>
                                    <?php if (!empty($angajat['data_angajarii'])): ?>
                                        <?php echo date('d.m.Y', strtotime($angajat['data_angajarii'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($angajat['localitate'])): ?>
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($angajat['localitate']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="view_personal.php?id=<?php echo $angajat['id_pers']; ?>"
                                       class="btn btn-sm btn-success me-1"
                                       title="Vezi detalii">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editPersonal(<?php echo htmlspecialchars(json_encode($angajat)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $angajat['id_pers']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest angajat?')">
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

<!-- Modal pentru adăugare/editare personal -->
<div class="modal fade" id="personalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personalModalTitle">
                    <i class="fas fa-user me-2"></i>
                    Adaugă Angajat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="personalForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_pers" id="personalId">
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="numele" class="form-label">Numele *</label>
                            <input type="text" class="form-control" name="numele" id="numele" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="prenumele" class="form-label">Prenumele *</label>
                            <input type="text" class="form-control" name="prenumele" id="prenumele" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți prenumele.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="cnp" class="form-label">CNP *</label>
                            <input type="text" class="form-control" name="cnp" id="cnp" required maxlength="13">
                            <div class="invalid-feedback">Vă rugăm să introduceți CNP-ul.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="data_nasterii" class="form-label">Data Nașterii</label>
                            <input type="date" class="form-control" name="data_nasterii" id="data_nasterii">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="id_stare_civila" class="form-label">Starea Civilă</label>
                            <select class="form-select" name="id_stare_civila" id="id_stare_civila">
                                <option value="">Selectați starea civilă</option>
                                <?php foreach ($stari_civile as $stare): ?>
                                    <option value="<?php echo $stare['id_stare_civila']; ?>">
                                        <?php echo htmlspecialchars($stare['nume_stare_civila']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="id_functie" class="form-label">Funcția</label>
                            <select class="form-select" name="id_functie" id="id_functie">
                                <option value="">Selectați funcția</option>
                                <?php foreach ($functii as $functie): ?>
                                    <option value="<?php echo $functie['id_functie']; ?>">
                                        <?php echo htmlspecialchars($functie['nume_functie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="adresa" class="form-label">Adresa</label>
                        <input type="text" class="form-control" name="adresa" id="adresa">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="localitate" class="form-label">Localitate</label>
                            <input type="text" class="form-control" name="localitate" id="localitate">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="judet" class="form-label">Județ</label>
                            <input type="text" class="form-control" name="judet" id="judet">
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
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="loc_nastere" class="form-label">Locul Nașterii</label>
                            <input type="text" class="form-control" name="loc_nastere" id="loc_nastere">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="jud_nastere" class="form-label">Județ Naștere</label>
                            <input type="text" class="form-control" name="jud_nastere" id="jud_nastere">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="data_angajarii" class="form-label">Data Angajării</label>
                            <input type="date" class="form-control" name="data_angajarii" id="data_angajarii">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nr_decizie" class="form-label">Numărul Deciziei</label>
                            <input type="text" class="form-control" name="nr_decizie" id="nr_decizie">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="data_incetarii_job" class="form-label">Data Încetării</label>
                            <input type="date" class="form-control" name="data_incetarii_job" id="data_incetarii_job">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="Observatii" class="form-label">Observații</label>
                        <textarea class="form-control" name="Observatii" id="Observatii" rows="3"></textarea>
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
function editPersonal(angajat) {
    document.getElementById('personalModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Angajat';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('personalId').value = angajat.id_pers;
    document.getElementById('numele').value = angajat.numele;
    document.getElementById('prenumele').value = angajat.prenumele;
    document.getElementById('cnp').value = angajat.cnp;
    document.getElementById('data_nasterii').value = angajat.data_nasterii || '';
    document.getElementById('id_stare_civila').value = angajat.id_stare_civila || '';
    document.getElementById('id_functie').value = angajat.id_functie || '';
    document.getElementById('adresa').value = angajat.adresa || '';
    document.getElementById('localitate').value = angajat.localitate || '';
    document.getElementById('judet').value = angajat.judet || '';
    document.getElementById('id_parohie').value = angajat.id_parohie || '';
    document.getElementById('loc_nastere').value = angajat.loc_nastere || '';
    document.getElementById('jud_nastere').value = angajat.jud_nastere || '';
    document.getElementById('data_angajarii').value = angajat.data_angajarii || '';
    document.getElementById('nr_decizie').value = angajat.nr_decizie || '';
    document.getElementById('data_incetarii_job').value = angajat.data_incetarii_job || '';
    document.getElementById('Observatii').value = angajat.Observatii || '';
    
    new bootstrap.Modal(document.getElementById('personalModal')).show();
}

// Reset form când se închide modalul
document.getElementById('personalModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('personalForm').reset();
    document.getElementById('personalForm').classList.remove('was-validated');
    document.getElementById('personalModalTitle').innerHTML = '<i class="fas fa-user me-2"></i>Adaugă Angajat';
    document.getElementById('formAction').value = 'add';
    document.getElementById('personalId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>