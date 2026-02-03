<?php
$page_title = 'Gestionare Funcții';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
requireRole('admin', 'protopop', 'paroh');

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id_functie = $_POST['id_functie'] ?? null;
        $nume_functie = sanitize($_POST['nume_functie']);
        $descriere = sanitize($_POST['descriere']);

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO functii (nume_functie, descriere) VALUES (?, ?)");
                $stmt->execute([$nume_functie, $descriere]);
                $_SESSION['success_message'] = 'Funcția a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("UPDATE functii SET nume_functie = ?, descriere = ? WHERE id_functie = ?");
                $stmt->execute([$nume_functie, $descriere, $id_functie]);
                $_SESSION['success_message'] = 'Funcția a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }

        redirect('functii.php');
    }
}

// Ștergere funcție
if (isset($_GET['delete'])) {
    $id_functie = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM functii WHERE id_functie = ?");
        $stmt->execute([$id_functie]);
        $_SESSION['success_message'] = 'Funcția a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea funcției: ' . $e->getMessage();
    }

    redirect('functii.php');
}

// Obține lista funcțiilor
$stmt = $pdo->query("SELECT * FROM functii ORDER BY nume_functie");
$functii = $stmt->fetchAll();
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-briefcase me-2"></i>
                Gestionare Funcții
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#functieModal">
                <i class="fas fa-plus me-2"></i>Adaugă Funcție
            </button>
        </div>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Funcțiilor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume Funcție</th>
                                <th>Descriere</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($functii as $functie): ?>
                            <tr>
                                <td><?php echo $functie['id_functie']; ?></td>
                                <td><?php echo htmlspecialchars($functie['nume_functie']); ?></td>
                                <td><?php echo htmlspecialchars($functie['descriere'] ?? ''); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editFunctie(<?php echo htmlspecialchars(json_encode($functie)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $functie['id_functie']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această funcție?')">
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

<!-- Modal pentru adăugare/editare funcție -->
<div class="modal fade" id="functieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="functieModalTitle">
                    <i class="fas fa-briefcase me-2"></i>
                    Adaugă Funcție
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="functieForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_functie" id="functieId">

                    <div class="mb-3">
                        <label for="nume_functie" class="form-label">Nume Funcție *</label>
                        <input type="text" class="form-control" name="nume_functie" id="nume_functie" required>
                        <div class="invalid-feedback">Vă rugăm să introduceți numele funcției.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descriere" class="form-label">Descriere</label>
                        <textarea class="form-control" name="descriere" id="descriere" rows="3"></textarea>
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
function editFunctie(functie) {
    document.getElementById('functieModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Funcție';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('functieId').value = functie.id_functie;
    document.getElementById('nume_functie').value = functie.nume_functie;
    document.getElementById('descriere').value = functie.descriere || '';

    new bootstrap.Modal(document.getElementById('functieModal')).show();
}

// Reset form când se închide modalul
document.getElementById('functieModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('functieForm').reset();
    document.getElementById('functieForm').classList.remove('was-validated');
    document.getElementById('functieModalTitle').innerHTML = '<i class="fas fa-briefcase me-2"></i>Adaugă Funcție';
    document.getElementById('formAction').value = 'add';
    document.getElementById('functieId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
