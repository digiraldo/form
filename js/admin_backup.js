/**
 * Sistema de gestión de backups
 * Manejo de exportación e importación de copias de seguridad
 */

// Variable global para almacenar los usuarios
let usersData = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeBackupSystem();
});

function initializeBackupSystem() {
    // Cargar usuarios primero, luego historial y estadísticas
    loadUsers().then(() => {
        loadBackupHistory();
        loadSystemStats();
        loadLocalBackups();
    });
    
    // Event listeners para botones
    document.getElementById('exportBtn').addEventListener('click', exportBackup);
    document.getElementById('importBtn').addEventListener('click', importBackup);
    document.getElementById('refreshBtn').addEventListener('click', refreshBackupData);
    
    // Event listener para input de archivo
    document.getElementById('backupFile').addEventListener('change', handleFileSelect);
}

/**
 * Cargar lista de usuarios del sistema
 */
async function loadUsers() {
    try {
        const response = await fetch('api/users.php?action=list');
        const result = await response.json();
        
        if (result.success) {
            usersData = result.data;
        } else {
            console.error('Error cargando usuarios:', result.message);
        }
        
    } catch (error) {
        console.error('Error cargando usuarios:', error);
    }
}

/**
 * Obtener nombre de usuario por ID
 */
function getUsernameById(userId) {
    const user = usersData.find(u => u.id === userId);
    return user ? user.username : userId; // Si no encuentra el usuario, devuelve el ID
}

/**
 * Exportar backup del sistema
 */
async function exportBackup() {
    const btn = document.getElementById('exportBtn');
    const progressContainer = document.getElementById('exportProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    
    try {
        // Deshabilitar botón y mostrar progreso
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando backup...';
        progressContainer.style.display = 'block';
        progressBar.style.width = '20%';
          const response = await fetch('api/backup.php?action=export', {
            method: 'POST'
        });
        
        progressBar.style.width = '60%';
        
        if (!response.ok) {
            // Si el response no es ok, intentar leer como JSON para obtener el error
            const errorData = await response.json();
            throw new Error(errorData.message || `Error HTTP: ${response.status}`);
        }
        
        progressBar.style.width = '80%';
        
        // Verificar si la respuesta es un archivo o JSON
        const contentType = response.headers.get('content-type');
        
        if (contentType && contentType.includes('application/zip')) {
            // Es un archivo ZIP, proceder con la descarga
            const blob = await response.blob();
            progressBar.style.width = '90%';
            
            // Obtener el nombre del archivo desde el header Content-Disposition
            const contentDisposition = response.headers.get('content-disposition');
            let filename = 'backup_formularios.zip';
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename="(.+)"/);
                if (filenameMatch) {
                    filename = filenameMatch[1];
                }
            }
            
            // Crear enlace de descarga
            const downloadLink = document.createElement('a');
            downloadLink.href = URL.createObjectURL(blob);
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Limpiar la URL del objeto
            URL.revokeObjectURL(downloadLink.href);
            
            progressBar.style.width = '100%';
            showAlert('success', 'Backup creado y descargado exitosamente: ' + filename);
            
        } else {
            // Es una respuesta JSON, probablemente un error
            const result = await response.json();
            throw new Error(result.message || 'Error desconocido al crear el backup');
        }          // Actualizar historial, estadísticas y backups locales
        setTimeout(() => {
            loadUsers().then(() => {
                loadBackupHistory();
                loadSystemStats();
                loadLocalBackups();
            });
        }, 1000);
        
    } catch (error) {
        console.error('Error exportando backup:', error);
        showAlert('danger', 'Error al crear el backup: ' + error.message);
        progressBar.classList.add('bg-danger');
    } finally {
        // Restaurar botón y ocultar progreso
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Exportar Backup';
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressBar.style.width = '0%';
            progressBar.classList.remove('bg-danger');
        }, 2000);
    }
}

/**
 * Importar backup del sistema
 */
