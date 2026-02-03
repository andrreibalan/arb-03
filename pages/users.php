<?php
$page_title = 'Gestionare Utilizatori';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Verifică permisiunile - doar admin poate gestiona utilizatorii
requireRole('admin');

// Debug: Verificăm sesiunea utilizatorului curent
$debug_session = "Sesiune utilizator curent:<br>";
$debug_session .= "User ID: " . ($_SESSION['user_id'] ?? 'null') . "<br>";
$debug_session .= "Username: " . ($_SESSION['username'] ?? 'null') . "<br>";
$debug_session .= "User Name: " . ($_SESSION['user_name'] ?? 'null') . "<br>";
$debug_session .= "User Role: " . ($_SESSION['user_role'] ?? 'null') . "<br>";
$debug_session .= "User Parohie: " . ($_SESSION['user_parohie'] ?? 'null') . "<br>";
$debug_session .= "User Proterie: " . ($_SESSION['user_proterie'] ?? 'null') . "<br>";

// Procesare acțiuni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id_user = $_POST['id_user'] ?? null;
        $username = sanitize($_POST['username']);
        $password = $_POST['password'] ?? '';
        $nume = sanitize($_POST['nume']);
        $email = sanitize($_POST['email']);
        $telefon = sanitize($_POST['telefon']);
        $id_rol = intval($_POST['id_rol']);
        $id_parohie = !empty($_POST['id_parohie']) ? intval($_POST['id_parohie']) : null;
        $id_proterie = !empty($_POST['id_proterie']) ? intval($_POST['id_proterie']) : null;
        $status = sanitize($_POST['status']);

        try {
            if ($action === 'add') {
                // Verifică dacă username-ul există deja
                $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $_SESSION['error_message'] = 'Username-ul există deja!';
                } else {
                    // Hash parola
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, Nume, email, telefon, id_rol, id_parohie, id_proterie, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$username, $hashed_password, $nume, $email, $telefon, $id_rol, $id_parohie, $id_proterie, $status]);
                    $_SESSION['success_message'] = 'Utilizatorul a fost adăugat cu succes!';
                }
            } else {
                // Actualizare utilizator
                $update_data = [$username, $nume, $email, $telefon, $id_rol, $id_parohie, $id_proterie, $status, $id_user];
                $update_query = "UPDATE users SET username = ?, Nume = ?, email = ?, telefon = ?, id_rol = ?, id_parohie = ?, id_proterie = ?, status = ? WHERE id_user = ?";

                // Dacă s-a introdus o parolă nouă, actualizează și ea
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_query = "UPDATE users SET username = ?, password = ?, Nume = ?, email = ?, telefon = ?, id_rol = ?, id_parohie = ?, id_proterie = ?, status = ? WHERE id_user = ?";
                    array_splice($update_data, 1, 0, $hashed_password);
                }

                $stmt = $pdo->prepare($update_query);
                $stmt->execute($update_data);
                $_SESSION['success_message'] = 'Utilizatorul a fost actualizat cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la salvarea datelor: ' . $e->getMessage();
        }
        redirect('users.php');
    } elseif ($action === 'delete') {
        $id_user = intval($_POST['id_user']);

        try {
            // Nu permitem ștergerea propriului cont
            if ($id_user == $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Nu puteți șterge propriul cont!';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
                $stmt->execute([$id_user]);
                $_SESSION['success_message'] = 'Utilizatorul a fost șters cu succes!';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Eroare la ștergerea utilizatorului: ' . $e->getMessage();
        }
        redirect('users.php');
    }
}

// Inițializăm variabilele
$roluri = [];
$parohii = [];
$protopopiate = [];
$users = [];

