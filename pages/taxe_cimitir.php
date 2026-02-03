<?php
$page_title = 'Gestionare Taxe Cimitir';
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
        $id_taxa = $_POST['id_taxa'] ?? null;
        $id_cesionar = intval($_POST['id_cesionar']);
        $nume_taxa = sanitize($_POST['nume_taxa']);
        $suma = floatval($_POST['suma']);
        $an = intval($_POST['an']);
        $data_achitarii = $_POST['data_achitarii'] ?: null;
        $observatii = sanitize($_POST['observatii'] ?? '');

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO taxe_cmt (id_cesionar, nume_taxa, suma, anul, data_achitarii, observatii)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_cesionar, $nume_taxa, $suma, $an, $data_achitarii, $observatii]);
                $_SESSION['success_message'] = 'Taxa a fost adăugată cu succes!';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE taxe_cmt SET id_cesionar = ?, nume_taxa = ?, suma = ?, anul = ?, data_achitarii = ?, observatii = ?
                    WHERE id_taxa = ?
                ");
                $stmt->execute([$id_cesionar, $nume_taxa, $suma, $an, $data_achitarii, $observatii, $id_taxa]);
                $_SESSION['success_message'] = 'Taxa a fost actualizată cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }

        redirect('taxe_cimitir.php' . ($concesionar_id ? '?concesionar=' . $concesionar_id : ''));
    }
}

// Ștergere taxă
if (isset($_GET['delete'])) {
    $id_taxa = intval($_GET['delete']);

    try {
        $stmt = $pdo->prepare("DELETE FROM taxe_cmt WHERE id_taxa = ?");
        $stmt->execute([$id_taxa]);
        $_SESSION['success_message'] = 'Taxa a fost ștearsă cu succes!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la ștergerea taxei: ' . $e->getMessage();
    }

    redirect('taxe_cimitir.php' . ($concesionar_id ? '?concesionar=' . $concesionar_id : ''));
}

// Construiește query-ul pentru taxe în funcție de concesionar
$where_clause = '';
$params = [];

if ($concesionar_id) {
    $where_clause = 'WHERE t.id_cesionar = ?';
    $params[] = $concesionar_id;
}

$stmt = $pdo->prepare("SELECT t.*, c.numele, c.prenumele, c.localitate FROM taxe_cmt t LEFT JOIN concesionari c ON t.id_cesionar = c.id_cesionar $where_clause ORDER BY t.anul DESC, c.numele, c.prenumele");
$stmt->execute($params);
$taxe = $stmt->fetchAll();

// Obține lista concesionarilor pentru dropdown
$concesionari = $pdo->query("SELECT id_cesionar, CONCAT(numele, ' ', prenumele) as nume_complet FROM concesionari ORDER BY numele, prenumele")->fetchAll();

// Calculează statistici pentru anul curent
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) as numar_taxe, SUM(suma) as suma_totala FROM taxe_cmt WHERE anul = ?");
$stmt->execute([$current_year]);
$stats = $stmt->fetch();

