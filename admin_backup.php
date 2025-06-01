<?php
session_start();

// Verificar que el usuario esté autenticado y sea propietario
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
    !isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario actual
$current_user = null;
if (file_exists('data/users.json')) {
    $users = json_decode(file_get_contents('data/users.json'), true);
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            $current_user = $user;
            break;
        }
    }
}

if (!$current_user) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Backups - Sistema de Formularios</title>
    <?php include 'header_includes.php'; ?>
</head>
<body class="">
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h3 mb-0">
                            <i class="fas fa-shield-alt text-primary me-2"></i>
                            Gestión de Backups del Sistema
                        </h2>
                        <p class="text-muted mb-0">Exportar e importar copias de seguridad completas del sistema</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="fas fa-crown me-1"></i>Solo Propietario
                        </span>
                    </div>
                </div>

                <!-- Alertas -->
                <div id="backup-alerts"></div>

                <!-- Cards principales -->
                <div class="row g-4">
                    <!-- Card Exportar Backup -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-download me-2"></i>
                                    Exportar Backup
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    Crea una copia de seguridad completa del sistema incluyendo:
                                </p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-database text-primary me-2"></i> <strong>data/</strong> - Base de datos JSON</li>
                                    <li><i class="fas fa-file-download text-info me-2"></i> <strong>downloads/</strong> - Archivos públicos</li>
                                    <li><i class="fas fa-user-circle text-warning me-2"></i> <strong>profile_images/</strong> - Avatares de usuarios</li>
                                    <li><i class="fas fa-cloud-upload-alt text-secondary me-2"></i> <strong>uploads/</strong> - Archivos subidos</li>
                                </ul>
                                
                                <div class="mt-4">                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-success btn-lg" id="exportBtn">
                                            <i class="fas fa-download me-2"></i>
                                            Crear y Descargar Backup
                                        </button>
                                    </div>
                                    <div class="progress mt-3 d-none" id="exportProgress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    El archivo ZIP se descargará automáticamente
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Card Importar Backup -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-upload me-2"></i>
                                    Importar Backup
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>¡Advertencia!</strong> Esta acción sobrescribirá todos los datos actuales del sistema.
                                </div>

                                <form id="import-backup-form" enctype="multipart/form-data">                                    <div class="mb-3">
                                        <label for="backupFile" class="form-label">
                                            <i class="fas fa-file-archive me-1"></i>
                                            Seleccionar archivo de backup (.zip)
                                        </label>
                                        <input type="file" 
                                               class="form-control" 
                                               id="backupFile" 
                                               name="backup_file" 
                                               accept=".zip"
                                               required>
                                        <div class="form-text">
                                            Solo archivos ZIP creados por este sistema
                                        </div>
                                        <div id="fileInfo" class="mt-2" style="display: none;"></div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="confirmImport" 
                                               required>
                                        <label class="form-check-label text-danger" for="confirmImport">
                                            <strong>Confirmo que entiendo que esta acción es irreversible</strong>
                                        </label>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-warning btn-lg" id="importBtn">
                                            <i class="fas fa-upload me-2"></i>
                                            Restaurar Backup
                                        </button>
                                    </div>
                                    
                                    <div class="progress mt-3 d-none" id="importProgress">
                                        <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    El proceso puede tomar varios minutos
                                </small>
                            </div>
                        </div>
                    </div>
                </div>                <!-- Historial de Backups -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    Historial de Backups
                                </h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="refreshBtn">
                                    <i class="fas fa-sync-alt me-1"></i>
                                    Actualizar
                                </button>
                            </div>
                            <div class="card-body">                                <div class="table-responsive">
                                    <table class="table table-striped" id="backupHistoryTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-calendar me-1"></i>Fecha</th>
                                                <th><i class="fas fa-user me-1"></i>Creado por</th>
                                                <th><i class="fas fa-database me-1"></i>Tipo</th>
                                                <th><i class="fas fa-hdd me-1"></i>Tamaño</th>
                                                <th><i class="fas fa-info-circle me-1"></i>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody id="backup-history-tbody">
                                            <!-- Se carga dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Sistema -->
                <div class="row mt-4">
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estadísticas del Sistema
                                </h6>
                            </div>                            <div class="card-body">
                                <div id="system-stats">
                                    <div class="row text-center">
                                        <div class="col-12 mb-3">
                                            <h6 class="text-muted mb-1">Total de Archivos</h6>
                                            <span class="h5 text-primary" id="totalFiles">-</span>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <h6 class="text-muted mb-1">Tamaño Total</h6>
                                            <span class="h5 text-info" id="totalSize">-</span>
                                        </div>
                                        <div class="col-12">
                                            <h6 class="text-muted mb-1">Último Backup</h6>
                                            <span class="h6 text-secondary" id="lastBackup">Nunca</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-folder-tree me-2"></i>
                                    Estructura de Backup
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-database text-primary"></i> data/</h6>
                                        <ul class="list-unstyled ms-3">
                                            <li>• users.json</li>
                                            <li>• areas.json</li>
                                            <li>• forms/ (formularios)</li>
                                            <li>• responses/ (respuestas)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6><i class="fas fa-file-download text-info"></i> downloads/</h6>
                                        <ul class="list-unstyled ms-3">
                                            <li>• download_urls.json</li>
                                            <li>• Archivos públicos</li>
                                        </ul>
                                        
                                        <h6 class="mt-3"><i class="fas fa-user-circle text-warning"></i> profile_images/</h6>
                                        <ul class="list-unstyled ms-3">
                                            <li>• Avatares de usuarios</li>
                                        </ul>
                                        
                                        <h6 class="mt-3"><i class="fas fa-cloud-upload-alt text-secondary"></i> uploads/</h6>
                                        <ul class="list-unstyled ms-3">
                                            <li>• Archivos subidos</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Backups Locales Disponibles -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-archive me-2"></i>
                                    Backups Almacenados Localmente
                                </h5>
                                <small class="text-muted">
                                    <i class="fas fa-folder me-1"></i>
                                    Directorio: /backups/
                                </small>
                            </div>
                            <div class="card-body">
                                <div id="local-backups-container">
                                    <div class="text-center py-3">
                                        <i class="fas fa-spinner fa-spin text-muted"></i>
                                        <p class="text-muted mt-2">Cargando backups locales...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="js/admin_backup.js"></script>
</body>
</html>
