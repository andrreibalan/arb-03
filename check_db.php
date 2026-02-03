<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=arb00', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Current biserici table structure ===\n";
    $stmt = $pdo->query('DESCRIBE biserici');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\n=== Current hramuri table structure ===\n";
    $stmt = $pdo->query('DESCRIBE hramuri');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\n=== Checking if tipuri_biserici table exists ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE tipuri_biserici');
        echo "tipuri_biserici table exists\n";
    } catch (Exception $e) {
        echo "tipuri_biserici table does NOT exist\n";
    }

    echo "\n=== Current taxe_cmt table structure ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE taxe_cmt');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "taxe_cmt table does NOT exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== Current concesionari table structure ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE concesionari');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "concesionari table does NOT exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== Current cimitir table structure ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE cimitir');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "cimitir table does NOT exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== Current loc_cimitir table structure ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE loc_cimitir');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "loc_cimitir table does NOT exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n=== Current concesiuni table structure ===\n";
    try {
        $stmt = $pdo->query('DESCRIBE concesiuni');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . ($col['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "concesiuni table does NOT exist or error: " . $e->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
