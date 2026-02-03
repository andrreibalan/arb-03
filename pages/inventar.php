<?php
$page_title = 'Gestionare Inventar';
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
        $id_inventar = $_POST['id_inventar'] ?? null;
        $UnitCultCentrala = sanitize($_POST['UnitCultCentrala']);
        $id_parohie = intval($_POST['id_parohie']);
        $Localitate = sanitize($_POST['Localitate']);
        $Denumire = sanitize($_POST['Denumire']);
        $Valoare = !empty($_POST['Valoare']) ? floatval($_POST['Valoare']) : null;
        $NrInventar = sanitize($_POST['NrInventar']);
        $DataIntrarii = $_POST['DataIntrarii'] ?? null;
        $AutorProducator = sanitize($_POST['AutorProducator']);
        $Descriere = sanitize($_POST['Descriere']);
        $UnitateMasura = sanitize($_POST['UnitateMasura']);
        $NrUnitateMasura = !empty($_POST['NrUnitateMasura']) ? intval($_POST['NrUnitateMasura']) : null;
        $Colectia = sanitize($_POST['Colectia']);
        $MaterialTitlu = sanitize($_POST['MaterialTitlu']);
        $DimensiuniGreutate = sanitize($_POST['DimensiuniGreutate']);
        $StareConservare = sanitize($_POST['StareConservare']);
        $Provenienta = sanitize($_POST['Provenienta']);
        $DocumentIntrare = sanitize($_POST['DocumentIntrare']);
        $ValoareIntrare = !empty($_POST['ValoareIntrare']) ? floatval($_POST['ValoareIntrare']) : null;
        $DataIesire = $_POST['DataIesire'] ?? null;
        $MotivIesire = sanitize($_POST['MotivIesire']);
        $DataVerificare = $_POST['DataVerificare'] ?? null;
        $Observatii = sanitize($_POST['Observatii']);
        $CategorieBun = sanitize($_POST['CategorieBun']);
        
        // Verifică permisiunile pentru parohie
        if (!hasAccessToParohie($id_parohie)) {
            $_SESSION['error_message'] = 'Nu aveți permisiunea să adăugați inventar în această parohie!';
            redirect('inventar.php');
        }
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO inventar (UnitCultCentrala, id_parohie, Localitate, Denumire, Valoare, 
                                        NrInventar, DataIntrarii, AutorProducator, Descriere, UnitateMasura, 
                                        NrUnitateMasura, Colectia, MaterialTitlu, DimensiuniGreutate, 
                                        StareConservare, Provenienta, DocumentIntrare, ValoareIntrare, 
                                        DataIesire, MotivIesire, DataVerificare, Observatii, CategorieBun)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$UnitCultCentrala, $id_parohie, $Localitate, $Denumire, $Valoare, 
                              $NrInventar, $DataIntrarii, $AutorProducator, $Descriere, $UnitateMasura, 
                              $NrUnitateMasura, $Colectia, $MaterialTitlu, $DimensiuniGreutate, 
                              $StareConservare, $Provenienta, $DocumentIntrare, $ValoareIntrare, 
                              $DataIesire, $MotivIesire, $DataVerificare, $Observatii, $CategorieBun]);
                $_SESSION['success_message'] = 'Obiectul a fost adăugat în inventar cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE inventar SET UnitCultCentrala = ?, id_parohie = ?, Localitate = ?, 
                                      Denumire = ?, Valoare = ?, NrInventar = ?, DataIntrarii = ?, 
                                      AutorProducator = ?, Descriere = ?, UnitateMasura = ?, 
                                      NrUnitateMasura = ?, Colectia = ?, MaterialTitlu = ?, 
                                      DimensiuniGreutate = ?, StareConservare = ?, Provenienta = ?, 
                                      DocumentIntrare = ?, ValoareIntrare = ?, DataIesire = ?, 
                                      MotivIesire = ?, DataVerificare = ?, Observatii = ?, CategorieBun = ?
                    WHERE id_inventar = ?
                ");
                $stmt->execute([$UnitCultCentrala, $id_parohie, $Localitate, $Denumire, $Valoare, 
                              $NrInventar, $DataIntrarii, $AutorProducator, $Descriere, $UnitateMasura, 
                              $NrUnitateMasura, $Colectia, $MaterialTitlu, $DimensiuniGreutate, 
                              $StareConservare, $Provenienta, $DocumentIntrare, $ValoareIntrare, 
                              $DataIesire, $MotivIesire, $DataVerificare, $Observatii, $CategorieBun, $id_inventar]);
                $_SESSION['success_message'] = 'Obiectul din inventar a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        
        redirect('inventar.php');
    }
}

