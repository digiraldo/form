<?php
ini_set('display_errors', 1); // Temporal para ver errores en pantalla
error_reporting(E_ALL);   // Temporal para ver todos los errores

session_start();
define('DATA_USERS_FILE', __DIR__ . '/data/users.json');

// --- Depuración de la ruta del archivo ---
// error_log("Ruta de DATA_USERS_FILE: " . DATA_USERS_FILE);
// error_log("¿Existe el archivo DATA_USERS_FILE? " . (file_exists(DATA_USERS_FILE) ? 'Sí' : 'No'));
// --- Fin Depuración ---

$needs_initial_setup = false;
if (!file_exists(DATA_USERS_FILE) || filesize(DATA_USERS_FILE) === 0) {
    $needs_initial_setup = true;
} else {
    $users_content = file_get_contents(DATA_USERS_FILE);
    if ($users_content === false || empty(trim($users_content)) || trim($users_content) === '[]') {
        $needs_initial_setup = true;
    } else {
        $users_check = json_decode($users_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($users_check)) {
            $needs_initial_setup = true; // Archivo existe pero JSON inválido o vacío
        }
    }
}

// Si el usuario ya está logueado Y NO se necesita setup inicial, redirigir al dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !$needs_initial_setup) {
    header('Location: admin_dashboard.php');
    exit;
}

// Definición de la URL base para las imágenes de perfil
if (!defined('PROFILE_IMG_DIR_FROM_ROOT_LOGIN')) { // Usar un nombre de constante diferente para evitar conflictos si se incluye otro archivo
    define('PROFILE_IMG_DIR_FROM_ROOT_LOGIN', '/profile_images/');
}
if (!defined('PROFILE_IMG_BASE_URL_LOGIN')) {
    $protocol_login = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host_login = $_SERVER['HTTP_HOST'];
    // Ajuste para obtener correctamente la raíz del proyecto cuando está en un subdirectorio
    $script_path_login = dirname($_SERVER['PHP_SELF']); // Directorio del script actual (/)
    // Si login.php está en la raíz, script_path_login es '/', project_root_path_login debería ser ''
    // Si está en /subdir/login.php, script_path_login es '/subdir', project_root_path_login debería ser '/subdir'
    $project_root_path_login = ($script_path_login === '/' || $script_path_login === '\\\\') ? '' : $script_path_login;
    define('PROFILE_IMG_BASE_URL_LOGIN', rtrim($protocol_login . $host_login . $project_root_path_login, '/') . PROFILE_IMG_DIR_FROM_ROOT_LOGIN);
}

$error_message = '';