async function importBackup() {
    const fileInput = document.getElementById('backupFile');
    const confirmCheck = document.getElementById('confirmImport');
    
    // Validaciones
    if (!fileInput.files || fileInput.files.length === 0) {
        showAlert('warning', 'Por favor selecciona un archivo de backup');
        return;
    }
    
    if (!confirmCheck.checked) {
        showAlert('warning', 'Debes confirmar que entiendes que esta acción reemplazará los datos actuales');
        return;
    }
    
    // Confirmación adicional
    if (!confirm('¿Estás seguro de que quieres restaurar este backup? Esta acción reemplazará TODOS los datos actuales y no se puede deshacer.')) {
        return;
    }
    
    const btn = document.getElementById('importBtn');
    const progressContainer = document.getElementById('importProgress');
    const progressBar = progressContainer.querySelector('.progress-bar');
    
    try {        // Preparar formulario
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('backup_file', fileInput.files[0]);
        
        // Deshabilitar botón y mostrar progreso
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restaurando backup...';
        progressContainer.style.display = 'block';
        progressBar.style.width = '20%';
        
        const response = await fetch('api/backup.php?action=import', {
            method: 'POST',
            body: formData
        });
        
        progressBar.style.width = '60%';
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            progressBar.style.width = '100%';
            showAlert('success', 'Backup restaurado exitosamente. Se creó un backup del estado anterior.');
            
            // Limpiar formulario
            fileInput.value = '';
            confirmCheck.checked = false;              // Actualizar datos
            setTimeout(() => {
                loadUsers().then(() => {
                    loadBackupHistory();
                    loadSystemStats();
                    loadLocalBackups();
                });
            }, 1000);
            
        } else {
            throw new Error(result.message || 'Error al restaurar el backup');
        }
        
    } catch (error) {
        console.error('Error importando backup:', error);
        showAlert('danger', 'Error al restaurar el backup: ' + error.message);
        progressBar.classList.add('bg-danger');
    } finally {
        // Restaurar botón y ocultar progreso
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload"></i> Importar Backup';
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressBar.style.width = '0%';
            progressBar.classList.remove('bg-danger');
        }, 2000);
    }
}

/**
 * Cargar historial de backups
 */
async function loadBackupHistory() {
    try {
        const response = await fetch('api/backup.php?action=history');
        const result = await response.json();
        
        if (result.success) {
            displayBackupHistory(result.data);
        } else {
            console.error('Error cargando historial:', result.message);
        }
        
    } catch (error) {
        console.error('Error cargando historial de backups:', error);
    }
}

/**
 * Cargar estadísticas del sistema
 */
async function loadSystemStats() {
    try {
        const response = await fetch('api/backup.php?action=stats');
        const result = await response.json();
        
        if (result.success) {
            displaySystemStats(result.data);
        } else {
            console.error('Error cargando estadísticas:', result.message);
        }
        
    } catch (error) {
        console.error('Error cargando estadísticas del sistema:', error);
    }
}

/**
 * Cargar lista de backups locales almacenados
 */
async function loadLocalBackups() {
    try {
        const response = await fetch('api/backup.php?action=list');
        const result = await response.json();
        
        if (result.success) {
            displayLocalBackups(result.data);
        } else {
            console.error('Error cargando backups locales:', result.message);
            document.getElementById('local-backups-container').innerHTML = 
                '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Error cargando backups locales</div>';
        }
        
    } catch (error) {
        console.error('Error cargando backups locales:', error);
        document.getElementById('local-backups-container').innerHTML = 
            '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error de conexión</div>';
    }
}

/**
 * Mostrar historial de backups en la tabla
 */
