<?php
session_start();

// Protección de ruta: Cualquier usuario logueado puede acceder a su configuración
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: logout.php');
    exit;
}

// Contador de formularios
$forms_directory = __DIR__ . '/data/forms/';
$form_files = glob($forms_directory . '*.json');
$form_count = $form_files ? count($form_files) : 0;

// Contador de usuarios
$users_file_path = __DIR__ . '/data/users.json';
$user_count = 0;
if (file_exists($users_file_path)) {
    $users_json = file_get_contents($users_file_path);
    $users_array = json_decode($users_json, true); // Cambiado $users_data a $users_array
    if (is_array($users_array)) { // Comprobar si $users_array es un array
        $user_count = count($users_array); // Contar directamente los elementos del array
    }
}

$page_title = "Configuración de Mi Cuenta";
$user_id_to_edit = $_SESSION['user_id']; // El usuario solo puede editar su propia cuenta aquí

// Simulación de datos del usuario actual (se cargarán con JS desde la API)
$current_username = $_SESSION['admin_username'] ?? '';
$current_profile_image = ''; // Se cargará vía JS o se obtendrá de la sesión/API

// Obtener la imagen de perfil actual del usuario si existe
// Esta lógica se moverá a la API, pero es un placeholder
// if (file_exists('data/profile_images/' . $user_id_to_edit . '.jpg')) { // Asumiendo jpg por ahora
//     $current_profile_image = 'data/profile_images/' . $user_id_to_edit . '.jpg?' . time(); // Añadir time() para evitar caché
// } elseif (file_exists('data/profile_images/' . $user_id_to_edit . '.png')) {
//     $current_profile_image = 'data/profile_images/' . $user_id_to_edit . '.png?' . time();
// }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Formularios</title>
    <?php include 'header_includes.php'; ?>
    <style>
        .profile-image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dee2e6;
            cursor: pointer;
        }
        .profile-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #dee2e6;
            cursor: pointer;
        }
        .profile-image-placeholder i {
            font-size: 3rem;
            color: #adb5bd;
            margin: 0; /* Asegurar que no haya márgenes extraños */
        }
        #mainSpinner {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255,255,255,0.7);
            align-items: center;
            justify-content: center;
        }
        #mainSpinner .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'navbar.php'; ?>

    <main class="container mt-5 pt-5 flex-grow-1">
        <h1 class="h2 mb-4 animate__animated animate__fadeInLeft"><?php echo htmlspecialchars($page_title); ?></h1>

        <div class="row justify-content-center"> <!-- Modificado para centrar el contenido -->
            <div class="col-md-8"> <!-- Modificado: Eliminado order-md-2, la columna ahora se centrará -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form id="updateProfileForm" enctype="multipart/form-data">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_to_edit); ?>">
                            
                            <div class="mb-3 text-center">
                                <h5 class="mb-3">Imagen de Perfil</h5>
                                <img src="<?php echo $current_profile_image ?: 'https://placehold.co/150x150/e9ecef/adb5bd?text=?'; ?>" alt="Imagen de Perfil" id="profileImagePreview" class="profile-image-preview img-thumbnail mb-2 mx-auto d-block" style="<?php echo $current_profile_image ? 'display:block;' : 'display:none;'; ?>">
                                <div id="profileImagePlaceholder" class="profile-image-placeholder mx-auto" style="<?php echo $current_profile_image ? 'display:none;' : 'display:flex; align-items:center; justify-content:center;'; ?>">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <input type="file" class="form-control form-control-sm mt-2" id="profileImageInput" name="profile_image" accept="image/jpeg, image/png, image/gif">
                                <small class="form-text text-muted">JPG, PNG, GIF. Máx 2MB.</small>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
                            </div>
                            
                            <hr>
                            <h5 class="mt-4 mb-3">Cambiar Contraseña (opcional)</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Dejar en blanco para no cambiar">
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                            </div>

                            <button type="submit" class="btn btn-primary hvr-sweep-to-right">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header"> 
          <i class="fas fa-info-circle me-2"></i> 
          <strong class="me-auto">Notificación</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          Mensaje de la notificación.
        </div>
      </div>
    </div>

    <!-- Spinner overlay global -->
    <div id="mainSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
    <!-- Fin spinner overlay -->

    <script src="js/admin_settings.js"></script> 
</body>
</html>