function get_users() {
    // error_log("Intentando leer usuarios desde: " . DATA_USERS_FILE);
    if (!file_exists(DATA_USERS_FILE)) {
        // error_log("Error: El archivo de usuarios no existe en la ruta: " . DATA_USERS_FILE);
        return [];
    }
    $json_data = file_get_contents(DATA_USERS_FILE);
    if ($json_data === false) {
        // error_log("Error: No se pudo leer el contenido del archivo de usuarios: " . DATA_USERS_FILE);
        return [];
    }
    // error_log("Contenido crudo de users.json: " . $json_data);
    $users = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // error_log("Error al decodificar JSON de usuarios: " . json_last_error_msg());
        // error_log("Contenido que falló la decodificación: " . $json_data);
        return [];
    }
    // error_log("Usuarios decodificados: " . print_r($users, true));
    return is_array($users) ? $users : [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_attempt = $_POST['username'] ?? '';
    $password_attempt = $_POST['password'] ?? '';
    // error_log("Intento de login con usuario: " . $username_attempt);

    $users = get_users();
    $authenticated_user = null;
    $user_found_by_name = false;

    foreach ($users as $user) {
        if (isset($user['username']) && $user['username'] === $username_attempt) {
            $user_found_by_name = true;
            // error_log("Usuario encontrado por nombre: " . $user['username']);
            // error_log("Hash almacenado: " . $user['password']);
            // error_log("Contraseña intentada: " . $password_attempt);
            if (isset($user['password']) && password_verify($password_attempt, $user['password'])) {
                // error_log("Contraseña verificada para: " . $user['username']);
                $authenticated_user = $user;
                break;
            } else {
                // error_log("Fallo en password_verify para: " . $user['username']);
            }
        }
    }

    if ($authenticated_user) {
        // error_log("Autenticación exitosa para: " . $authenticated_user['username']);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $authenticated_user['id'];
        $_SESSION['admin_username'] = $authenticated_user['username'];
        $_SESSION['user_role'] = $authenticated_user['role'];

        // Establecer la URL de la imagen de perfil en la sesión
        if (!empty($authenticated_user['profile_image_filename'])) {
            $_SESSION['profile_image_url'] = PROFILE_IMG_BASE_URL_LOGIN . $authenticated_user['profile_image_filename'];
        } else {
            $_SESSION['profile_image_url'] = null;
        }
        error_log("[Login.php] Login exitoso para " . $authenticated_user['username'] . ". Profile Image URL en sesión: " . ($_SESSION['profile_image_url'] ?? 'NINGUNA'));

        header('Location: admin_dashboard.php');
        exit;
    } else {
        // error_log("Autenticación fallida. Usuario encontrado por nombre: " . ($user_found_by_name ? 'Sí' : 'No'));
        $error_message = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $needs_initial_setup ? 'Configuración Inicial' : 'Iniciar Sesión'; ?> - Admin Forms</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bs-light-bg-subtle); 
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border-radius: 0.75rem; 
        }
        .debug-output { /* Estilo para la salida de depuración en pantalla */
            background-color: #000000;
            border: 1px solid #dda;
            padding: 10px;
            margin-bottom: 15px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <?php
                // --- Salida de depuración en pantalla ---
                //  echo "<div class='debug-output'>";
                //  echo "Ruta de DATA_USERS_FILE: " . DATA_USERS_FILE . "<br>";
                //  echo "¿Existe el archivo DATA_USERS_FILE? " . (file_exists(DATA_USERS_FILE) ? 'Sí' : 'No') . "<br>";
                //  if (file_exists(DATA_USERS_FILE)) {
                //      echo "Contenido crudo de users.json:<br><textarea rows='5' cols='50' readonly>" . htmlspecialchars(file_get_contents(DATA_USERS_FILE)) . "</textarea><br>";
                //      $temp_users = json_decode(file_get_contents(DATA_USERS_FILE), true);
                //      echo "Resultado de json_decode: ";
                //      var_dump($temp_users);
                //      echo "<br>Error de JSON (si hay): " . json_last_error_msg() . "<br>";
                //  }
                //  echo "</div>";
                // --- Fin Salida de depuración ---
                ?>

                <?php if (!$needs_initial_setup): ?>
                <div class="card login-card shadow-lg animate__animated animate__fadeInUp">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-shield fa-3x text-primary"></i>
                            <h3 class="mt-3">Acceso Administrador</h3>
                        </div>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Usuario
                                </label>
                                <input type="text" class="form-control form-control-lg rounded-pill" id="username" name="username" required value="<?php echo htmlspecialchars($username_attempt ?? ''); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <input type="password" class="form-control form-control-lg rounded-pill" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill">
                                    <i class="fas fa-sign-in-alt me-2"></i>Ingresar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <small class="text-muted">&copy; <?php echo date('Y'); ?> Custom Forms App</small>
                    </div>
                </div>
                <?php else: ?>
                <div class="card initial-setup-card shadow-lg animate__animated animate__fadeInUp">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-cogs fa-3x text-primary"></i>
                            <h3 class="mt-3">Configuración Inicial Requerida</h3>
                        </div>
                        <p class="text-center text-muted">Es necesario crear el primer usuario propietario para utilizar la aplicación.</p>
                        <div class="d-grid">
                             <button type="button" class="btn btn-primary btn-lg rounded-pill" data-bs-toggle="modal" data-bs-target="#initialSetupModal">
                                <i class="fas fa-user-plus me-2"></i>Crear Usuario Propietario
                            </button>
                        </div>
                    </div>
                     <div class="card-footer text-center py-3">
                        <small class="text-muted">&copy; <?php echo date('Y'); ?> Custom Forms App</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para Configuración Inicial del Propietario -->
    <div class="modal fade" id="initialSetupModal" tabindex="-1" aria-labelledby="initialSetupModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="initialSetupModalLabel"><i class="fas fa-user-shield me-2"></i>Crear Usuario Propietario Inicial</h5>
                </div>
                <form id="initialSetupForm">
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            Este será el primer usuario del sistema y tendrá el rol de <strong>Propietario</strong>.
                        </div>
                        <div class="mb-3">
                            <label for="setupUsername" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="setupUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="setupPassword" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="setupPassword" name="password" required>
                        </div>
                        <input type="hidden" name="role" value="owner">
                        <div class="mb-3">
                            <label for="setupProfileImage" class="form-label">Imagen de Perfil (opcional)</label>
                            <input type="file" class="form-control" id="setupProfileImage" name="profile_image" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text text-muted">JPG, PNG, GIF. Máx 2MB.</small>
                        </div>
                        <div id="initialSetupError" class="alert alert-danger mt-3 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100">Crear Propietario y Continuar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast para notificaciones -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1150">
      <div id="notificationToastLogin" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header"> 
          <i class="fas fa-info-circle me-2"></i> 
          <strong class="me-auto">Notificación</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          <!-- Mensaje del toast -->
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // ...existing code...

        const needsInitialSetup = <?php echo json_encode($needs_initial_setup); ?>;
        let initialSetupModalInstance = null;

        function showLoginToast(message, type = 'info') {
            const toastElement = document.getElementById('notificationToastLogin');
            const toastBody = toastElement.querySelector('.toast-body');
            const toastHeader = toastElement.querySelector('.toast-header .me-auto');
            const toastIcon = toastElement.querySelector('.toast-header i');

            toastBody.textContent = message;
            toastElement.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white', 'text-dark');
            toastIcon.className = 'fas me-2';

            switch (type) {
                case 'success':
                    toastElement.classList.add('bg-success', 'text-white');
                    toastHeader.textContent = 'Éxito';
                    toastIcon.classList.add('fa-check-circle');
                    break;
                case 'danger':
                    toastElement.classList.add('bg-danger', 'text-white');
                    toastHeader.textContent = 'Error';
                    toastIcon.classList.add('fa-times-circle');
                    break;
                case 'warning':
                    toastElement.classList.add('bg-warning', 'text-dark');
                    toastHeader.textContent = 'Advertencia';
                    toastIcon.classList.add('fa-exclamation-triangle');
                    break;
                default:
                    toastElement.classList.add('bg-info', 'text-white');
                    toastHeader.textContent = 'Información';
                    toastIcon.classList.add('fa-info-circle');
                    break;
            }
            const toast = bootstrap.Toast.getOrCreateInstance(toastElement);
            toast.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (needsInitialSetup) {
                const initialSetupModalElement = document.getElementById('initialSetupModal');
                initialSetupModalInstance = new bootstrap.Modal(initialSetupModalElement);
                initialSetupModalInstance.show();

                const setupForm = document.getElementById('initialSetupForm');
                const setupErrorDiv = document.getElementById('initialSetupError');

                setupForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    setupErrorDiv.classList.add('d-none');
                    setupErrorDiv.textContent = '';

                    const formData = new FormData(setupForm);
                    
                    fetch('api/users.php?action=create_initial_owner', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showLoginToast('Usuario propietario creado con éxito. Ahora puedes iniciar sesión.', 'success');
                            if(initialSetupModalInstance) {
                                initialSetupModalInstance.hide();
                            }
                            // Opcional: Pequeña demora antes de recargar para que el usuario vea el toast.
                            setTimeout(() => {
                                window.location.reload(); // Recargar para mostrar el formulario de login
                            }, 2500);
                        } else {
                            setupErrorDiv.textContent = data.message || 'Error al crear el usuario propietario.';
                            setupErrorDiv.classList.remove('d-none');
                            showLoginToast(data.message || 'Error al crear el usuario propietario.', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error en initialSetupForm:', error);
                        setupErrorDiv.textContent = 'Error de conexión al servidor. Inténtalo de nuevo.';
                        setupErrorDiv.classList.remove('d-none');
                        showLoginToast('Error de conexión al servidor.', 'danger');
                    });
                });
            }
             // Verificar si hay un mensaje de owner_created en la URL (después de la creación desde admin_users.php)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('owner_created') && urlParams.get('owner_created') === 'true') {
                showLoginToast('Propietario creado con éxito. Por favor, inicia sesión.', 'success');
                // Limpiar el parámetro de la URL para que no se muestre el toast en cada recarga
                if (window.history.replaceState) {
                    const cleanURL = window.location.pathname;
                    window.history.replaceState({ path: cleanURL }, '', cleanURL);
                }
            }

        });
    </script>
</body>
</html>