// Ștergere obiect inventar
if (isset($_GET['delete'])) {
    $id_inventar = intval($_GET['delete']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM inventar WHERE id_inventar = ?");
        $stmt->execute([$id_inventar]);
        $_SESSION['success_message'] = 'Obiectul a fost șters din inventar cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea obiectului: ' . $e->getMessage();
    }
    
    redirect('inventar.php');
}

// Construiește query-ul pentru inventar în funcție de rol
$where_clause = '';
$params = [];

if (hasRole('paroh')) {
    $where_clause = 'WHERE i.id_parohie = ?';
    $params[] = $_SESSION['user_parohie'];
} elseif (hasRole('protopop')) {
    $where_clause = 'WHERE p.id_proterie = ?';
    $params[] = $_SESSION['user_proterie'];
}

$stmt = $pdo->prepare("
    SELECT i.*, p.nume_parohie
    FROM inventar i
    LEFT JOIN parohii p ON i.id_parohie = p.id_parohie
    $where_clause
    ORDER BY i.DataIntrarii DESC, i.Denumire
");
$stmt->execute($params);
$inventar = $stmt->fetchAll();

// Obține parohiile accesibile
$parohii_accesibile = getAccessibleParohii();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-boxes me-2"></i>
                Gestionare Inventar
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inventarModal">
                <i class="fas fa-plus me-2"></i>Adaugă Obiect
            </button>
        </div>
        
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Obiectelor din Inventar
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nr. Inventar</th>
                                <th>Denumire</th>
                                <th>Categoria</th>
                                <th>Parohia</th>
                                <th>Valoare (RON)</th>
                                <th>Data Intrării</th>
                                <th>Starea</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventar as $obiect): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($obiect['NrInventar']); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($obiect['Denumire']); ?></strong>
                                    <?php if (!empty($obiect['AutorProducator'])): ?>
                                        <br><small class="text-muted">Autor: <?php echo htmlspecialchars($obiect['AutorProducator']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($obiect['CategorieBun'])): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($obiect['CategorieBun']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Necategorizat</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($obiect['nume_parohie']); ?></td>
                                <td>
                                    <?php if (!empty($obiect['Valoare'])): ?>
                                        <?php echo number_format($obiect['Valoare'], 2); ?> RON
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($obiect['DataIntrarii'])): ?>
                                        <?php echo date('d.m.Y', strtotime($obiect['DataIntrarii'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($obiect['StareConservare'])): ?>
                                        <?php 
                                        $stare_class = '';
                                        switch(strtolower($obiect['StareConservare'])) {
                                            case 'bună': case 'buna': $stare_class = 'bg-success'; break;
                                            case 'satisfăcătoare': case 'satisfacatoare': $stare_class = 'bg-warning'; break;
                                            case 'proastă': case 'proasta': $stare_class = 'bg-danger'; break;
                                            default: $stare_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $stare_class; ?>"><?php echo htmlspecialchars($obiect['StareConservare']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Nespecificat</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="editInventar(<?php echo htmlspecialchars(json_encode($obiect)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $obiect['id_inventar']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest obiect din inventar?')">
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

<!-- Modal pentru adăugare/editare inventar -->
<div class="modal fade" id="inventarModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inventarModalTitle">
                    <i class="fas fa-box me-2"></i>
                    Adaugă Obiect în Inventar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="inventarForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_inventar" id="inventarId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Denumire" class="form-label">Denumire *</label>
                            <input type="text" class="form-control" name="Denumire" id="Denumire" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți denumirea obiectului.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="NrInventar" class="form-label">Număr Inventar *</label>
                            <input type="text" class="form-control" name="NrInventar" id="NrInventar" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numărul de inventar.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="CategorieBun" class="form-label">Categoria Bunului</label>
                            <select class="form-select" name="CategorieBun" id="CategorieBun">
                                <option value="">Selectați categoria</option>
                                <option value="Obiecte de cult">Obiecte de cult</option>
                                <option value="Cărți">Cărți</option>
                                <option value="Icoane">Icoane</option>
                                <option value="Mobilier">Mobilier</option>
                                <option value="Vase sacre">Vase sacre</option>
                                <option value="Veșminte">Veșminte</option>
                                <option value="Documente">Documente</option>
                                <option value="Altele">Altele</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="Valoare" class="form-label">Valoare (RON)</label>
                            <input type="number" class="form-control" name="Valoare" id="Valoare" step="0.01" min="0">
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
                        <div class="col-md-6 mb-3">
                            <label for="AutorProducator" class="form-label">Autor/Producător</label>
                            <input type="text" class="form-control" name="AutorProducator" id="AutorProducator">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="DataIntrarii" class="form-label">Data Intrării</label>
                            <input type="date" class="form-control" name="DataIntrarii" id="DataIntrarii">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="UnitateMasura" class="form-label">Unitate Măsură</label>
                            <select class="form-select" name="UnitateMasura" id="UnitateMasura">
                                <option value="">Selectați unitatea</option>
                                <option value="bucată">bucată</option>
                                <option value="set">set</option>
                                <option value="pereche">pereche</option>
                                <option value="kg">kg</option>
                                <option value="litru">litru</option>
                                <option value="metru">metru</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="NrUnitateMasura" class="form-label">Număr Unități</label>
                            <input type="number" class="form-control" name="NrUnitateMasura" id="NrUnitateMasura" min="1">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="StareConservare" class="form-label">Starea de Conservare</label>
                            <select class="form-select" name="StareConservare" id="StareConservare">
                                <option value="">Selectați starea</option>
                                <option value="Bună">Bună</option>
                                <option value="Satisfăcătoare">Satisfăcătoare</option>
                                <option value="Proastă">Proastă</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="MaterialTitlu" class="form-label">Material/Titlu</label>
                            <input type="text" class="form-control" name="MaterialTitlu" id="MaterialTitlu">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="DimensiuniGreutate" class="form-label">Dimensiuni/Greutate</label>
                            <input type="text" class="form-control" name="DimensiuniGreutate" id="DimensiuniGreutate">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Provenienta" class="form-label">Proveniența</label>
                            <input type="text" class="form-control" name="Provenienta" id="Provenienta">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="Colectia" class="form-label">Colecția</label>
                            <input type="text" class="form-control" name="Colectia" id="Colectia">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="UnitCultCentrala" class="form-label">Unitatea de Cult Centrală</label>
                            <input type="text" class="form-control" name="UnitCultCentrala" id="UnitCultCentrala">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="Localitate" class="form-label">Localitate</label>
                            <input type="text" class="form-control" name="Localitate" id="Localitate">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="Descriere" class="form-label">Descriere</label>
                        <textarea class="form-control" name="Descriere" id="Descriere" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="Observatii" class="form-label">Observații</label>
                        <textarea class="form-control" name="Observatii" id="Observatii" rows="2"></textarea>
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
function editInventar(obiect) {
    document.getElementById('inventarModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Obiect din Inventar';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('inventarId').value = obiect.id_inventar;
    document.getElementById('Denumire').value = obiect.Denumire;
    document.getElementById('NrInventar').value = obiect.NrInventar;
    document.getElementById('CategorieBun').value = obiect.CategorieBun || '';
    document.getElementById('Valoare').value = obiect.Valoare || '';
    document.getElementById('id_parohie').value = obiect.id_parohie || '';
    document.getElementById('AutorProducator').value = obiect.AutorProducator || '';
    document.getElementById('DataIntrarii').value = obiect.DataIntrarii || '';
    document.getElementById('UnitateMasura').value = obiect.UnitateMasura || '';
    document.getElementById('NrUnitateMasura').value = obiect.NrUnitateMasura || '';
    document.getElementById('StareConservare').value = obiect.StareConservare || '';
    document.getElementById('MaterialTitlu').value = obiect.MaterialTitlu || '';
    document.getElementById('DimensiuniGreutate').value = obiect.DimensiuniGreutate || '';
    document.getElementById('Provenienta').value = obiect.Provenienta || '';
    document.getElementById('Colectia').value = obiect.Colectia || '';
    document.getElementById('UnitCultCentrala').value = obiect.UnitCultCentrala || '';
    document.getElementById('Localitate').value = obiect.Localitate || '';
    document.getElementById('Descriere').value = obiect.Descriere || '';
    document.getElementById('Observatii').value = obiect.Observatii || '';
    
    new bootstrap.Modal(document.getElementById('inventarModal')).show();
}

// Reset form când se închide modalul
document.getElementById('inventarModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('inventarForm').reset();
    document.getElementById('inventarForm').classList.remove('was-validated');
    document.getElementById('inventarModalTitle').innerHTML = '<i class="fas fa-box me-2"></i>Adaugă Obiect în Inventar';
    document.getElementById('formAction').value = 'add';
    document.getElementById('inventarId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>