// Preia date pentru dropdown-uri
try {
    // Debug: Verificăm tabelele disponibile
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $debug_info = "Tabele disponibile: " . implode(", ", $tables) . "<br>";
    $debug_info .= $debug_session . "<br>";

    $stmt = $pdo->query("SELECT id_rol, nume_rol FROM roluri ORDER BY nume_rol");
    $roluri = $stmt->fetchAll();
    $debug_info .= "Roluri găsite: " . count($roluri) . "<br>";

    $stmt = $pdo->query("SELECT id_parohie, nume_parohie FROM parohii ORDER BY nume_parohie");
    $parohii = $stmt->fetchAll();
    $debug_info .= "Parohii găsite: " . count($parohii) . "<br>";

    $stmt = $pdo->query("SELECT id_proterie, denumire FROM protopopiate ORDER BY denumire");
    $protopopiate = $stmt->fetchAll();
    $debug_info .= "Protopopiate găsite: " . count($protopopiate) . "<br>";

    // Verificăm mai întâi tabela users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    $debug_info .= "Utilizatori în total: " . $user_count . "<br>";

    // Preia utilizatorii
    $stmt = $pdo->query("
        SELECT u.*, r.nume_rol, p.nume_parohie, pt.denumire as nume_proterie
        FROM users u
        LEFT JOIN roluri r ON u.id_rol = r.id_rol
        LEFT JOIN parohii p ON u.id_parohie = p.id_parohie
        LEFT JOIN protopopiate pt ON u.id_proterie = pt.id_proterie
        ORDER BY u.Nume
    ");
    $users = $stmt->fetchAll();
    $debug_info .= "Utilizatori după JOIN: " . count($users) . "<br>";

    // Adăugăm debug info în sesiune pentru afișare
    $_SESSION['debug_info'] = $debug_info;

} catch (PDOException $e) {
    $error_message = 'Eroare la încărcarea datelor: ' . $e->getMessage();
    $_SESSION['debug_info'] = 'Eroare: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-users me-2"></i>Gestionare Utilizatori
                </h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="fas fa-plus me-2"></i>Adaugă Utilizator
                </button>
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

            <!-- Debug Information -->
            <?php if (isset($_SESSION['debug_info'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6><i class="fas fa-bug me-2"></i>Informații Debug:</h6>
                    <div style="font-size: 0.9em; white-space: pre-line;"><?php echo $_SESSION['debug_info']; unset($_SESSION['debug_info']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Nume</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Rol</th>
                                    <th>Parohie</th>
                                    <th>Protopopiat</th>
                                    <th>Status</th>
                                    <th>Ultima autentificare</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['Nume']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['telefon'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($user['nume_rol']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['nume_parohie'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($user['nume_proterie'] ?? ''); ?></td>
                                        <td>
                                            <?php if ($user['status'] === 'activ'): ?>
                                                <span class="badge bg-success">Activ</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactiv</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Niciodată'; ?></td>
                                        <td class="table-actions">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                    title="Editează">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['id_user'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteUser(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars($user['Nume']); ?>')"
                                                        title="Șterge">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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
</div>

<!-- Modal pentru adăugare/editare utilizator -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">
                    <i class="fas fa-user-plus me-2"></i>Adaugă Utilizator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_user" id="userId" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" id="username" required>
                                <div class="invalid-feedback">Vă rugăm să introduceți username-ul.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Parolă *</label>
                                <input type="password" class="form-control" name="password" id="password" required>
                                <div class="invalid-feedback">Vă rugăm să introduceți parola.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nume" class="form-label">Nume complet *</label>
                                <input type="text" class="form-control" name="nume" id="nume" required>
                                <div class="invalid-feedback">Vă rugăm să introduceți numele complet.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" name="telefon" id="telefon">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_rol" class="form-label">Rol *</label>
                                <select class="form-select" name="id_rol" id="id_rol" required>
                                    <option value="">Selectați rolul</option>
                                    <?php foreach ($roluri as $rol): ?>
                                        <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nume_rol']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vă rugăm să selectați rolul.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_parohie" class="form-label">Parohie</label>
                                <select class="form-select" name="id_parohie" id="id_parohie">
                                    <option value="">Selectați parohia</option>
                                    <?php foreach ($parohii as $parohie): ?>
                                        <option value="<?php echo $parohie['id_parohie']; ?>"><?php echo htmlspecialchars($parohie['nume_parohie']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_proterie" class="form-label">Protopopiat</label>
                                <select class="form-select" name="id_proterie" id="id_proterie">
                                    <option value="">Selectați protopopiatul</option>
                                    <?php foreach ($protopopiate as $protopopiat): ?>
                                        <option value="<?php echo $protopopiat['id_proterie']; ?>"><?php echo htmlspecialchars($protopopiat['denumire']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="">Selectați statusul</option>
                            <option value="activ">Activ</option>
                            <option value="dezactivat">Inactiv</option>
                        </select>
                        <div class="invalid-feedback">Vă rugăm să selectați statusul.</div>
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
function editUser(user) {
    document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Editează Utilizator';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = user.id_user;
    document.getElementById('username').value = user.username;
    document.getElementById('password').value = ''; // Nu populăm parola la editare
    document.getElementById('nume').value = user.Nume;
    document.getElementById('email').value = user.email || '';
    document.getElementById('telefon').value = user.telefon || '';
    document.getElementById('id_rol').value = user.id_rol;
    document.getElementById('id_parohie').value = user.id_parohie || '';
    document.getElementById('id_proterie').value = user.id_proterie || '';
    document.getElementById('status').value = user.status;

    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function deleteUser(id, name) {
    Swal.fire({
        title: 'Confirmare ștergere',
        text: `Sunteți sigur că doriți să ștergeți utilizatorul "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Da, șterge!',
        cancelButtonText: 'Anulează'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_user" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Reset form când se închide modalul
document.getElementById('userModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('userForm').reset();
    document.getElementById('userForm').classList.remove('was-validated');
    document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-plus me-2"></i>Adaugă Utilizator';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
});
</script>

<?php require_once '../includes/footer.php'; ?>
