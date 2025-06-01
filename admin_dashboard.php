<?php
session_start();

// Protección de ruta: si el admin no está logueado, redirigir a login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    // Si falta alguno de los datos de sesión esperados, forzar logout/login
    header('Location: logout.php'); // logout.php destruirá la sesión y redirigirá a login.php
    exit;
}

// Lógica para Cierre de Sesión
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
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

$GLOBALS['form_count'] = $form_count; // Añadido para pasar al navbar
$GLOBALS['user_count'] = $user_count; // Añadido para pasar al navbar

?>
<!DOCTYPE html>
<html lang="es"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Formularios</title>
    <?php include 'header_includes.php'; ?>
    <style>
        /* Estilos para tablas modernas y responsivas */
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
        }
        .table-custom thead th {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: none;
            padding: 15px;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,.1);
            transition: all 0.3s;
        }
        .table-custom .badge {
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 30px;
        }
        .table-custom .btn-group .btn {
            margin: 2px;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        .table-custom .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,.2);
        }
        .table-custom td {
            vertical-align: middle;
            padding: 12px 15px;
        }
          /* Estilo para tooltips */
        .custom-tooltip {
            position: relative;
            cursor: pointer;
        }
        .custom-tooltip:hover:after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            white-space: nowrap;
            font-size: 12px;
            z-index: 1000;
        }
          /* Estilos para popovers de permisos */
        .permission-popover {
            --bs-popover-max-width: 380px;
            --bs-popover-border-color: rgba(0, 0, 0, 0.12);
            --bs-popover-header-bg: #ffffff;
            --bs-popover-header-color: #495057;
            --bs-popover-body-padding-x: 1.25rem;
            --bs-popover-body-padding-y: 1rem;
            --bs-popover-border-radius: 0.75rem;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .permission-popover .popover-header {
            font-weight: 600;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 1rem 1.25rem 0.75rem;
        }
        
        .permission-popover .popover-body {
            font-size: 0.875rem;
            line-height: 1.5;
            padding: 0.75rem 1.25rem 1rem;
        }
        
        /* Nuevos estilos mejorados para el contenido del popover */
        .permission-popover .permission-popover-header .popover-title {
            font-size: 1rem;
            color: #2d3748;
        }
        
        .permission-popover .permission-popover-body .editors-list {
            max-height: 280px;
            overflow-y: auto;
            overflow-x: hidden; /* Eliminar scroll horizontal */
            padding-right: 8px; /* Espacio para scrollbar personalizado */
            margin-right: -8px; /* Compensar el padding */
        }
        
        .permission-popover .editor-item {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .permission-popover .editor-item:hover {
            background: #e3f2fd !important;
            border-color: #90caf9;
            transform: translateY(-1px);
        }

        .permission-popover .editor-avatar-popover {
            background-color: palevioletred;
            box-shadow: 0 2px 8px rgb(129, 129, 129);
            transition: transform 0.2s ease;
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            max-width: 32px !important;
            min-height: 32px !important;
            max-height: 32px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            line-height: 1 !important;
            text-align: center !important;
            overflow: hidden !important;
        }
        
        .permission-popover .editor-avatar-popover img {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            max-width: 32px !important;
            min-height: 32px !important;
            max-height: 32px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
        }
        
        .permission-popover .editor-item {
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        
        .permission-popover .editor-item:hover {
            background: #e3f2fd !important;
            border-color: #90caf9;
            transform: translateY(-1px);
        }
        
        .permission-popover .editor-item:hover .editor-avatar-popover {
            transform: scale(1.05);
        }        
        /* Estilos específicos para asegurar círculos perfectos en popovers */
        .permission-popover .editor-avatar-popover[style*="background"] {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
            letter-spacing: 0 !important;
            text-transform: uppercase !important;
        }
        
        /* Override para asegurar que Bootstrap no interfiera */
        .permission-popover .d-flex.rounded-circle {
            border-radius: 50% !important;
            aspect-ratio: 1 / 1 !important;
        }
        
        .permission-popover .permission-tooltip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .permission-popover .permission-tooltip-header .title {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #495057;
        }
        
        .permission-popover .permission-tooltip-header .title i {
            margin-right: 0.5rem;
            color: #198754;
        }
        
        .permission-popover .permission-tooltip-count {
            font-size: 0.8rem;
            color: #6c757d;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
        }
        
        .permission-popover .permission-users-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .permission-popover .permission-users-list li {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .permission-popover .permission-users-list li:last-child {
            border-bottom: none;
        }
        
        .permission-popover .user-role-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.4rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 1px solid transparent;
        }
        
        .permission-popover .user-role-badge.owner-badge {
            background: linear-gradient(135deg, #d1e7dd, #c3e6cb);
            color: #0f5132;
            border-color: #badbcc;
            box-shadow: 0 1px 3px rgba(15, 81, 50, 0.2);
        }
        
        .permission-popover .user-role-badge.admin-badge {
            background: linear-gradient(135deg, #cff4fc, #b6effb);
            color: #055160;
            border-color: #b6effb;
            box-shadow: 0 1px 3px rgba(5, 81, 96, 0.2);
        }
          .permission-popover .user-role-badge.editor-badge {
            background: linear-gradient(135deg, #fff3cd, #ffecb5);
            color: #664d03;
            border-color: #ffecb5;
            box-shadow: 0 1px 3px rgba(102, 77, 3, 0.2);
        }

        /* ===== ADAPTACIÓN A MODO CLARO/OSCURO ===== */
        [data-bs-theme="dark"] .permission-popover {
            --bs-popover-bg: #2d3748;
            --bs-popover-border-color: rgba(255, 255, 255, 0.1);
            --bs-popover-header-bg: #2d3748;
            --bs-popover-header-color: #e2e8f0;
            box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        [data-bs-theme="dark"] .permission-popover .permission-popover-header .popover-title {
            color: #e2e8f0;
        }

        [data-bs-theme="dark"] .permission-popover .editor-item {
            background: #2d3748 !important;
            border-color: transparent;
        }

        [data-bs-theme="dark"] .permission-popover .editor-item:hover {
            background: #3a4a63 !important;
            border-color: #4299e1;
        }

        [data-bs-theme="dark"] .permission-popover .editor-avatar-popover {
            border-color: #4a5568 !important;
        }

        [data-bs-theme="dark"] .permission-popover .text-muted {
            color: #a0aec0 !important;
        }

        [data-bs-theme="dark"] .permission-popover .user-role-badge.owner-badge {
            background: linear-gradient(135deg, #2d5a3d, #22543d);
            color: #9ae6b4;
            border-color: #2d5a3d;
        }

        [data-bs-theme="dark"] .permission-popover .user-role-badge.admin-badge {
            background: linear-gradient(135deg, #2d4a5a, #1a3a4a);
            color: #90cdf4;
            border-color: #2d4a5a;
        }

        [data-bs-theme="dark"] .permission-popover .user-role-badge.editor-badge {
            background: linear-gradient(135deg, #5a4a2d, #4a3a1a);
            color: #fbd38d;
            border-color: #5a4a2d;
        }

        [data-bs-theme="dark"] .permission-popover .permission-tooltip-header {
            border-bottom-color: #4a5568;
        }

        [data-bs-theme="dark"] .permission-popover .badge.bg-success {
            background: linear-gradient(135deg, #2d5a3d, #22543d) !important;
            color: #9ae6b4 !important;
        }

        /* ===== CORRECCIÓN DEL PROBLEMA DEL SCROLL ===== */
        .permission-popover .permission-popover-body .editors-list {
            max-height: 280px;
            overflow-y: auto;
            overflow-x: hidden; /* Eliminar scroll horizontal */
            padding-right: 8px; /* Espacio para scrollbar personalizado */
            margin-right: -8px; /* Compensar el padding */
        }

        /* Scrollbar personalizado para el contenido del popover */
        .permission-popover .editors-list::-webkit-scrollbar {
            width: 6px;
        }

        .permission-popover .editors-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
        }

        .permission-popover .editors-list::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 3px;
            transition: background 0.2s ease;
        }

        .permission-popover .editors-list::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.5);
        }

        /* Scrollbar para modo oscuro */
        [data-bs-theme="dark"] .permission-popover .editors-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .permission-popover .editors-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
        }

        [data-bs-theme="dark"] .permission-popover .editors-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Prevenir overflow horizontal en todo el popover */
        .permission-popover .popover-body {
            overflow-x: hidden;
            word-wrap: break-word;
        }

        .permission-popover .editor-item {
            width: 100%;
            box-sizing: border-box;
            min-width: 0; /* Permitir que flex items se contraigan */
        }

        .permission-popover .editor-item .flex-grow-1 {
            min-width: 0; /* Evitar overflow de texto largo */
        }

        .permission-popover .editor-item .fw-medium {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Animación para cargas */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        .loading-shimmer {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #eff1f3 4%, #e2e2e2 25%, #eff1f3 36%);
            background-size: 1000px 100%;
        }
          /* Ajustes para móviles */
        @media (max-width: 768px) {
            .table-custom .btn-group .btn {
                width: 32px;
                height: 32px;
                padding: 0;
                font-size: 0.875rem;
            }
            .table-custom td, .table-custom th {
                padding: 10px 8px;
            }
        }
          /* Estilos específicos para la columna de acciones */
        .actions-column {
            min-width: 240px !important;
            width: 240px !important;
        }
        
        .actions-column .btn-group {
            white-space: nowrap;
            flex-wrap: nowrap;
        }
        
        .actions-column .btn {
            flex-shrink: 0;
        }
        
        /* Mejor responsive para tablets */
        @media (min-width: 768px) and (max-width: 1199px) {
            .actions-column {
                min-width: 200px !important;
                width: 200px !important;
            }
            
            .table-custom .btn-group .btn {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
        }
          /* Para pantallas grandes */
        @media (min-width: 1200px) {
            .actions-column {
                min-width: 280px !important;
                width: 280px !important;
            }
        }
        
        /* Para pantallas extra grandes */
        @media (min-width: 1400px) {
            .actions-column {
                min-width: 320px !important;
                width: 320px !important;
            }
        }
        
        /* Spinner overlay global: centrado, z-index alto, cubre toda la pantalla */
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
    <!-- Spinner visual overlay -->
    <div id="mainSpinner">
      <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>
    
    <?php include 'navbar.php'; ?>

    <main class="container-fluid mt-5 pt-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 animate__animated animate__fadeInLeft">Gestión de Formularios</h1>
            <?php 
            if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['owner', 'admin', 'editor'])): ?>
            <button class="btn btn-primary btn-lg hvr-sweep-to-right animate__animated animate__pulse animate__delay-1s animate__infinite animate__slower" id="createNewFormBtn">
                <i class="fas fa-plus-circle me-2"></i>Crear Nuevo Formulario
            </button>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm mb-4 animate__animated animate__fadeInUp">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Mis Formularios</h5>
            </div>
            <div class="card-body">                <div class="table-responsive">
                    <table id="formsTable" class="table table-striped table-hover table-custom nowrap" style="width:100%">                        <thead class="table-dark">
                            <tr>
                                <!-- Columna oculta para el ID -->
                                <th style="display:none">ID</th>
                                <th>Título / Estado</th>
                                <th>Área</th>
                                <th>URL pública</th>
                                <th>Resp.</th>
                                <th>Creado</th>
                                <th>Caduca</th>
                                <th>Editor</th>
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
        
        <div id="chartsSection" class="card shadow-sm mb-4 animate__animated animate__fadeInUp" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Análisis y Respuestas: <span id="formAnalysisTitle"></span></h5>
                <button type="button" class="btn-close" aria-label="Close" id="closeChartsSection"></button>
            </div>
            <div class="card-body">
                <p class="text-muted">Formulario: "<span id="formAnalysisTitleContext"></span>".</p>
                
                <ul class="nav nav-tabs mb-3" id="analysisTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active hvr-underline-from-left" id="charts-tab" data-bs-toggle="tab" data-bs-target="#charts-tab-pane" type="button" role="tab" aria-controls="charts-tab-pane" aria-selected="true">
                            <i class="fas fa-chart-pie me-1"></i>Resumen Gráfico
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link hvr-underline-from-left" id="individual-responses-tab" data-bs-toggle="tab" data-bs-target="#individual-responses-tab-pane" type="button" role="tab" aria-controls="individual-responses-tab-pane" aria-selected="false">
                            <i class="fas fa-list-alt me-1"></i>Respuestas Individuales (<span id="responsesCountBadge" class="badge bg-secondary">0</span>)
                        </button>
                    </li>
                </ul>
                <div class="tab-content" id="analysisTabsContent">
                    <div class="tab-pane fade show active p-2" id="charts-tab-pane" role="tabpanel" aria-labelledby="charts-tab" tabindex="0">
                        <div class="row" id="chartsRenderContainer">
                            <p class="text-muted text-center col-12">Selecciona un formulario y respuestas para ver el análisis gráfico.</p>
                        </div>
                    </div>
                    <div class="tab-pane fade p-2" id="individual-responses-tab-pane" role="tabpanel" aria-labelledby="individual-responses-tab" tabindex="0">
                        <div id="individualResponsesTableContainer" class="table-responsive">
                            <p class="text-muted text-center">Cargando respuestas individuales...</p>
                        </div>
                         <div class="mt-3 text-center">
                            <button id="exportResponsesCsvBtn" class="btn btn-sm btn-outline-success hvr-grow disabled">
                                <i class="fas fa-file-csv me-2"></i>Exportar a CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <div class="modal fade" id="createEditFormModal" tabindex="-1" aria-labelledby="createEditFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"> 
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createEditFormModalLabel"><i class="fas fa-wpforms me-2"></i>Crear Nuevo Formulario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formBuilder">
                        <input type="hidden" id="formId" name="formId"> 
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="formTitle" class="form-label">Título del Formulario <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formTitle" name="formTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="formDescription" class="form-label">Descripción (opcional)</label>
                                    <textarea class="form-control" id="formDescription" name="formDescription" rows="2"></textarea>
                                </div>
                                <hr>
                                <h6 class="mt-3 mb-3 d-flex align-items-center">
                                    <i class="fas fa-tasks me-2"></i>Campos del Formulario
                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="toggleFieldsCompactBtn" title="Mostrar solo encabezados de campos"><i class="fas fa-layer-group"></i> Compactar</button>
                                </h6>
                                <div id="formFieldsContainer" class="mb-3 p-3 rounded border" style="min-height: 200px;">
                                    <p class="text-muted text-center" id="noFieldsMessage">Aún no hay campos. ¡Añade algunos desde el panel de la derecha!</p>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="sticky-top" style="top: 80px;"> 
                                    <h6 class="mt-3 mt-lg-0"><i class="fas fa-cog me-1"></i>Configuración General</h6>
                                    <div class="mb-3">
                                        <label for="companyName" class="form-label">Nombre de la Empresa</label>
                                        <input type="text" class="form-control form-control-sm" id="companyName" name="companyName">
                                    </div>                                    <div class="mb-3">
                                        <label for="logoUrl" class="form-label">URL del Logo</label>
                                        <input type="url" class="form-control form-control-sm" id="logoUrl" name="logoUrl" placeholder="https://ejemplo.com/logo.png">
                                    </div>
                                    <div class="mb-3">
                                        <label for="area_id" class="form-label">Área <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="area_id" name="area_id" required>
                                            <option value="">Seleccionar área...</option>
                                            <!-- Las áreas se cargarán dinámicamente con JavaScript -->
                                        </select>
                                        <div class="form-text text-muted small">El formulario pertenecerá a esta área</div>
                                    </div>
                                    <div class="mb-3"> 
                                        <label for="expirationDate" class="form-label">Fecha de Caducidad (opcional)</label>
                                        <input type="date" class="form-control form-control-sm" id="expirationDate" name="expirationDate">
                                    </div>
                                    <hr>
                                    <h6><i class="fas fa-plus-square me-2"></i>Añadir Campo</h6>
                                    <div class="list-group">
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="text"><i class="fas fa-font fa-fw me-2 text-primary"></i>Texto Corto</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="textarea"><i class="fas fa-paragraph fa-fw me-2 text-primary"></i>Párrafo</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="number"><i class="fas fa-hashtag fa-fw me-2 text-primary"></i>Número</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="email"><i class="fas fa-envelope fa-fw me-2 text-primary"></i>Correo Electrónico</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="tel"><i class="fas fa-phone fa-fw me-2 text-primary"></i>Número de Teléfono</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="date"><i class="fas fa-calendar-alt fa-fw me-2 text-primary"></i>Fecha</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="birthdate"><i class="fas fa-birthday-cake fa-fw me-2 text-info"></i>Fecha de Nacimiento</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="radio"><i class="fas fa-dot-circle fa-fw me-2 text-info"></i>Opción Múltiple</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="checkbox"><i class="fas fa-check-square fa-fw me-2 text-info"></i>Casillas</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="select"><i class="fas fa-list-ul fa-fw me-2 text-info"></i>Desplegable</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="image"><i class="fas fa-image fa-fw me-2 text-info"></i>Imagen</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="file"><i class="fas fa-upload fa-fw me-2 text-success"></i>Cargar Documento (usuario sube)</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="downloadable"><i class="fas fa-download fa-fw me-2 text-primary"></i>Descargar Documento (usuario descarga)</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="accept_only_terms"><i class="fas fa-check-circle fa-fw me-2 text-warning"></i>Aceptación de Términos (Solo Aceptar)</a>
                                        <a href="#" class="list-group-item list-group-item-action add-field-btn hvr-forward" data-type="terms"><i class="fas fa-file-contract fa-fw me-2 text-secondary"></i>Aceptación de Términos</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer mt-3">
                             <button type="button" class="btn btn-outline-secondary hvr-wobble-horizontal" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary hvr-sweep-to-right">
                                <i class="fas fa-save me-2"></i>Guardar Formulario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar el formulario "<strong id="formNameToDelete"></strong>"?</p>
                    <p class="text-danger">Esta acción no se puede deshacer y también eliminará todas las respuestas asociadas.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary hvr-wobble-horizontal" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger hvr-buzz-out" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>        </div>
    </div>

    <!-- Modal de Permisos Cruzados -->
    <div class="modal fade" id="manageCrossPermissionsModal" tabindex="-1" aria-labelledby="manageCrossPermissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="manageCrossPermissionsModalLabel">
                        <i class="fas fa-users-between-lines me-2"></i>Gestionar Permisos Cruzados Entre Áreas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Información del formulario -->
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Formulario:</strong> <span id="crossPermissionsFormTitle">-</span><br>
                            <small class="text-muted" id="crossPermissionsFormArea">Área: -</small>
                        </div>
                    </div>

                    <!-- Explicación -->
                    <div class="mb-4">
                        <h6><i class="fas fa-question-circle me-2"></i>¿Qué son los permisos cruzados?</h6>
                        <p class="text-muted small">
                            Los permisos cruzados permiten que editores de <strong>otras áreas</strong> puedan editar este formulario específico. 
                            Solo se pueden asignar a editores que NO pertenezcan al área del formulario.
                        </p>
                    </div>

                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="crossPermissionsAreaFilter" class="form-label">
                                <i class="fas fa-layer-group me-1"></i>Filtrar por Área
                            </label>
                            <select class="form-select" id="crossPermissionsAreaFilter">
                                <option value="">Todas las áreas</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="crossPermissionsUserFilter" class="form-label">
                                <i class="fas fa-search me-1"></i>Buscar Usuario
                            </label>
                            <input type="text" class="form-control" id="crossPermissionsUserFilter" placeholder="Nombre de usuario...">
                        </div>
                    </div>

                    <!-- Lista de editores disponibles -->
                    <div class="mb-4">
                        <h6><i class="fas fa-users me-2"></i>Editores Disponibles</h6>
                        <div id="crossPermissionsUsersList" class="row g-3">
                            <!-- Se llenarán dinámicamente -->
                        </div>
                    </div>

                    <!-- Permisos activos -->
                    <div class="mb-4">
                        <h6><i class="fas fa-user-check me-2"></i>Permisos Cruzados Activos</h6>
                        <div id="crossPermissionsActiveList" class="row g-2">
                            <!-- Se llenarán dinámicamente -->
                        </div>
                    </div>

                    <!-- Historial de permisos -->
                    <div class="mb-3">
                        <h6>
                            <i class="fas fa-history me-2"></i>Historial de Permisos
                            <button class="btn btn-sm btn-outline-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#crossPermissionsHistory" aria-expanded="false">
                                <i class="fas fa-chevron-down"></i> Ver Historial
                            </button>
                        </h6>
                        <div class="collapse" id="crossPermissionsHistory">
                            <div class="border rounded p-3">
                                <div id="crossPermissionsHistoryList">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cerrar
                    </button>
                    <button type="button" class="btn btn-warning" id="refreshCrossPermissionsBtn">
                        <i class="fas fa-sync-alt me-1"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

      <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header"> 
          <i class="fas fa-info-circle me-2 toast-icon"></i> 
          <strong class="me-auto toast-title">Notificación</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          Mensaje de la notificación.
        </div>
      </div>
      
      <!-- Toast específico para notificaciones de permisos RBAC -->
      <div id="rbacPermissionToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-primary text-white">
          <i class="fas fa-user-shield me-2"></i>
          <strong class="me-auto">Gestión de Permisos</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
        </div>
      </div>
    </div>

    <!-- Definición de currentUser global para JS -->
    <script>
        window.currentUser = {
            id: '<?php echo isset($_SESSION['user_id']) ? addslashes($_SESSION['user_id']) : ''; ?>',
            role: '<?php echo isset($_SESSION['user_role']) ? addslashes($_SESSION['user_role']) : ''; ?>',
            name: '<?php echo isset($_SESSION['user_name']) ? addslashes($_SESSION['user_name']) : ''; ?>',
            email: '<?php echo isset($_SESSION['user_email']) ? addslashes($_SESSION['user_email']) : ''; ?>'
        };
    </script>    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="js/admin_sortable.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/circle-corrector.js"></script>

</body>
</html>
