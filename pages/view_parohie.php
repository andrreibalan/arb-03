<?php
$page_title = 'Vizualizare Parohie';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică dacă a fost specificat ID-ul parohiei
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID parohie nespecificat!");
}

$id_parohie = intval($_GET['id']);

// Verifică permisiunile
if (!hasRole('admin') && !hasRole('protopop') && !hasRole('paroh')) {
    die("Acces interzis! Nu aveți permisiunile necesare pentru a accesa această pagină.");
}

// Dacă este paroh, verifică dacă are acces la această parohie
if (hasRole('paroh') && $_SESSION['user_parohie'] != $id_parohie) {
    die("Acces interzis! Nu aveți permisiunea să vizualizați această parohie.");
}

// Dacă este protopop, verifică dacă parohia aparține protopopiatului său
if (hasRole('protopop')) {
    $stmt = $pdo->prepare("SELECT id_proterie FROM parohii WHERE id_parohie = ?");
    $stmt->execute([$id_parohie]);
    $parohie_check = $stmt->fetch();
    if (!$parohie_check || $parohie_check['id_proterie'] != $_SESSION['user_proterie']) {
        die("Acces interzis! Nu aveți permisiunea să vizualizați această parohie.");
    }
}

// Obține detaliile parohiei
$stmt = $pdo->prepare("
    SELECT p.*, pr.denumire as nume_proterie, tp.nume_tip_parohie
    FROM parohii p
    LEFT JOIN protopopiate pr ON p.id_proterie = pr.id_proterie
    LEFT JOIN tipuri_parohie tp ON p.id_tip_parohie = tp.id_tip_parohie
    WHERE p.id_parohie = ?
");
$stmt->execute([$id_parohie]);
$parohie = $stmt->fetch();

if (!$parohie) {
    die("Parohia nu a fost găsită!");
}

// Obține bisericile parohiei
$stmt = $pdo->prepare("
    SELECT b.*, h.Hram
    FROM biserici b
    LEFT JOIN hramuri h ON b.id_hram = h.id_hram
    WHERE b.id_parohie = ?
    ORDER BY b.localitate
");
$stmt->execute([$id_parohie]);
$biserici = $stmt->fetchAll();

// Obține clădirile parohiei
$stmt = $pdo->prepare("SELECT * FROM cladiri WHERE id_parohie = ? ORDER BY nume_cladire");
$stmt->execute([$id_parohie]);
$cladiri = $stmt->fetchAll();

// Obține personalul parohiei
$stmt = $pdo->prepare("
    SELECT pe.*, f.nume_functie, sc.nume_stare_civila
    FROM personal pe
    LEFT JOIN functii f ON pe.id_functie = f.id_functie
    LEFT JOIN stari_civile sc ON pe.id_stare_civila = sc.id_stare_civila
    WHERE pe.id_parohie = ?
    ORDER BY pe.numele, pe.prenumele
");
$stmt->execute([$id_parohie]);
$personal = $stmt->fetchAll();

// Obține cimitirele parohiei
$stmt = $pdo->prepare("SELECT * FROM cimitir WHERE id_parohie = ? ORDER BY nume_cimitir");
$stmt->execute([$id_parohie]);
$cimitire = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
         
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-church me-2"></i><?php echo htmlspecialchars($parohie['nume_parohie']); ?>
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="parohii.php">Parohii</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($parohie['nume_parohie']); ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="parohii.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Înapoi la Parohii
                    </a>
                    <?php if (hasRole('admin') || hasRole('protopop')): ?>
                        <a href="edit_parohie.php?id=<?php echo $id_parohie; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editează Parohia
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informații Generale Parohie -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informații Generale
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="30%">Nume Parohie:</th>
                                    <td><?php echo htmlspecialchars($parohie['nume_parohie']); ?></td>
                                </tr>
                                <tr>
                                    <th>CUI:</th>
                                    <td><?php echo htmlspecialchars($parohie['cui']); ?></td>
                                </tr>
                                <tr>
                                    <th>Protopopiat:</th>
                                    <td><?php echo htmlspecialchars($parohie['nume_proterie']); ?></td>
                                </tr>
                                <tr>
                                    <th>Tip Parohie:</th>
                                    <td><?php echo htmlspecialchars($parohie['nume_tip_parohie'] ?? 'Nespecificat'); ?></td>
                                </tr>
                                <tr>
                                    <th>Anul Înființării:</th>
                                    <td><?php echo $parohie['anul_infiintarii'] ?? 'Nespecificat'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="30%">Adresa:</th>
                                    <td><?php echo htmlspecialchars($parohie['adresa'] ?? 'Nespecificată'); ?></td>
                                </tr>
                                <tr>
                                    <th>Localitate:</th>
                                    <td><?php echo htmlspecialchars($parohie['localitate'] ?? 'Nespecificată'); ?></td>
                                </tr>
                                <tr>
                                    <th>Județ:</th>
                                    <td><?php echo htmlspecialchars($parohie['judet'] ?? 'Nespecificat'); ?></td>
                                </tr>
                                <tr>
                                    <th>Telefon:</th>
                                    <td><?php echo htmlspecialchars($parohie['telefon'] ?? 'Nespecificat'); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($parohie['email'] ?? 'Nespecificat'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($parohie['observatii'])): ?>
                        <div class="mt-3">
                            <strong>Observații:</strong><br>
                            <?php echo nl2br(htmlspecialchars($parohie['observatii'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Biserici -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-church me-2"></i>Biserici (<?php echo count($biserici); ?>)
                    </h5>
                    <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                        <a href="biserici.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adaugă Biserică
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($biserici)): ?>
                        <p class="text-muted">Nu există biserici înregistrate pentru această parohie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Hram</th>
                                        <th>Localitate</th>
                                        <th>Județ</th>
                                        <th>Anul Construcției</th>
                                        <th>Observații</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($biserici as $biserica): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($biserica['Hram'] ?? ''); ?> </strong> </td>
                                            <td><?php echo htmlspecialchars($biserica['localitate'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($biserica['judetul'] ?? ''); ?></td>
                                            <td><?php echo $biserica['anul_constructie'] ?? ''; ?></td>
                                            <td><?php echo htmlspecialchars(substr($biserica['obs_biserica'] ?? '', 0, 50)); ?><?php if (strlen($biserica['obs_biserica'] ?? '') > 50) echo '...'; ?></td>
                                            <td>
                                                <a href="view_biserica.php?id=<?php echo $biserica['id_biserica']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-4">
                <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                    <a href="biserici.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Adaugă Biserică
                    </a>
                <?php endif; ?>
            </div>

            <!-- Clădiri -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>Clădiri (<?php echo count($cladiri); ?>)
                    </h5>
                    <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                        <a href="cladiri.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adaugă Clădire
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($cladiri)): ?>
                        <p class="text-muted">Nu există clădiri înregistrate pentru această parohie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nume Clădire</th>
                                        <th>Anul Construcției</th>
                                        <th>Stare</th>
                                        <th>Observații</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cladiri as $cladire): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cladire['nume_cladire']); ?> </strong> </td>
                                            <td><?php echo $cladire['anul_constructie'] ?? ''; ?></td>
                                            <td><?php echo htmlspecialchars($cladire['stare'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars(substr($cladire['obs_cladire'] ?? '', 0, 50)); ?><?php if (strlen($cladire['obs_cladire'] ?? '') > 50) echo '...'; ?></td>
                                            <td>
                                                <a href="view_cladire.php?id=<?php echo $cladire['id_cladire']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-4">
                <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                    <a href="cladiri.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Adaugă Clădire
                    </a>
                <?php endif; ?>
            </div>

            <!-- Personal -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Personal (<?php echo count($personal); ?>)
                    </h5>
                    <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                        <a href="personal.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adaugă Angajat
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($personal)): ?>
                        <p class="text-muted">Nu există personal înregistrat pentru această parohie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nume Complet</th>
                                        <th>Funcția</th>
                                        <th>Data Angajării</th>
                                        <th>Starea Civilă</th>
                                        <th>Contact</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($personal as $angajat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($angajat['numele'] . ' ' . $angajat['prenumele']); ?> </strong> </td>
                                            <td><span class="badge bg-danger"><?php echo htmlspecialchars($angajat['nume_functie'] ?? 'Neasignat'); ?> </span> </td>
                                            <td><?php echo $angajat['data_angajarii'] ? date('d.m.Y', strtotime($angajat['data_angajarii'])) : ''; ?></td>
                                            <td><?php echo htmlspecialchars($angajat['nume_stare_civila'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($angajat['localitate'] ?? ''); ?></td>
                                            <td>
                                                <a href="view_personal.php?id=<?php echo $angajat['id_pers']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-4">
                <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                    <a href="personal.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Adaugă Angajat
                    </a>
                <?php endif; ?>
            </div>

            <!-- Cimitire -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-cross me-2"></i>Cimitire (<?php echo count($cimitire); ?>)
                    </h5>
                    <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                        <a href="cimitire.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>Adaugă Cimitir
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($cimitire)): ?>
                        <p class="text-muted">Nu există cimitire înregistrate pentru această parohie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nume Cimitir</th>
                                        <th>Adresa</th>
                                        <th>Anul Înființării</th>
                                        <th>Observații</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cimitire as $cimitir): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cimitir['nume_cimitir']); ?></td>
                                            <td><?php echo htmlspecialchars($cimitir['adresa'] ?? ''); ?></td>
                                            <td><?php echo $cimitir['anul_infintarii'] ?? ''; ?></td>
                                            <td><?php echo htmlspecialchars(substr($cimitir['Observatii'] ?? '', 0, 50)); ?><?php if (strlen($cimitir['Observatii'] ?? '') > 50) echo '...'; ?></td>
                                            <td>
                                                <a href="view_cimitir.php?id=<?php echo $cimitir['id_cimitir']; ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-4">
                <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                    <a href="cimitire.php?filter_parohie=<?php echo $id_parohie; ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Adaugă Cimitir
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
