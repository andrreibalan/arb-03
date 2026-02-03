<?php
// Pornim output buffering pentru a preveni erorile cu headers already sent
ob_start();

// Configurare conexiune baza de date
define('DB_HOST', 'localhost');
define('DB_NAME', 'arb01');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Eroare conexiune baza de date: " . $e->getMessage());
}

// Funcție pentru verificarea conexiunii
function testConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funcții utile
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        // Dacă headers au fost deja trimise, folosim JavaScript pentru redirect
        echo "<script>window.location.href = '$url';</script>";
        exit();
    }
}

function showAlert($message, $type = 'info') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// La sfârșitul scriptului, flush output buffer-ul
ob_end_flush();
?>
