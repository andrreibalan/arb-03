<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Sistem Management Parohial</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .main-content {
            min-height: calc(100vh - 56px);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
        }
        .table-actions {
            white-space: nowrap;
        }
        .btn-sm {
            margin: 0 2px;
        }
        .role-badge {
            font-size: 0.8em;
        }
        .user-info {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container-fluid">
            <a class="navbar-brand" href="../pages/dashboard.php">
                <i class="fas fa-church me-2"></i>
                CMS ARB                
            </a>
            
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <?php if (hasRole('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/protopopiate.php">
                            <i class="fas fa-crown me-1"></i>Protopopiate
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/users.php">
                            <i class="fas fa-users me-1"></i>Utilizatori
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasRole('admin') || hasRole('protopop')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/parohii.php">
                            <i class="fas fa-church me-1"></i>Parohii
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('admin') || hasRole('protopop') || hasRole('paroh')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Personal
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../pages/personal.php">
                                <i class="fas fa-user me-1"></i>Angajați
                            </a></li>
                            <li><a class="dropdown-item" href="../pages/functii.php">
                                <i class="fas fa-briefcase me-1"></i>Funcții
                            </a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-building me-1"></i>Infrastructură
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../pages/biserici.php">
                                <i class="fas fa-church me-1"></i>Biserici
                            </a></li>
                            <li><a class="dropdown-item" href="../pages/cladiri.php">
                                <i class="fas fa-building me-1"></i>Clădiri
                            </a></li>
                            <li><a class="dropdown-item" href="../pages/cimitire.php">
                                <i class="fas fa-cross me-1"></i>Cimitire
                            </a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/inventar.php">
                            <i class="fas fa-boxes me-1"></i>Inventar
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasRole('cimitir') || hasRole('admin') || hasRole('paroh')): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cross me-1"></i>Cimitir
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../pages/locuri_cimitir.php">
                                <i class="fas fa-map-marker-alt me-1"></i>Locuri
                            </a></li>
                            <li><a class="dropdown-item" href="../pages/concesionari.php">
                                <i class="fas fa-users me-1"></i>Concesionari
                            </a></li>
                            <li><a class="dropdown-item" href="../pages/taxe_cimitir.php">
                                <i class="fas fa-money-bill me-1"></i>Taxe
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
               

                
                <ul class="navbar-nav">
                    <!-- Live Clock afiseaza ceasul din functia live-clock  -->
                    <div class="text-white me-3 mt-2">
                        <i class="fas fa-calendar-clock me-1"></i>               
                        <i class="fas fa-clock me-1"></i>
                        <span id="live-clock"></span> 
                    </div>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo $current_user['Nume'] ?? 'Utilizator'; ?>
                            <span class="badge bg-light text-dark role-badge ms-1">
                                <?php echo $current_user['nume_rol'] ?? ''; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header user-info">
                                <strong><?php echo $current_user['Nume'] ?? 'Utilizator'; ?></strong><br>
                                <small class="text-muted"><?php echo $current_user['nume_rol'] ?? ''; ?></small>
                                <?php if (!empty($current_user['nume_parohie'])): ?>
                                    <br><small class="text-muted"><?php echo $current_user['nume_parohie']; ?></small>
                                <?php endif; ?>
                                <?php if (!empty($current_user['nume_proterie'])): ?>
                                    <br><small class="text-muted"><?php echo $current_user['nume_proterie']; ?></small>
                                <?php endif; ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../pages/profil.php">
                                <i class="fas fa-user me-1"></i>Profil
                            </a></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Deconectare
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <div class="main-content p-4">