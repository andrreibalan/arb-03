<?php
session_start();

// Distruge toate datele din sesiune
$_SESSION = array();

// Dacă se dorește să se distrugă complet sesiunea, șterge și cookie-ul de sesiune
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruge sesiunea
session_destroy();

// Redirecționează la pagina de login
header("Location: index.php");
exit();
?>