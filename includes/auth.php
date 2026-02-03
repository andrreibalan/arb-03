<?php
//session_start();
require_once __DIR__ . '/../config.php';

// Verifică dacă utilizatorul este autentificat
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Verifică rolul utilizatorului
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Verifică dacă utilizatorul are acces la o anumită parohie
function hasAccessToParohie($id_parohie) {
    if (hasRole('admin')) return true;
    
    if (hasRole('protopop')) {
        // Protopopul poate accesa parohiile din protopopiatul său
        return isset($_SESSION['user_proterie']) && 
               checkParohieInProtopopiat($id_parohie, $_SESSION['user_proterie']);
    }
    
    if (hasRole('paroh')) {
        // Parohul poate accesa doar parohia sa
        return isset($_SESSION['user_parohie']) && $_SESSION['user_parohie'] == $id_parohie;
    }
    
    return false;
}

// Verifică dacă parohia aparține protopopiatului
function checkParohieInProtopopiat($id_parohie, $id_proterie) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parohii WHERE id_parohie = ? AND id_proterie = ?");
    $stmt->execute([$id_parohie, $id_proterie]);
    return $stmt->fetchColumn() > 0;
}

// Obține lista parohiilor accesibile utilizatorului curent
function getAccessibleParohii() {
    global $pdo;
    
    if (hasRole('admin')) {
        $stmt = $pdo->query("SELECT * FROM parohii ORDER BY nume_parohie");
        return $stmt->fetchAll();
    }
    
    if (hasRole('protopop')) {
        $stmt = $pdo->prepare("SELECT * FROM parohii WHERE id_proterie = ? ORDER BY nume_parohie");
        $stmt->execute([$_SESSION['user_proterie']]);
        return $stmt->fetchAll();
    }
    
    if (hasRole('paroh')) {
        $stmt = $pdo->prepare("SELECT * FROM parohii WHERE id_parohie = ?");
        $stmt->execute([$_SESSION['user_parohie']]);
        return $stmt->fetchAll();
    }
    
    return [];
}

// Middleware pentru verificarea autentificării
function requireAuth() {
    if (!isLoggedIn()) {
        redirect('../index.php');
    }
}

// Middleware pentru verificarea rolului
function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        die("Acces interzis! Nu aveți permisiunile necesare.");
    }
}

// Middleware pentru verificarea oricărui rol din lista dată
function requireAnyRole(...$roles) {
    requireAuth();
    $hasAccess = false;

    foreach ($roles as $role) {
        if (hasRole($role)) {
            $hasAccess = true;
            break;
        }
    }

    if (!$hasAccess) {
        die("Acces interzis! Nu aveți permisiunile necesare.");
    }
}

// Actualizează ultima autentificare
function updateLastLogin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
    $stmt->execute([$user_id]);
}

// Obține informații despre utilizatorul curent
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, r.nume_rol, p.nume_parohie, pr.denumire as nume_proterie
        FROM users u 
        LEFT JOIN roluri r ON u.id_rol = r.id_rol
        LEFT JOIN parohii p ON u.id_parohie = p.id_parohie
        LEFT JOIN protopopiate pr ON u.id_proterie = pr.id_proterie
        WHERE u.id_user = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>