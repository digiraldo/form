<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación y rol de propietario
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
    !isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo el propietario puede realizar backups.']);
    exit;
}

// Configuración de directorios para backup
$backup_dirs = ['data', 'downloads', 'profile_images', 'uploads'];
$backup_base_dir = __DIR__ . '/../';
$temp_dir = sys_get_temp_dir();

// Función para crear un backup
function createBackup($backup_dirs, $backup_base_dir, $temp_dir) {
    try {
        // Crear nombre único para el backup
        $timestamp = date('Y-m-d_H-i-s');
        $backup_filename = "backup_formularios_rbac_{$timestamp}.zip";
        $backup_path = $temp_dir . '/' . $backup_filename;
        
        // Crear el archivo ZIP
        $zip = new ZipArchive();
        if ($zip->open($backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('No se pudo crear el archivo ZIP');
        }
        
        // Función recursiva para agregar archivos al ZIP
        function addDirectoryToZip($zip, $dir, $base_dir, $prefix = '') {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $file_path = $file->getRealPath();
                $relative_path = $prefix . substr($file_path, strlen($base_dir));
                
                if ($file->isDir()) {
                    $zip->addEmptyDir($relative_path);
                } else {
                    $zip->addFile($file_path, $relative_path);
                }
            }
        }
        
        // Agregar información del backup
        $backup_info = [
            'version' => '2.0',
            'created_by' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s'),
            'system' => 'Formularios Admin RBAC',
            'directories' => $backup_dirs,
            'php_version' => PHP_VERSION,
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
        
        $zip->addFromString('backup_info.json', json_encode($backup_info, JSON_PRETTY_PRINT));
        
        // Agregar cada directorio al backup
        foreach ($backup_dirs as $dir) {
            $full_dir_path = $backup_base_dir . $dir;
            if (is_dir($full_dir_path)) {
                addDirectoryToZip($zip, $full_dir_path, $backup_base_dir, '');
            }
        }
        
        $zip->close();
          // Verificar que el archivo fue creado correctamente
        if (!file_exists($backup_path)) {
            throw new Exception('El archivo de backup no fue creado correctamente');
        }
        
        // Guardar una copia en el directorio backups/
        $backups_dir = $backup_base_dir . 'backups/';
        if (!is_dir($backups_dir)) {
            mkdir($backups_dir, 0755, true);
        }
        
        $local_backup_path = $backups_dir . $backup_filename;
        if (!copy($backup_path, $local_backup_path)) {
            // No fallar si no se puede copiar, pero registrar el warning
            error_log("Warning: No se pudo guardar copia local del backup en {$local_backup_path}");
        }
        
        // Registrar el backup en el historial
        logBackupOperation('export', $backup_filename, filesize($backup_path), 'success');
        
        return [
            'success' => true,
            'filename' => $backup_filename,
            'path' => $backup_path,
            'size' => filesize($backup_path),
            'message' => 'Backup creado exitosamente'
        ];
        
    } catch (Exception $e) {
        logBackupOperation('export', 'Error', 0, 'error', $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al crear backup: ' . $e->getMessage()
        ];
    }
}

// Función para restaurar un backup
function restoreBackup($uploaded_file, $backup_base_dir, $temp_dir) {
    try {
        // Verificar que el archivo es un ZIP válido
        $zip = new ZipArchive();
        if ($zip->open($uploaded_file['tmp_name']) !== TRUE) {
            throw new Exception('El archivo no es un ZIP válido');
        }
        
        // Verificar que es un backup válido
        $backup_info_content = $zip->getFromName('backup_info.json');
        if ($backup_info_content === false) {
            $zip->close();
            throw new Exception('El archivo no es un backup válido del sistema');
        }
        
        $backup_info = json_decode($backup_info_content, true);
        if (!$backup_info || !isset($backup_info['system']) || $backup_info['system'] !== 'Formularios Admin RBAC') {
            $zip->close();
            throw new Exception('El backup no pertenece a este sistema');
        }
        
        // Crear backup temporal del estado actual
        $current_backup = createBackup(['data', 'downloads', 'profile_images', 'uploads'], $backup_base_dir, $temp_dir);
        if (!$current_backup['success']) {
            $zip->close();
            throw new Exception('No se pudo crear backup de seguridad del estado actual');
        }
        
        // Extraer el backup a un directorio temporal
        $extract_dir = $temp_dir . '/backup_restore_' . uniqid();
        if (!mkdir($extract_dir, 0755, true)) {
            $zip->close();
            throw new Exception('No se pudo crear el directorio temporal');
        }
        
        if (!$zip->extractTo($extract_dir)) {
            $zip->close();
            rmdir($extract_dir);
            throw new Exception('No se pudo extraer el archivo de backup');
        }
        $zip->close();
        
        // Función para copiar recursivamente
        function copyDirectory($src, $dst) {
            if (!is_dir($src)) return false;
            
            if (!is_dir($dst)) {
                mkdir($dst, 0755, true);
            }
            
            $dir = opendir($src);
            while (($file = readdir($dir)) !== false) {
                if ($file != '.' && $file != '..') {
                    $src_file = $src . '/' . $file;
                    $dst_file = $dst . '/' . $file;
                    
                    if (is_dir($src_file)) {
                        copyDirectory($src_file, $dst_file);
                    } else {
                        copy($src_file, $dst_file);
                    }
                }
            }
            closedir($dir);
            return true;
        }
        
        // Función para eliminar recursivamente
        function removeDirectory($dir) {
            if (!is_dir($dir)) return;
            
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $file_path = $dir . '/' . $file;
                if (is_dir($file_path)) {
                    removeDirectory($file_path);
                } else {
                    unlink($file_path);
                }
            }
            rmdir($dir);
        }
        
        // Restaurar cada directorio
        foreach ($backup_info['directories'] as $dir) {
            $src_dir = $extract_dir . '/' . $dir;
            $dst_dir = $backup_base_dir . $dir;
            
            if (is_dir($src_dir)) {
                // Eliminar directorio actual si existe
                if (is_dir($dst_dir)) {
                    removeDirectory($dst_dir);
                }
                
                // Copiar desde el backup
                if (!copyDirectory($src_dir, $dst_dir)) {
                    throw new Exception("No se pudo restaurar el directorio: $dir");
                }
            }
        }
        
        // Limpiar directorio temporal
        removeDirectory($extract_dir);
        
        // Registrar la operación
        logBackupOperation('import', $uploaded_file['name'], $uploaded_file['size'], 'success');
        
        return [
            'success' => true,
            'message' => 'Backup restaurado exitosamente',
            'backup_info' => $backup_info,
            'current_backup' => $current_backup['filename']
        ];
        
    } catch (Exception $e) {
        logBackupOperation('import', $uploaded_file['name'] ?? 'Unknown', $uploaded_file['size'] ?? 0, 'error', $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al restaurar backup: ' . $e->getMessage()
        ];
    }
}