// Calculează statistici lunare pentru anul curent
$stmt_monthly = $pdo->prepare("
    SELECT
        MONTH(data_achitarii) as luna,
        COUNT(*) as numar_taxe,
        SUM(suma) as suma_totala
    FROM taxe_cmt
    WHERE anul = ? AND data_achitarii IS NOT NULL
    GROUP BY MONTH(data_achitarii)
    ORDER BY MONTH(data_achitarii)
");
$stmt_monthly->execute([$current_year]);
$monthly_stats = $stmt_monthly->fetchAll();

// Numele lunilor în română
$luni = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-money-bill me-2"></i>
                Gestionare Taxe Cimitir
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taxaModal">
                <i class="fas fa-plus me-2"></i>Adaugă Taxă
            </button>
        </div>

        <!-- Statistici -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Număr Taxe (<?php echo $current_year; ?>)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $stats['numar_taxe'] ?? 0; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Suma Totală (<?php echo $current_year; ?>)
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($stats['suma_totala'] ?? 0, 2); ?> RON
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistici lunare -->
        <?php if (!empty($monthly_stats)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-chart-bar me-2"></i>
                            Totaluri Lunare (<?php echo $current_year; ?>)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Luna</th>
                                        <th>Număr Taxe</th>
                                        <th>Suma Totală</th>
                                        <th>Media per Taxă</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_stats as $month_stat): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $luni[$month_stat['luna']]; ?></strong>
                                        </td>
                                        <td><?php echo $month_stat['numar_taxe']; ?></td>
                                        <td>
                                            <span class="text-success font-weight-bold">
                                                <?php echo number_format($month_stat['suma_totala'], 2); ?> RON
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $media = $month_stat['numar_taxe'] > 0 ? $month_stat['suma_totala'] / $month_stat['numar_taxe'] : 0;
                                            echo number_format($media, 2) . ' RON';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-info">
                                    <tr>
                                        <th>TOTAL AN <?php echo $current_year; ?></th>
                                        <th><?php echo $stats['numar_taxe'] ?? 0; ?></th>
                                        <th>
                                            <strong><?php echo number_format($stats['suma_totala'] ?? 0, 2); ?> RON</strong>
                                        </th>
                                        <th>
                                            <?php
                                            $media_total = ($stats['numar_taxe'] ?? 0) > 0 ? ($stats['suma_totala'] ?? 0) / ($stats['numar_taxe'] ?? 0) : 0;
                                            echo '<strong>' . number_format($media_total, 2) . ' RON</strong>';
                                            ?>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-list me-2"></i>
                    Lista Taxelor
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered data-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Concesionar</th>
                                <th>Nume Taxă</th>
                                <th>Suma</th>
                                <th>Anul</th>
                                <th>Data Achitării</th>
                                <th>Observații</th>
                                <th class="table-actions">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($taxe as $taxa): ?>
                            <tr>
                                <td><?php echo $taxa['id_taxa']; ?></td>
                                <td><?php echo htmlspecialchars(($taxa['numele'] ?? '') . ' ' . ($taxa['prenumele'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($taxa['nume_taxa'] ?? ''); ?></td>
                                <td><?php echo number_format($taxa['suma'] ?? 0, 2); ?> RON</td>
                                <td><?php echo $taxa['anul'] ?? ''; ?></td>
                                <td><?php echo $taxa['data_achitarii'] ?? ''; ?></td>
                                <td><?php echo htmlspecialchars($taxa['observatii'] ?? ''); ?></td>
                                <td class="table-actions">
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="editTaxa(<?php echo htmlspecialchars(json_encode($taxa)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $taxa['id_taxa']; ?><?php echo $concesionar_id ? '&concesionar=' . $concesionar_id : ''; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete(this.href, 'Sunteți sigur că doriți să ștergeți această taxă?')">
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

<!-- Modal pentru adăugare/editare taxă -->
<div class="modal fade" id="taxaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taxaModalTitle">
                    <i class="fas fa-money-bill me-2"></i>
                    Adaugă Taxă
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="taxaForm" method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_taxa" id="taxaId">

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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nume_taxa" class="form-label">Nume Taxă *</label>
                            <input type="text" class="form-control" name="nume_taxa" id="nume_taxa" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți numele taxei.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="suma" class="form-label">Suma (RON) *</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="suma" id="suma" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți suma.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="an" class="form-label">An *</label>
                            <input type="number" min="2000" max="<?php echo date('Y') + 1; ?>" class="form-control" name="an" id="an" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți anul.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="data_achitarii" class="form-label">Data Achitării</label>
                            <input type="date" class="form-control" name="data_achitarii" id="data_achitarii">
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
function editTaxa(taxa) {
    document.getElementById('taxaModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Taxă';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('taxaId').value = taxa.id_taxa;
    document.getElementById('id_cesionar').value = taxa.id_cesionar || '';
    document.getElementById('nume_taxa').value = taxa.nume_taxa || '';
    document.getElementById('suma').value = taxa.suma || '';
    document.getElementById('an').value = taxa.anul || '';
    document.getElementById('data_achitarii').value = taxa.data_achitarii || '';
    document.getElementById('observatii').value = taxa.observatii || '';

    new bootstrap.Modal(document.getElementById('taxaModal')).show();
}

// Reset form când se închide modalul
document.getElementById('taxaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('taxaForm').reset();
    document.getElementById('taxaForm').classList.remove('was-validated');
    document.getElementById('taxaModalTitle').innerHTML = '<i class="fas fa-money-bill me-2"></i>Adaugă Taxă';
    document.getElementById('formAction').value = 'add';
    document.getElementById('taxaId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
