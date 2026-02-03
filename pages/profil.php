<?php
$page_title = 'Profil Utilizator';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Obține datele utilizatorului curent
$current_user = getCurrentUser();
$user_id = $_SESSION['user_id'];

// Procesare actualizare profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume = sanitize($_POST['nume']);
    $email = sanitize($_POST['email']);
    $telefon = sanitize($_POST['telefon']);

    try {
        $stmt = $pdo->prepare("UPDATE users SET Nume = ? WHERE id_user = ?");
        $stmt->execute([$nume, $user_id]);

        // Actualizează și în sesiune
        $_SESSION['user_name'] = $nume;

        $_SESSION['success_message'] = 'Profilul a fost actualizat cu succes!';
        redirect('profil.php');
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Eroare la actualizarea profilului: ' . $e->getMessage();
    }
}
?>

<div class="col-12">
    <div class="main-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>
                Profil Utilizator
            </h1>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-user-edit me-2"></i>
                            Informații Personale
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="nume" class="form-label">Nume Complet *</label>
                                <input type="text" class="form-control" id="nume" name="nume"
                                       value="<?php echo htmlspecialchars($current_user['Nume'] ?? ''); ?>" required>
                                <div class="invalid-feedback">Vă rugăm să introduceți numele complet.</div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Nume Utilizator</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo htmlspecialchars($current_user['username'] ?? ''); ?>" readonly>
                                <div class="form-text">Numele de utilizator nu poate fi modificat.</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon"
                                       value="<?php echo htmlspecialchars($current_user['telefon'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['nume_rol'] ?? ''); ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ultima Autentificare</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['last_login'] ?? 'Niciodată'); ?>" readonly>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Actualizează Profil
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-info-circle me-2"></i>
                            Informații Suplimentare
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($current_user['nume_parohie'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Parohie Asociată</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($current_user['nume_parohie']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($current_user['nume_proterie'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Protopopiat Asociat</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($current_user['nume_proterie']); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Status Cont</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-<?php echo ($current_user['status'] ?? '') === 'activ' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($current_user['status'] ?? ''); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