// Función para registrar operaciones de backup
function logBackupOperation($type, $filename, $size, $status, $error_message = null) {
    $log_file = __DIR__ . '/../data/backup_history.json';
    
    // Cargar historial existente
    $history = [];
    if (file_exists($log_file)) {
        $history = json_decode(file_get_contents($log_file), true) ?: [];
    }
    
    // Agregar nueva entrada
    $entry = [
        'id' => uniqid(),
        'type' => $type,
        'filename' => $filename,
        'size' => $size,
        'status' => $status,
        'created_by' => $_SESSION['user_id'],
        'created_at' => date('Y-m-d H:i:s'),
        'error_message' => $error_message
    ];
    
    array_unshift($history, $entry);
    
    // Mantener solo los últimos 50 registros
    $history = array_slice($history, 0, 50);
    
    // Guardar historial
    file_put_contents($log_file, json_encode($history, JSON_PRETTY_PRINT));
}

// Función para obtener estadísticas del sistema
function getSystemStats($backup_dirs, $backup_base_dir) {
    $totalFiles = 0;
    $totalSize = 0;
    $stats = [];
    
    foreach ($backup_dirs as $dir) {
        $full_path = $backup_base_dir . $dir;
        if (is_dir($full_path)) {
            $size = 0;
            $files = 0;
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($full_path, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $files++;
                }
            }
            
            $stats[$dir] = [
                'size' => $size,
                'files' => $files,
                'size_formatted' => formatBytes($size)
            ];
            
            $totalFiles += $files;
            $totalSize += $size;
        }
    }
    
    // Obtener fecha del último backup
    $lastBackup = null;
    $log_file = __DIR__ . '/../data/backup_history.json';
    if (file_exists($log_file)) {
        $history = json_decode(file_get_contents($log_file), true);
        if ($history && count($history) > 0) {
            // Ordenar por fecha más reciente
            usort($history, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            $lastBackup = $history[0]['created_at'];
        }
    }
    
    return [
        'totalFiles' => $totalFiles,
        'totalSize' => $totalSize,
        'lastBackup' => $lastBackup,
        'directories' => $stats
    ];
}

