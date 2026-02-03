<?php
$page_title = 'Gestionare Concesionari';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
requireAnyRole('admin', 'paroh', 'cimitir');

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id_cesionar = $_POST['id_cesionar'] ?? null;
        $numele = sanitize($_POST['numele']);
        $prenumele = sanitize($_POST['prenumele']);
        $cnp = sanitize($_POST['cnp']);
        $localitate = sanitize($_POST['localitate']);
        $telefon = sanitize($_POST['telefon']);
        $email = sanitize($_POST['email'] ?? '');
        $adresa = sanitize($_POST['adresa'] ?? '');

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO concesionari (numele, prenumele, cnp, localitate, telefon, email, adresa)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$numele, $prenumele, $cnp, $localitate, $telefon, $email, $adresa]);
                $_SESSION['success_message'] = 'Concesionarul a fost adăugat cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE concesionari SET numele = ?, prenumele = ?, cnp = ?, localitate = ?,
                                          telefon = ?, email = ?, adresa = ?
                    WHERE id_cesionar = ?
                ");
                $stmt->execute([$numele, $prenumele, $cnp, $localitate, $telefon, $email, $adresa, $id_cesionar]);
                $_SESSION['success_message'] = 'Concesionarul a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }

        redirect('concesionari.php');
    }
}

// Ștergere concesionar
if (isset($_GET['delete'])) {
    $id_cesionar = intval($_GET['delete']);

    try {
        // Verifică dacă concesionarul are taxe asociate
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM taxe_cmt WHERE id_cesionar = ?");
        $stmt->execute([$id_cesionar]);
        $count_taxe = $stmt->fetchColumn();

        if ($count_taxe > 0) {
            $_SESSION['error_message'] = 'Nu puteți șterge concesionarul deoarece are taxe asociate!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM concesionari WHERE id_cesionar = ?");
            $stmt->execute([$id_cesionar]);
            $_SESSION['success_message'] = 'Concesionarul a fost șters cu succes!';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea concesionarului: ' . $e->getMessage();
    }

    redirect('concesionari.php');
}

// Obține lista concesionarilor
$stmt = $pdo->query("SELECT * FROM concesionari ORDER BY numele, prenumele");
$concesionari = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-users me-2"></i>
                Gestionare Concesionari
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#concesionarModal">
                <i class="fas fa-plus me-2"></i>Adaugă Concesionar
            </button>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Concesionarilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume</th>
                                <th>Prenume</th>
                                <th>CNP</th>
                                <th>Localitate</th>
                                <th>Telefon</th>
                                <th>Email</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concesionari as $concesionar): ?>
                            <tr>
                                <td><?php echo $concesionar['id_cesionar']; ?></td>
                                <td><?php echo htmlspecialchars($concesionar['numele'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($concesionar['prenumele'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($concesionar['cnp'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($concesionar['localitate'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($concesionar['telefon'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($concesionar['email'] ?? ''); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editConcesionar(<?php echo htmlspecialchars(json_encode($concesionar)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $concesionar['id_cesionar']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți acest concesionar?')">
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

<!-- Modal pentru adăugare/editare concesionar -->
<div class="modal fade" id="concesionarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="concesionarModalTitle">
                    <i class="fas fa-users me-2"></i>
                    Adaugă Concesionar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="concesionarForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_cesionar" id="concesionarId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="numele" class="form-label">Nume *</label>
                            <input type="text" class="form-control" name="numele" id="numele" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="prenumele" class="form-label">Prenume *</label>
                            <input type="text" class="form-control" name="prenumele" id="prenumele" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți prenumele.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cnp" class="form-label">CNP *</label>
                            <input type="text" class="form-control" name="cnp" id="cnp" maxlength="13" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți CNP-ul (13 cifre).</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="telefon" class="form-label">Telefon *</label>
                            <input type="tel" class="form-control" name="telefon" id="telefon" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numărul de telefon.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="localitate" class="form-label">Localitate *</label>
                            <input type="text" class="form-control" name="localitate" id="localitate" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți localitatea.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adresa" class="form-label">Adresă</label>
                        <textarea class="form-control" name="adresa" id="adresa" rows="2"></textarea>
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
function editConcesionar(concesionar) {
    document.getElementById('concesionarModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Concesionar';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('concesionarId').value = concesionar.id_cesionar;
    document.getElementById('numele').value = concesionar.numele || '';
    document.getElementById('prenumele').value = concesionar.prenumele || '';
    document.getElementById('cnp').value = concesionar.cnp || '';
    document.getElementById('telefon').value = concesionar.telefon || '';
    document.getElementById('email').value = concesionar.email || '';
    document.getElementById('localitate').value = concesionar.localitate || '';
    document.getElementById('adresa').value = concesionar.adresa || '';

    new bootstrap.Modal(document.getElementById('concesionarModal')).show();
}

// Reset form când se închide modalul
document.getElementById('concesionarModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('concesionarForm').reset();
    document.getElementById('concesionarForm').classList.remove('was-validated');
    document.getElementById('concesionarModalTitle').innerHTML = '<i class="fas fa-users me-2"></i>Adaugă Concesionar';
    document.getElementById('formAction').value = 'add';
    document.getElementById('concesionarId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
