i<?php
$page_title = 'Detalii Angajat';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile
if (!hasRole('admin') && !hasRole('protopop') && !hasRole('paroh')) {
    die("Acces interzis! Nu aveți permisiunile necesare pentru a accesa această pagină.");
}

// Verifică dacă a fost specificat un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID-ul angajatului nu a fost specificat!';
    redirect('personal.php');
}

$id_pers = intval($_GET['id']);

// Obține datele angajatului
try {
    $stmt = $pdo->prepare("
        SELECT pe.*, p.nume_parohie, f.nume_functie, sc.nume_stare_civila
        FROM personal pe
        LEFT JOIN parohii p ON pe.id_parohie = p.id_parohie
        LEFT JOIN functii f ON pe.id_functie = f.id_functie
        LEFT JOIN stari_civile sc ON pe.id_stare_civila = sc.id_stare_civila
        WHERE pe.id_pers = ?
    ");
    $stmt->execute([$id_pers]);
    $angajat = $stmt->fetch();

    if (!$angajat) {
        $_SESSION['error_message'] = 'Angajatul nu a fost găsit!';
        redirect('personal.php');
    }

    // Verifică permisiunile pentru această parohie
    if (!hasAccessToParohie($angajat['id_parohie'])) {
        $_SESSION['error_message'] = 'Nu aveți permisiunea să vizualizați acest angajat!';
        redirect('personal.php');
    }

    // Obține studiile angajatului
    $stmt = $pdo->prepare("
        SELECT * FROM pers_studii
        WHERE id_pers = ?
        ORDER BY anul_absolvirii DESC
    ");
    $stmt->execute([$id_pers]);
    $studii = $stmt->fetchAll();

    // Obține activitățile angajatului
    $stmt = $pdo->prepare("
        SELECT * FROM activitati
        WHERE id_persoana = ?
        ORDER BY data_activ DESC
    ");
    $stmt->execute([$id_pers]);
    $activitati = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Eroare la încărcarea datelor: ' . $e->getMessage();
    redirect('personal.php');
}

// Procesare formular pentru adăugare studii
if (isset($_POST['action']) && $_POST['action'] === 'add_studii') {
    try {
        $tip_studiu = trim($_POST['tip_studiu']);
        $institutia = trim($_POST['institutia']);
        $anul_absolvirii = intval($_POST['anul_absolvirii']);
        $media = !empty($_POST['media']) ? floatval($_POST['media']) : null;

        if (empty($tip_studiu) || empty($institutia) || empty($anul_absolvirii)) {
            $_SESSION['error_message'] = 'Vă rugăm să completați toate câmpurile obligatorii!';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO pers_studii (id_pers, tip_studiu, Institutia, anul_absolvirii, media)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pers, $tip_studiu, $institutia, $anul_absolvirii, $media]);

            $_SESSION['success_message'] = 'Studiile au fost adăugate cu succes!';
            redirect('view_personal.php?id=' . $id_pers);
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la adăugarea studiilor: ' . $e->getMessage();
    }
}

// Procesare formular pentru editare studii
if (isset($_POST['action']) && $_POST['action'] === 'edit_studii') {
    try {
        $studii_id = intval($_POST['studii_id']);
        $tip_studiu = trim($_POST['tip_studiu']);
        $institutia = trim($_POST['institutia']);
        $anul_absolvirii = intval($_POST['anul_absolvirii']);
        $media = !empty($_POST['media']) ? floatval($_POST['media']) : null;

        if (empty($tip_studiu) || empty($institutia) || empty($anul_absolvirii)) {
            $_SESSION['error_message'] = 'Vă rugăm să completați toate câmpurile obligatorii!';
        } else {
            $stmt = $pdo->prepare("
                UPDATE pers_studii
                SET tip_studiu = ?, Institutia = ?, anul_absolvirii = ?, media = ?
                WHERE id = ? AND id_pers = ?
            ");
            $stmt->execute([$tip_studiu, $institutia, $anul_absolvirii, $media, $studii_id, $id_pers]);

            $_SESSION['success_message'] = 'Studiile au fost actualizate cu succes!';
            redirect('view_personal.php?id=' . $id_pers);
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la actualizarea studiilor: ' . $e->getMessage();
    }
}

// Procesare formular pentru editare activități
if (isset($_POST['action']) && $_POST['action'] === 'edit_activitate') {
    try {
        $activitate_id = intval($_POST['activitate_id']);
        $nume_activ = trim($_POST['nume_activ']);
        $data_activ = !empty($_POST['data_activ']) ? $_POST['data_activ'] : null;
        $descriere = trim($_POST['descriere']);
        $obs_activ = trim($_POST['obs_activ']);

        if (empty($nume_activ)) {
            $_SESSION['error_message'] = 'Vă rugăm să introduceți numele activității!';
        } else {
            $stmt = $pdo->prepare("
                UPDATE activitati
                SET nume_activ = ?, data_activ = ?, descriere = ?, obs_activ = ?
                WHERE id_activ = ? AND id_persoana = ?
            ");
            $stmt->execute([$nume_activ, $data_activ, $descriere, $obs_activ, $activitate_id, $id_pers]);

            $_SESSION['success_message'] = 'Activitatea a fost actualizată cu succes!';
            redirect('view_personal.php?id=' . $id_pers);
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la actualizarea activității: ' . $e->getMessage();
    }
}

// Procesare formular pentru adăugare activități
if (isset($_POST['action']) && $_POST['action'] === 'add_activitate') {
    try {
        $nume_activ = trim($_POST['nume_activ']);
        $data_activ = !empty($_POST['data_activ']) ? $_POST['data_activ'] : null;
        $descriere = trim($_POST['descriere']);
        $obs_activ = trim($_POST['obs_activ']);

        if (empty($nume_activ)) {
            $_SESSION['error_message'] = 'Vă rugăm să introduceți numele activității!';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO activitati (id_persoana, nume_activ, data_activ, descriere, obs_activ)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_pers, $nume_activ, $data_activ, $descriere, $obs_activ]);

            $_SESSION['success_message'] = 'Activitatea a fost adăugată cu succes!';
            redirect('view_personal.php?id=' . $id_pers);
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la adăugarea activității: ' . $e->getMessage();
    }
}

// Procesare formular pentru editare adresă
if (isset($_POST['action']) && $_POST['action'] === 'edit_address') {
    try {
        $adresa = trim($_POST['adresa']);
        $localitate = trim($_POST['localitate']);
        $judet = trim($_POST['judet']);

        $stmt = $pdo->prepare("
            UPDATE personal
            SET adresa = ?, localitate = ?, judet = ?
            WHERE id_pers = ?
        ");
        $stmt->execute([$adresa, $localitate, $judet, $id_pers]);

        $_SESSION['success_message'] = 'Adresa a fost actualizată cu succes!';
        redirect('view_personal.php?id=' . $id_pers);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la actualizarea adresei: ' . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user me-2"></i>Detalii Angajat
                </h1>
                <div>
                    <a href="personal.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Înapoi la Listă
                    </a>
                    <button type="button" class="btn btn-primary" onclick="editPersonal(<?php echo htmlspecialchars(json_encode($angajat)); ?>)">
                        <i class="fas fa-edit me-2"></i>Editează
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Informații personale -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>Informații Personale
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nume:</strong></td>
                                    <td><?php echo htmlspecialchars($angajat['numele'] . ' ' . $angajat['prenumele']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>CNP:</strong></td>
                                    <td><?php echo htmlspecialchars($angajat['cnp']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Data nașterii:</strong></td>
                                    <td>
                                        <?php if ($angajat['data_nasterii']): ?>
                                            <?php echo date('d.m.Y', strtotime($angajat['data_nasterii'])); ?>
                                            <small class="text-muted">(<?php echo floor((time() - strtotime($angajat['data_nasterii'])) / 31556926); ?> ani)</small>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificată</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Locul nașterii:</strong></td>
                                    <td>
                                        <?php if ($angajat['loc_nastere']): ?>
                                            <?php echo htmlspecialchars($angajat['loc_nastere'] . ', ' . $angajat['jud_nastere']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Stare civilă:</strong></td>
                                    <td>
                                        <?php if ($angajat['nume_stare_civila']): ?>
                                            <?php echo htmlspecialchars($angajat['nume_stare_civila']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificată</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Funcția:</strong></td>
                                    <td>
                                        <?php if ($angajat['nume_functie']): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($angajat['nume_functie']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificată</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Parohia:</strong></td>
                                    <td><?php echo htmlspecialchars($angajat['nume_parohie']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Data angajării:</strong></td>
                                    <td>
                                        <?php if ($angajat['data_angajarii']): ?>
                                            <?php echo date('d.m.Y', strtotime($angajat['data_angajarii'])); ?>
                                            <small class="text-muted">(<?php echo floor((time() - strtotime($angajat['data_angajarii'])) / 31556926); ?> ani vechime)</small>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificată</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Număr decizie:</strong></td>
                                    <td>
                                        <?php if ($angajat['nr_decizie']): ?>
                                            <?php echo htmlspecialchars($angajat['nr_decizie']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nu este specificat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Data încetării:</strong></td>
                                    <td>
                                        <?php if ($angajat['data_incetarii_job']): ?>
                                            <?php echo date('d.m.Y', strtotime($angajat['data_incetarii_job'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Activ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adresă și contact -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-address-book me-2"></i>Adresă și Contact
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Adresă:</h6>
                            <p>
                                <?php if ($angajat['adresa']): ?>
                                    <?php echo htmlspecialchars($angajat['adresa']); ?><br>
                                    <?php echo htmlspecialchars($angajat['localitate'] . ', ' . $angajat['judet']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Nu este specificată</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Observații:</h6>
                            <p>
                                <?php if ($angajat['Observatii']): ?>
                                    <?php echo nl2br(htmlspecialchars($angajat['Observatii'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Nu sunt observații</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Studii -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-graduation-cap me-2"></i>Studii și Formare
                    </h5>
                    <button type="button" class="btn btn-sm btn-success" onclick="showAddStudiiModal()">
                        <i class="fas fa-plus me-1"></i>Adaugă Studii
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($studii)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Tip Studiu</th>
                                        <th>Instituția</th>
                                        <th>An Absolvire</th>
                                        <th>Medie</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($studii as $studiu): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($studiu['tip_studiu']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($studiu['Institutia']); ?></td>
                                            <td><?php echo $studiu['anul_absolvirii']; ?></td>
                                            <td>
                                                <?php if ($studiu['media']): ?>
                                                    <?php echo number_format($studiu['media'], 2); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editStudii(<?php echo htmlspecialchars(json_encode($studiu)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nu sunt înregistrate studii pentru acest angajat.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Activități -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Activități și Evenimente
                    </h5>
                    <button type="button" class="btn btn-sm btn-success" onclick="showAddActivitateModal()">
                        <i class="fas fa-plus me-1"></i>Adaugă Activitate
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($activitati)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Nume Activitate</th>
                                        <th>Descriere</th>
                                        <th>Observații</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activitati as $activitate): ?>
                                        <tr>
                                            <td>
                                                <?php if ($activitate['data_activ']): ?>
                                                    <?php echo date('d.m.Y', strtotime($activitate['data_activ'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($activitate['nume_activ']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($activitate['descriere']): ?>
                                                    <?php echo htmlspecialchars(substr($activitate['descriere'], 0, 100)) . (strlen($activitate['descriere']) > 100 ? '...' : ''); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($activitate['obs_activ']): ?>
                                                    <?php echo htmlspecialchars(substr($activitate['obs_activ'], 0, 50)) . (strlen($activitate['obs_activ']) > 50 ? '...' : ''); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="editActivitate(<?php echo htmlspecialchars(json_encode($activitate)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nu sunt înregistrate activități pentru acest angajat.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pentru editare (reutilizat din personal.php) -->
<div class="modal fade" id="personalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="personalModalTitle">
                    <i class="fas fa-user me-2"></i>Editează Angajat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="personalForm" method="POST" action="personal.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="edit">
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
                                <?php
                                $stmt = $pdo->query("SELECT * FROM stari_civile ORDER BY nume_stare_civila");
                                $stari_civile = $stmt->fetchAll();
                                foreach ($stari_civile as $stare): ?>
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
                                <?php
                                $stmt = $pdo->query("SELECT * FROM functii ORDER BY nume_functie");
                                $functii = $stmt->fetchAll();
                                foreach ($functii as $functie): ?>
                                    <option value="<?php echo $functie['id_functie']; ?>">
                                        <?php echo htmlspecialchars($functie['nume_functie']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adresa" class="form-label">Adresă</label>
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
                                <?php
                                $parohii_accesibile = getAccessibleParohii();
                                foreach ($parohii_accesibile as $parohie): ?>
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

<!-- Modal pentru adăugare studii -->
<div class="modal fade" id="studiiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studiiModalTitle">
                    <i class="fas fa-graduation-cap me-2"></i>Adaugă Studii
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="studiiForm" method="POST" action="view_personal.php?id=<?php echo $id_pers; ?>" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="studiiFormAction" value="add_studii">
                    <input type="hidden" name="id_pers" value="<?php echo $id_pers; ?>">
                    <input type="hidden" name="studii_id" id="studiiId">

                    <div class="mb-3">
                        <label for="tip_studiu" class="form-label">Tip Studiu *</label>
                        <select class="form-select" name="tip_studiu" id="tip_studiu" required>
                            <option value="">Selectați tipul studiului</option>
                            <option value="Licență">Licență</option>
                            <option value="Master">Master</option>
                            <option value="Doctorat">Doctorat</option>
                            <option value="Studii Postuniversitare">Studii Postuniversitare</option>
                            <option value="Cursuri de Specializare">Cursuri de Specializare</option>
                            <option value="Liceu">Liceu</option>
                            <option value="Școală Profesională">Școală Profesională</option>
                        </select>
                        <div class="invalid-feedback">Vă rugăm să selectați tipul studiului.</div>
                    </div>

                    <div class="mb-3">
                        <label for="institutia" class="form-label">Instituția *</label>
                        <input type="text" class="form-control" name="institutia" id="institutia" required>
                        <div class="invalid-feedback">Vă rugăm să introduceți instituția.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="anul_absolvirii" class="form-label">An Absolvire *</label>
                            <input type="number" class="form-control" name="anul_absolvirii" id="anul_absolvirii" min="1900" max="<?php echo date('Y'); ?>" required>
                            <div class="invalid-feedback">Vă rugăm să introduceți anul absolvirii.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="media" class="form-label">Medie</label>
                            <input type="number" class="form-control" name="media" id="media" step="0.01" min="1" max="10">
                            <div class="form-small text-muted">Opțional (dacă nu se specifică, se va afișa "-")</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Adaugă Studii
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pentru adăugare activități -->
<div class="modal fade" id="activitateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activitateModalTitle">
                    <i class="fas fa-calendar-alt me-2"></i>Adaugă Activitate
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="activitateForm" method="POST" action="view_personal.php?id=<?php echo $id_pers; ?>" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" id="activitateFormAction" value="add_activitate">
                    <input type="hidden" name="id_persoana" value="<?php echo $id_pers; ?>">
                    <input type="hidden" name="activitate_id" id="activitateId">

                    <div class="mb-3">
                        <label for="nume_activ" class="form-label">Nume Activitate *</label>
                        <input type="text" class="form-control" name="nume_activ" id="nume_activ" required>
                        <div class="invalid-feedback">Vă rugăm să introduceți numele activității.</div>
                    </div>

                    <div class="mb-3">
                        <label for="data_activ" class="form-label">Data Activitate</label>
                        <input type="date" class="form-control" name="data_activ" id="data_activ">
                        <div class="form-small text-muted">Opțional (dacă nu se specifică, se va afișa "-")</div>
                    </div>

                    <div class="mb-3">
                        <label for="descriere" class="form-label">Descriere</label>
                        <textarea class="form-control" name="descriere" id="descriere" rows="3"></textarea>
                        <div class="form-small text-muted">Opțional</div>
                    </div>

                    <div class="mb-3">
                        <label for="obs_activ" class="form-label">Observații</label>
                        <textarea class="form-control" name="obs_activ" id="obs_activ" rows="2"></textarea>
                        <div class="form-small text-muted">Opțional</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Adaugă Activitate
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
    document.getElementById('personalModalTitle').innerHTML = '<i class="fas fa-user me-2"></i>Editează Angajat';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('personalId').value = '';
});

// Funcții pentru modalele de adăugare și editare
function showAddStudiiModal() {
    document.getElementById('studiiModalTitle').innerHTML = '<i class="fas fa-graduation-cap me-2"></i>Adaugă Studii';
    document.getElementById('studiiForm').reset();
    document.getElementById('studiiId').value = '';
    document.getElementById('studiiFormAction').value = 'add_studii';
    new bootstrap.Modal(document.getElementById('studiiModal')).show();
}

function showAddActivitateModal() {
    document.getElementById('activitateModalTitle').innerHTML = '<i class="fas fa-calendar-alt me-2"></i>Adaugă Activitate';
    document.getElementById('activitateForm').reset();
    document.getElementById('activitateId').value = '';
    document.getElementById('activitateFormAction').value = 'add_activitate';
    new bootstrap.Modal(document.getElementById('activitateModal')).show();
}

function editStudii(studii) {
    document.getElementById('studiiModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Studii';
    document.getElementById('studiiFormAction').value = 'edit_studii';
    document.getElementById('studiiId').value = studii.id;
    document.getElementById('tip_studiu').value = studii.tip_studiu;
    document.getElementById('institutia').value = studii.Institutia;
    document.getElementById('anul_absolvirii').value = studii.anul_absolvirii;
    document.getElementById('media').value = studii.media || '';
    new bootstrap.Modal(document.getElementById('studiiModal')).show();
}

function editActivitate(activitate) {
    document.getElementById('activitateModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Activitate';
    document.getElementById('activitateFormAction').value = 'edit_activitate';
    document.getElementById('activitateId').value = activitate.id_activ;
    document.getElementById('nume_activ').value = activitate.nume_activ;
    document.getElementById('data_activ').value = activitate.data_activ || '';
    document.getElementById('descriere').value = activitate.descriere || '';
    document.getElementById('obs_activ').value = activitate.obs_activ || '';
    new bootstrap.Modal(document.getElementById('activitateModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
