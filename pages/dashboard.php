<?php
$page_title = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/auth.php';

requireAuth();

// Obține statistici pentru dashboard
$stats = [];

try {
    if (hasRole('admin')) {
        // Statistici pentru admin - toate datele
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM protopopiate WHERE status = 'activ'");
        $stats['protopopiate'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM parohii");
        $stats['parohii'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM biserici");
        $stats['biserici'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM personal");
        $stats['personal'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inventar");
        $stats['inventar'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cimitir");
        $stats['cimitire'] = $stmt->fetchColumn();
        
    } elseif (hasRole('protopop')) {
        // Statistici pentru protopop - doar din protopopiatul său
        $proterie_id = $_SESSION['user_proterie'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM parohii WHERE id_proterie = ?");
        $stmt->execute([$proterie_id]);
        $stats['parohii'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM biserici b 
            JOIN parohii p ON b.id_parohie = p.id_parohie 
            WHERE p.id_proterie = ?
        ");
        $stmt->execute([$proterie_id]);
        $stats['biserici'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM personal pe 
            JOIN parohii p ON pe.id_parohie = p.id_parohie 
            WHERE p.id_proterie = ?
        ");
        $stmt->execute([$proterie_id]);
        $stats['personal'] = $stmt->fetchColumn();
        
    } elseif (hasRole('paroh')) {
        // Statistici pentru paroh - doar din parohia sa
        $parohie_id = $_SESSION['user_parohie'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM biserici WHERE id_parohie = ?");
        $stmt->execute([$parohie_id]);
        $stats['biserici'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM personal WHERE id_parohie = ?");
        $stmt->execute([$parohie_id]);
        $stats['personal'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventar WHERE id_parohie = ?");
        $stmt->execute([$parohie_id]);
        $stats['inventar'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cimitir WHERE id_parohie = ?");
        $stmt->execute([$parohie_id]);
        $stats['cimitire'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cladiri WHERE id_parohie = ?");
        $stmt->execute([$parohie_id]);
        $stats['cladiri'] = $stmt->fetchColumn();
        
    } elseif (hasRole('cimitir')) {
        // Statistici pentru administrator cimitir
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM loc_cimitir");
        $stats['locuri_cimitir'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM concesionari");
        $stats['concesionari'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM taxe_cmt WHERE YEAR(data_achitarii) = YEAR(CURDATE())");
        $stats['taxe_anul_curent'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT SUM(suma) as total FROM taxe_cmt WHERE YEAR(data_achitarii) = YEAR(CURDATE())");
        $stats['suma_taxe_anul_curent'] = $stmt->fetchColumn() ?? 0;
    }
    
} catch (PDOException $e) {
    $error_message = "Eroare la încărcarea statisticilor: " . $e->getMessage();
}
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </h1>

        </div>
        
        <!-- Mesaj de bun venit -->
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Bun venit, <?php echo $current_user['Nume']; ?>!</strong>
            Sunteți autentificat ca <strong><?php echo $current_user['nume_rol']; ?></strong>
            <?php if (!empty($current_user['nume_parohie'])): ?>
                la <strong><?php echo $current_user['nume_parohie']; ?></strong>
            <?php endif; ?>
            <?php if (!empty($current_user['nume_proterie'])): ?>
                din <strong><?php echo $current_user['nume_proterie']; ?></strong>
            <?php endif; ?>
        </div>
        
        <!-- Statistici -->
        <div class="row">
            <?php if (hasRole('admin')): ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Protopopiate
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['protopopiate'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-crown fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole('admin') || hasRole('protopop')): ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Parohii
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['parohii'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-church fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Biserici
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['biserici'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-church fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Personal
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['personal'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole('admin') || hasRole('paroh')): ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-secondary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                        Inventar
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['inventar'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-dark shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                        Cimitire
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['cimitire'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cross fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole('paroh')): ?>
                <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Clădiri
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['cladiri'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-building fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (hasRole('cimitir')): ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Locuri Cimitir
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['locuri_cimitir'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Concesionari
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['concesionari'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Taxe <?php echo date('Y'); ?>
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['taxe_anul_curent'] ?? 0; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Încasări <?php echo date('Y'); ?>
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['suma_taxe_anul_curent'] ?? 0, 2); ?> RON
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-coins fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Acțiuni rapide -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-bolt me-2"></i>
                            Acțiuni Rapide
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (hasRole('admin') || hasRole('protopop')): ?>
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/parohii.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-church fa-2x d-block mb-2"></i>
                                        Gestionare Parohii
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/personal.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-users fa-2x d-block mb-2"></i>
                                        Personal
                                    </a>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/inventar.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-boxes fa-2x d-block mb-2"></i>
                                        Inventar
                                    </a>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/biserici.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-church fa-2x d-block mb-2"></i>
                                        Biserici
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (hasRole('cimitir') || hasRole('admin') || hasRole('paroh')): ?>
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/concesionari.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-users fa-2x d-block mb-2"></i>
                                        Concesionari
                                    </a>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <a href="../pages/taxe_cimitir.php" class="btn btn-outline-dark w-100">
                                        <i class="fas fa-money-bill fa-2x d-block mb-2"></i>
                                        Taxe Cimitir
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.border-left-secondary {
    border-left: 4px solid #858796 !important;
}

.border-left-dark {
    border-left: 4px solid #5a5c69 !important;
}

.border-left-danger {
    border-left: 4px solid #e74a3b !important;
}

.text-xs {
    font-size: 0.7rem;
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover,
.btn-outline-warning:hover,
.btn-outline-secondary:hover,
.btn-outline-dark:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php require_once '../includes/footer.php'; ?>