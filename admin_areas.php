<?php
session_start();

// Contador de formularios
$forms_directory = __DIR__ . '/data/forms/';
$form_files = glob($forms_directory . '*.json');
$form_count = $form_files ? count($form_files) : 0;

// Contador de usuarios
$users_file_path = __DIR__ . '/data/users.json';
$user_count = 0;
if (file_exists($users_file_path)) {
    $users_json = file_get_contents($users_file_path);
    $users_array = json_decode($users_json, true);
    if (is_array($users_array)) {
        $user_count = count($users_array);
    }
}

$GLOBALS['form_count'] = $form_count; // Añadido para pasar al navbar
$GLOBALS['user_count'] = $user_count; // Añadido para pasar al navbar

// Protección de ruta: Solo owner y admin pueden acceder
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['owner', 'admin'])) {
    header('Location: admin_dashboard.php?error=unauthorized_area_management');
    exit;
}

// Variable para determinar si el usuario actual es propietario o administrador
$is_owner = $_SESSION['user_role'] === 'owner';
$current_user_id = $_SESSION['user_id'] ?? '';

$page_title = "Gestión de Áreas de Trabajo";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Formularios</title>
    <script>
    // Inicializar datos del usuario actual para asegurar disponibilidad antes de cargar el JS principal
    window.currentUserForAreas = {
        id: "<?php echo $_SESSION['user_id']; ?>",
        role: "<?php echo $_SESSION['user_role']; ?>",
        isOwner: <?php echo $is_owner ? 'true' : 'false'; ?>
    };
    </script>
    <?php include 'header_includes.php'; ?>
    <style>
        /* Mejora visual para selects múltiples */
        select[multiple] {
            min-height: 120px;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        select[multiple] option {
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 2px;
        }
        select[multiple]:focus {
            outline: 2px solid #4b6cb7;
            border-color: #4b6cb7;
            background: #eef2fa;
        }
        /* Mejora visual para selects múltiples de usuarios */
        select[multiple] {
            min-height: 140px;
            background: #f8f9fa;
            border: 1px solid #bfc9d1;
            border-radius: 6px;
            padding: 8px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        select[multiple] option {
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 2px;
            background: #fff;
            color: #222;
        }
        select[multiple]:focus {
            outline: 2px solid #4b6cb7;
            border-color: #4b6cb7;
            background: #eef2fa;        }
        label[for="areaAdmins"], label[for="areaEditors"] {
            font-weight: 600;
            margin-bottom: 4px;
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
        /* Estilos para imágenes de perfil en la tabla de áreas */
        #areasTable .rounded-circle {
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        #areasTable .d-flex.align-items-center {
            transition: all 0.2s ease;
        }
        #areasTable .d-flex.align-items-center:hover {
            transform: translateX(3px);
            background-color: rgba(0,0,0,0.02);
            border-radius: 6px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include 'navbar.php'; ?>    <main class="container-fluid mt-5 pt-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 animate__animated animate__fadeInLeft"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php if ($is_owner): ?>
            <button id="btnCreateArea" class="btn btn-primary hvr-sweep-to-right create-area-btn" data-bs-toggle="modal" data-bs-target="#createAreaModal">
                <i class="fas fa-layer-group me-2"></i>Crear Nueva Área
            </button>
            <?php endif; ?>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Lista de Áreas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="areasTable" class="table table-striped table-hover table-custom align-middle" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Administradores</th>
                                <th>Editores</th>
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
    <div class="modal fade" id="createAreaModal" tabindex="-1" aria-labelledby="createAreaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAreaModalLabel">
                        <i class="fas fa-layer-group me-2"></i>
                        <span id="modalAreaTitle">Crear Nueva Área</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createAreaForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="areaName" class="form-label">Nombre del Área <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="areaName" name="name" required>
                        </div>                        <div class="mb-3">
                            <label for="areaDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="areaDescription" name="description" rows="2"></textarea>
                        </div>                        <div class="mb-3">
                            <label for="areaColor" class="form-label">Color del Área</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-palette"></i></span>
                                <input type="color" class="form-control form-control-color" id="areaColor" name="color" value="#4285F4" title="Elige un color para el área">
                                <span class="input-group-text preview-color-text" id="colorPreviewText">Previsualización</span>
                            </div>
                            <div class="mt-2 p-2 rounded color-preview" id="colorPreview" style="background-color: #4285F4; color: #FFFFFF; text-align: center; border-radius: 6px;">
                                <span>Vista previa del color del área</span>
                            </div>                            <small class="form-text text-muted">Este color se usará para identificar visualmente el área en la interfaz.</small>
                        </div>
                        <div class="mb-3 form-group-admins">
                            <label for="areaAdmins" class="form-label">Administradores</label>
                            <ul class="list-group" id="areaAdminsList" style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem;">
                                <!-- Checkboxes de administradores se cargarán aquí por JS -->
                            </ul>
                            <small class="form-text text-muted">Selecciona los administradores para esta área.</small>
                        </div>
                        <div class="mb-3">
                            <label for="areaEditors" class="form-label">Editores</label>
                            <ul class="list-group" id="areaEditorsList" style="max-height: 150px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem;">
                                <!-- Checkboxes de editores se cargarán aquí por JS -->
                            </ul>
                            <small class="form-text text-muted">Selecciona los editores para esta área.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Área</button>
                    </div>
                </form>
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
    </div>    <div id="mainSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
    <script src="js/admin_areas.js?v=<?php echo time(); ?>"></script>
    <script>
    // Permitir que las funciones JS de edición/eliminación estén en el scope global
    window.abrirModalEditarArea = abrirModalEditarArea;
    window.eliminarArea = eliminarArea;
    </script>
</body>
</html>