// Función para formatear bytes
function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// Procesar la solicitud según el método y acción
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'export':
                $result = createBackup($backup_dirs, $backup_base_dir, $temp_dir);
                
                if ($result['success']) {
                    // Configurar headers para descarga
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
                    header('Content-Length: ' . $result['size']);
                    header('Cache-Control: no-cache, must-revalidate');
                    
                    // Enviar el archivo
                    readfile($result['path']);
                    
                    // Eliminar archivo temporal
                    unlink($result['path']);
                    exit;
                } else {
                    http_response_code(500);
                    echo json_encode($result);
                }
                break;
                
            case 'import':
                if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'No se recibió un archivo válido']);
                    exit;
                }
                
                $result = restoreBackup($_FILES['backup_file'], $backup_base_dir, $temp_dir);
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'history':
                $log_file = __DIR__ . '/../data/backup_history.json';
                $history = [];
                if (file_exists($log_file)) {
                    $history = json_decode(file_get_contents($log_file), true) ?: [];
                }
                echo json_encode(['success' => true, 'data' => $history]);
                break;
                  case 'stats':
                $stats = getSystemStats($backup_dirs, $backup_base_dir);
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            case 'list':
                $backups_dir = $backup_base_dir . 'backups/';
                $local_backups = [];
                
                if (is_dir($backups_dir)) {
                    $files = glob($backups_dir . 'backup_formularios_rbac_*.zip');
                    foreach ($files as $file) {
                        $filename = basename($file);
                        $local_backups[] = [
                            'filename' => $filename,
                            'size' => filesize($file),
                            'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                            'download_url' => 'api/backup.php?action=download&file=' . urlencode($filename)
                        ];
                    }
                    
                    // Ordenar por fecha de creación (más reciente primero)
                    usort($local_backups, function($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                }
                
                echo json_encode(['success' => true, 'data' => $local_backups]);
                break;
                
            case 'download':
                $filename = $_GET['file'] ?? '';
                if (empty($filename)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nombre de archivo requerido']);
                    exit;
                }
                
                // Validar que es un archivo de backup válido
                if (!preg_match('/^backup_formularios_rbac_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nombre de archivo no válido']);
                    exit;
                }
                
                $backup_file = $backup_base_dir . 'backups/' . $filename;
                if (!file_exists($backup_file)) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Archivo de backup no encontrado']);
                    exit;
                }
                
                // Configurar headers para descarga
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($backup_file));
                header('Cache-Control: no-cache, must-revalidate');                // Enviar el archivo
                readfile($backup_file);
                exit;
                  case 'delete':
                $filename = $_GET['file'] ?? '';
                if (empty($filename)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nombre de archivo requerido']);
                    exit;
                }
                
                // Validar que es un archivo de backup válido
                if (!preg_match('/^backup_formularios_rbac_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nombre de archivo no válido']);
                    exit;
                }
                
                $backup_file = $backup_base_dir . 'backups/' . $filename;
                if (!file_exists($backup_file)) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Archivo de backup no encontrado']);
                    exit;
                }
                
                try {
                    // Eliminar archivo físico
                    if (!unlink($backup_file)) {
                        throw new Exception('No se pudo eliminar el archivo de backup');
                    }
                    
                    // Eliminar del historial
                    $log_file = __DIR__ . '/../data/backup_history.json';
                    if (file_exists($log_file)) {
                        $history = json_decode(file_get_contents($log_file), true) ?: [];
                        $updated_history = array_filter($history, function($backup) use ($filename) {
                            return $backup['filename'] !== $filename;
                        });
                        
                        // Reindexar el array para mantener orden
                        $updated_history = array_values($updated_history);
                        file_put_contents($log_file, json_encode($updated_history, JSON_PRETTY_PRINT));
                    }
                    
                    // Registrar la operación de eliminación
                    logBackupOperation('delete', $filename, 0, 'success');
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => "Backup '{$filename}' eliminado exitosamente"
                    ]);
                    
                } catch (Exception $e) {
                    logBackupOperation('delete', $filename, 0, 'error', $e->getMessage());
                    http_response_code(500);
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Error al eliminar backup: ' . $e->getMessage()
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
