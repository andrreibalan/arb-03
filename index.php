<?php
session_start();
require_once 'config.php';

// Dacă utilizatorul este deja autentificat, redirecționează la dashboard
if (isset($_SESSION['user_id'])) {
    redirect('pages/dashboard.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Vă rugăm să completați toate câmpurile.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT u.*, r.nume_rol 
                FROM users u 
                LEFT JOIN roluri r ON u.id_rol = r.id_rol 
                WHERE u.username = ? AND u.status = 'activ'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Autentificare reușită
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['Nume'];
                $_SESSION['user_role'] = $user['nume_rol'];
                $_SESSION['user_parohie'] = $user['id_parohie'];
                $_SESSION['user_proterie'] = $user['id_proterie'];
                
                // Actualizează ultima autentificare
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
                $update_stmt->execute([$user['id_user']]);
                
                redirect('pages/dashboard.php');
            } else {
                $error_message = 'Nume de utilizator sau parolă incorectă.';
            }
        } catch (PDOException $e) {
            $error_message = 'Eroare la autentificare. Vă rugăm să încercați din nou.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare - Sistem Management Parohial</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .demo-accounts {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        
        .demo-accounts h6 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .demo-accounts .demo-account {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-church"></i>
            <h4 class="mb-0">Sistem Management</h4>
            <p class="mb-0">Parohial</p>
        </div>
        
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Nume utilizator" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    <label for="username">
                        <i class="fas fa-user me-2"></i>Nume utilizator
                    </label>
                    <div class="invalid-feedback">
                        Vă rugăm să introduceți numele de utilizator.
                    </div>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Parolă" required>
                    <label for="password">
                        <i class="fas fa-lock me-2"></i>Parolă
                    </label>
                    <div class="invalid-feedback">
                        Vă rugăm să introduceți parola.
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Autentificare
                </button>
            </form>
            
            <!-- Demo accounts pentru testare -->
            <div class="demo-accounts">
                <h6><i class="fas fa-info-circle me-1"></i>Conturi demo:</h6>
                <div class="demo-account">
                    <span><strong>Admin:</strong> admin</span>
                    <span class="text-muted">parola: admin</span>
                </div>
                <div class="demo-account">
                    <span><strong>Protopop:</strong> Bacau</span>
                    <span class="text-muted">parola: bacau</span>
                </div>
                <div class="demo-account">
                    <span><strong>Paroh:</strong> izvoare.bacau</span>
                    <span class="text-muted">parola: izvoare</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>