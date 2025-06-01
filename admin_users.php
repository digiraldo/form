<?php
session_start();

// Protección de ruta: Solo el 'owner' puede acceder
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['owner', 'admin'])) {
    header('Location: admin_dashboard.php?error=unauthorized_user_management');
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

$GLOBALS['form_count'] = $form_count; // Añadido para pasar al navbar
$GLOBALS['user_count'] = $user_count; // Añadido para pasar al navbar

$page_title = "Gestión de Usuarios";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Formularios</title>
    <?php include 'header_includes.php'; ?>    <style>
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
        /* Estilos para badges de áreas */
        .badge.fs-6 {
            transition: all 0.2s ease-in-out;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .badge.fs-6:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .tooltip-areas-list {
            max-width: 280px;
            font-size: 0.9rem;
        }
        .tooltip-areas-list .hover-effect:hover {
            transform: translateX(3px);
            background-color: rgba(0,0,0,0.05) !important;
        }
        /* Mejorar aspecto de los selects de áreas */
        select option.area-option {
            margin: 3px 0;
            padding: 8px;
        }
        /* Estilo para opciones de select en Chrome/Firefox */
        select.form-select {
            background-size: 16px 12px !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4a5568 !important;
        }
        /* Animación para badges de áreas */
        @keyframes pulse-shadow {
            0% { box-shadow: 0 0 0 0 rgba(0,0,0,0.2); }
            70% { box-shadow: 0 0 0 6px rgba(0,0,0,0); }
            100% { box-shadow: 0 0 0 0 rgba(0,0,0,0); }
        }
        .badge.fs-6:active {
            animation: pulse-shadow 0.5s ease-out;
        }
        
        /* Mejoras para tooltips Bootstrap */
        .tooltip {
            pointer-events: none;
            z-index: 1080 !important;
        }
        .tooltip-inner {
            max-width: 300px;
            padding: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-radius: 0.375rem;
        }
        /* Prevenir que el tooltip se muestre como texto en la columna */
        [data-tooltip-content] {
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Spinner visual overlay -->
    <div id="mainSpinner">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>
    
    <?php include 'navbar.php'; ?>

    <main class="container-fluid mt-5 pt-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 animate__animated animate__fadeInLeft"><?php echo htmlspecialchars($page_title); ?></h1>
            <div>
                <button class="btn btn-primary hvr-sweep-to-right me-2" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </button>
                <a href="admin_areas.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-layer-group me-1"></i>Gestionar Áreas
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Lista de Usuarios</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-striped table-hover table-custom align-middle" style="width:100%;">                        <thead>
                            <tr>
                                <th style="width: 70px; text-align: center; white-space: nowrap;">Perfil</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Áreas</th>
                                <th>Creado el</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel"><i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createUserForm" enctype="multipart/form-data"> 
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newUsername" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                        </div>                        <div class="mb-3">
                            <label for="newRole" class="form-label">Rol <span class="text-danger">*</span></label>
                            <select class="form-select" id="newRole" name="role" required>
                                <option value="" disabled selected>Selecciona un rol</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="newAreaIdGroup">
                            <label for="newAreaId" class="form-label">Área <span class="text-danger">*</span></label>
                            <select class="form-select" id="newAreaId" name="area_id">
                                <option value="" disabled selected>Selecciona un área</option>
                                <!-- Las opciones se cargarán dinámicamente mediante JavaScript -->
                            </select>
                            <small class="form-text text-muted">Selecciona el área a la que asignar este usuario.</small>
                        </div>
                        <div class="mb-3"> <!-- Nuevo campo para imagen de perfil -->
                            <label for="newUserProfileImage" class="form-label">Imagen de Perfil (opcional)</label>
                            <input type="file" class="form-control" id="newUserProfileImage" name="profile_image" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text text-muted">JPG, PNG, GIF. Máx 2MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editUserRoleModal" tabindex="-1" aria-labelledby="editUserRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserRoleModalLabel"><i class="fas fa-user-edit me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserRoleForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="user_id">
                        <p>Editando usuario: <strong id="editUsernameDisplay"></strong></p>
                        
                        <div class="mb-3">
                            <label for="editUserProfileImage" class="form-label">Imagen de Perfil (opcional)</label>
                            <div class="mb-2 text-center">
                                <img src="" id="editProfileImagePreview" alt="Vista previa" class="profile-image-table d-none" style="width: 80px; height: 80px; margin-bottom: 10px;">
                                <div id="editProfileImagePlaceholder" class="profile-image-placeholder-table" style="width: 80px; height: 80px; font-size: 2rem; margin-bottom: 10px;">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                            </div>
                            <input type="file" class="form-control form-control-sm" id="editUserProfileImage" name="profile_image" accept="image/jpeg, image/png, image/gif">
                            <small class="form-text text-muted">Dejar en blanco para no cambiar. JPG, PNG, GIF. Máx 2MB.</small>
                        </div>

                        <div class="mb-3">
                            <label for="editRole" class="form-label">Nuevo Rol <span class="text-danger">*</span></label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="" disabled selected>Selecciona un rol</option>
                                <option value="editor">Editor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="editAdminIdGroup">
                            <label for="editAdminId" class="form-label">Administrador Responsable <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAdminId" name="admin_id">
                                <option value="" disabled selected>Selecciona un administrador</option>
                                <!-- Opciones dinámicas vía JS -->
                            </select>
                            <small class="form-text text-muted">Solo para editores. Elige el administrador responsable de este editor.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" aria-labelledby="confirmDeleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteUserModalLabel"><i class="fas fa-user-times me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar al usuario "<strong id="userNameToDeleteDisplay"></strong>"?</p>
                    <p class="text-danger small">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">Eliminar Usuario</button>
                </div>
            </div>
        </div>
    </div>

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
    </div>    <script>
        // currentUserForAdminUsers se usa en admin_users.js
        const currentUserForAdminUsers = { 
            id: <?php echo json_encode($_SESSION['user_id'] ?? null); ?>,
            role: <?php echo json_encode($_SESSION['user_role'] ?? null); ?>,
            username: <?php echo json_encode($_SESSION['admin_username'] ?? null); ?>,
            profileImageUrl: <?php echo json_encode($_SESSION['profile_image_url'] ?? null); ?>,
            areas_admin: <?php 
                // Cargar las áreas del usuario actual si es admin
                if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
                    $users_file_path = __DIR__ . '/data/users.json';
                    if (file_exists($users_file_path)) {
                        $users_data = json_decode(file_get_contents($users_file_path), true);
                        foreach ($users_data as $user) {
                            if ($user['id'] === $_SESSION['user_id']) {
                                echo json_encode($user['areas_admin'] ?? []);
                                break;
                            }
                        }
                    } else {
                        echo "[]";
                    }
                } else {
                    echo "[]";
                }            ?>
        };
    </script>
    <!-- Cargar información de áreas primero -->
    <script src="js/common.js?v=<?php echo time(); ?>"></script>
    <script src="js/admin.js?v=<?php echo time(); ?>"></script>
    <script src="js/admin_users.js?v=<?php echo time(); ?>"></script>
</body>
</html>