function displayBackupHistory(history) {
    const tbody = document.getElementById('backupHistoryTable');
    
    if (!history || history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay historial de backups disponible</td></tr>';
        return;
    }      tbody.innerHTML = history.map(backup => {
        const date = new Date(backup.created_at);
        const typeClass = backup.type === 'export' ? 'success' : (backup.type === 'delete' ? 'danger' : 'primary');
        const typeIcon = backup.type === 'export' ? 'download' : (backup.type === 'delete' ? 'trash' : 'upload');
        const typeName = backup.type === 'export' ? 'Exportar' : (backup.type === 'delete' ? 'Eliminar' : 'Importar');
        const size = backup.size ? formatFileSize(backup.size) : 'N/A';
        const username = getUsernameById(backup.created_by) || 'N/A';
        
        return `
            <tr>
                <td>${date.toLocaleString('es-ES')}</td>
                <td>${username}</td>
                <td>
                    <span class="badge bg-${typeClass}">
                        <i class="fas fa-${typeIcon}"></i> ${typeName}
                    </span>
                </td>
                <td>${size}</td>
                <td>
                    <span class="badge bg-${backup.status === 'success' ? 'success' : 'danger'}">
                        ${backup.status === 'success' ? 'Exitoso' : 'Error'}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Mostrar estadísticas del sistema
 */
function displaySystemStats(stats) {
    document.getElementById('totalFiles').textContent = stats.totalFiles || '0';
    document.getElementById('totalSize').textContent = formatFileSize(stats.totalSize || 0);
    document.getElementById('lastBackup').textContent = stats.lastBackup ? 
        new Date(stats.lastBackup).toLocaleString('es-ES') : 
        'Nunca';
}

/**
 * Mostrar lista de backups locales
 */
function displayLocalBackups(backups) {
    const container = document.getElementById('local-backups-container');
    
    if (!backups || backups.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay backups almacenados localmente</p>
                <small class="text-muted">Los backups creados se guardarán automáticamente aquí</small>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row g-3">';
    
    backups.forEach(backup => {
        html += `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h6 class="card-title text-truncate">
                            <i class="fas fa-file-archive text-primary me-2"></i>
                            ${backup.filename}
                        </h6>
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                ${backup.created_at}
                            </small>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-hdd me-1"></i>
                                ${formatFileSize(backup.size)}
                            </small>
                        </div>                        <div class="d-grid gap-2">
                            <a href="${backup.download_url}" 
                               class="btn btn-outline-primary btn-sm"
                               target="_blank">
                                <i class="fas fa-download me-1"></i>
                                Descargar
                            </a>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="deleteBackup('${backup.filename}')">
                                <i class="fas fa-trash me-1"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Manejar selección de archivo
 */
function handleFileSelect(event) {
    const file = event.target.files[0];
    const fileInfo = document.getElementById('fileInfo');
    
    if (file) {
        if (!file.name.endsWith('.zip')) {
            showAlert('warning', 'Solo se permiten archivos ZIP');
            event.target.value = '';
            return;
        }
        
        fileInfo.innerHTML = `
            <div class="small text-muted">
                <i class="fas fa-file-archive"></i> 
                ${file.name} (${formatFileSize(file.size)})
            </div>
        `;
        fileInfo.style.display = 'block';
    } else {
        fileInfo.style.display = 'none';
    }
}

/**
 * Refrescar datos de backup
 */
function refreshBackupData() {
    const btn = document.getElementById('refreshBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Cargar usuarios primero, luego el resto de datos
    loadUsers().then(() => {
        Promise.all([loadBackupHistory(), loadSystemStats(), loadLocalBackups()])
            .finally(() => {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }, 500);
            });
    });
}

/**
 * Mostrar alerta
 */
function showAlert(type, message) {
    // Remover alertas existentes
    const existingAlerts = document.querySelectorAll('.alert-dismissible');
    existingAlerts.forEach(alert => alert.remove());
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar al inicio del contenido principal
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alert, container.firstChild);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Eliminar backup local
 */
async function deleteBackup(filename) {
    // Confirmación del usuario
    if (!confirm(`¿Estás seguro de que quieres eliminar el backup "${filename}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    try {
        // Buscar el botón específico que se presionó para mostrar loading
        const deleteButtons = document.querySelectorAll('button[onclick*="' + filename + '"]');
        const deleteButton = deleteButtons[0];
        
        if (deleteButton) {
            deleteButton.disabled = true;
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminando...';
        }
        
        // Realizar petición DELETE
        const response = await fetch(`api/backup.php?action=delete&file=${encodeURIComponent(filename)}`, {
            method: 'GET'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            
            // Actualizar la lista de backups locales automáticamente
            await loadLocalBackups();
              // También actualizar historial y estadísticas
            await loadUsers();
            await loadBackupHistory();
            await loadSystemStats();
            
        } else {
            throw new Error(result.message || 'Error al eliminar el backup');
        }
        
    } catch (error) {
        console.error('Error eliminando backup:', error);
        showAlert('danger', 'Error al eliminar el backup: ' + error.message);
        
        // Restaurar botón en caso de error
        const deleteButtons = document.querySelectorAll('button[onclick*="' + filename + '"]');
        const deleteButton = deleteButtons[0];
        
        if (deleteButton) {
            deleteButton.disabled = false;
            deleteButton.innerHTML = '<i class="fas fa-trash me-1"></i>Eliminar';
        }
    }
}
