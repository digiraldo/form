// admin.js

// --- Definición segura de currentUser global ---
if (typeof currentUser === 'undefined') {
    window.currentUser = {};
}

document.addEventListener('DOMContentLoaded', function () {
    // --- Modals & Toast ---
    const createEditFormModalEl = document.getElementById('createEditFormModal');
    const createEditFormModal = createEditFormModalEl ? new bootstrap.Modal(createEditFormModalEl) : null;
    const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
    const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
      
    // Función para cargar las áreas en el selector del formulario
    function loadAvailableAreas(selectElement, callback) {
        if (!selectElement) return;
        
        // Mostrar indicador de carga
        selectElement.innerHTML = '<option value="">Cargando áreas...</option>';
        
        fetch('api/areas_list_available.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.areas)) {
                    // Almacenar áreas globalmente para uso en otras partes
                    window.formAreas = {};
                    data.areas.forEach(area => {
                        window.formAreas[area.id] = area;
                    });
                      // Generar las opciones del selector
                    selectElement.innerHTML = '<option value="">Seleccionar área...</option>';
                    
                    // Función para aplicar estilos al selector con colores
                    const applyColorStyles = () => {
                        const select = selectElement;
                        
                        // Crear un wrapper con estilos para el select
                        if (!select.parentNode.classList.contains('area-select-wrapper')) {
                            const wrapper = document.createElement('div');
                            wrapper.classList.add('area-select-wrapper', 'position-relative');
                            select.parentNode.insertBefore(wrapper, select);
                            wrapper.appendChild(select);
                            
                            // Añadir icono decorativo
                            const icon = document.createElement('div');
                            icon.classList.add('area-select-icon');
                            icon.innerHTML = '<i class="fas fa-layer-group"></i>';
                            wrapper.appendChild(icon);
                        }
                    };
                    
                    // Mejorar las opciones con colores
                    data.areas.forEach(area => {
                        const option = document.createElement('option');
                        option.value = area.id;
                        option.textContent = area.name;
                        option.dataset.color = area.color || '#ECF0F1';
                        option.style.backgroundColor = area.color || '#ECF0F1';
                        option.style.color = getContrastColor(area.color || '#ECF0F1');
                        option.style.borderLeft = `4px solid ${area.color || '#ECF0F1'}`;
                        selectElement.appendChild(option);
                    });
                    
                    // Aplicar estilos al selector
                    applyColorStyles();
                    
                    // Si no hay áreas disponibles, mostrar mensaje claro y deshabilitar el botón de envío
                    if (data.areas.length === 0) {
                        selectElement.innerHTML = '<option value="">No hay áreas disponibles</option>';
                        selectElement.disabled = true;
                        
                        // Mostrar un mensaje más descriptivo
                        const submitBtn = document.querySelector('#formBuilder button[type="submit"]');
                        if (submitBtn) submitBtn.disabled = true;
                        
                        // Determinar mensaje según el rol
                        let roleName = '';
                        let message = '';
                        if (window.currentUser && window.currentUser.role) {
                            switch(window.currentUser.role) {
                                case 'admin':
                                    roleName = 'Administrador';
                                    message = 'No tienes áreas asignadas. Contacta con el propietario del sistema para que te asigne áreas en el panel de administración.';
                                    break;
                                case 'editor':
                                    roleName = 'Editor';
                                    message = 'No tienes áreas asignadas. Contacta con tu administrador para que te asigne áreas en tu perfil.';
                                    break;
                                default:
                                    roleName = 'Usuario';
                                    message = 'No hay áreas disponibles. Contacta con el administrador del sistema.';
                            }
                        }
                        
                        showRbacPermissionToast(`<strong>${roleName}:</strong> ${message}`, 'warning');
                        
                        // Añadir mensaje directamente bajo el select
                        const helpText = document.createElement('div');
                        helpText.className = 'alert alert-warning mt-2 mb-0 py-2';
                        helpText.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${message}`;
                        selectElement.parentNode.appendChild(helpText);
                    }
                    
                    // Ejecutar callback si se proporciona
                    if (typeof callback === 'function') {
                        callback();
                    }
                } else {
                    selectElement.innerHTML = '<option value="">Error al cargar áreas</option>';
                    console.error('Error al cargar áreas:', data.error || 'Respuesta inválida del servidor');
                }
            })
            .catch(error => {
                selectElement.innerHTML = '<option value="">Error al cargar áreas</option>';
                console.error('Error al cargar áreas:', error);
            });
    }
    
    // Agregar event listener para el botón de crear nuevo formulario
    const createNewFormBtn = document.getElementById('createNewFormBtn');
    if (createNewFormBtn) {
        createNewFormBtn.addEventListener('click', function() {
            // Resetear el formulario
            if (document.getElementById('formBuilder')) {
                document.getElementById('formBuilder').reset();
                document.getElementById('formId').value = ''; // Limpiar el ID para indicar que es un nuevo formulario
            }
            
            // Cambiar el título del modal a "Crear Nuevo Formulario"
            if (document.getElementById('createEditFormModalLabel')) {
                document.getElementById('createEditFormModalLabel').innerHTML = '<i class="fas fa-wpforms me-2"></i>Crear Nuevo Formulario';
            }
            
            // Limpiar el contenedor de campos
            const formFieldsContainer = document.getElementById('formFieldsContainer');
            if (formFieldsContainer) {
                formFieldsContainer.innerHTML = '<p class="text-muted text-center" id="noFieldsMessage">Aún no hay campos. ¡Añade algunos desde el panel de la derecha!</p>';
            }
            
            // Cargar áreas disponibles
            const areaSelector = document.getElementById('area_id');
            if (areaSelector) {
                loadAvailableAreas(areaSelector);
            }
            
            // Mostrar el modal
            if (createEditFormModal) {
                createEditFormModal.show();
            }
        });
    }
    const notificationToastEl = document.getElementById('notificationToast');
    const rbacPermissionToastEl = document.getElementById('rbacPermissionToast');
    const notificationToast = notificationToastEl ? new bootstrap.Toast(notificationToastEl, { delay: 3500 }) : null;
    const rbacPermissionToast = rbacPermissionToastEl ? new bootstrap.Toast(rbacPermissionToastEl, { delay: 5000 }) : null;
    
    function showToast(message, isError = false, allowHtml = true) {
        if (notificationToast) {
            const toastBody = notificationToastEl.querySelector('.toast-body');
            const toastHeader = notificationToastEl.querySelector('.toast-header');
            const toastIcon = notificationToastEl.querySelector('.toast-header i');

            if (toastBody) {
                if (allowHtml) {
                    toastBody.innerHTML = message;
                } else {
                    toastBody.textContent = message;
                }
            }
            
            toastHeader.classList.remove('text-success', 'text-danger');
            if(toastIcon) toastIcon.classList.remove('fa-check-circle', 'text-success', 'fa-exclamation-triangle', 'text-danger', 'fa-info-circle');
            notificationToastEl.classList.remove('border-danger', 'border-success');

            if (isError) {
                toastHeader.classList.add('text-danger');
                if(toastIcon) toastIcon.classList.add('fa-exclamation-triangle', 'text-danger');
                notificationToastEl.classList.add('border-danger');
            } else {
                toastHeader.classList.add('text-success');
                 if(toastIcon) toastIcon.classList.add('fa-check-circle', 'text-success');
                notificationToastEl.classList.add('border-success');
            }
            notificationToast.show();

            notificationToastEl.addEventListener('hidden.bs.toast', () => {
                if(toastIcon) {
                    toastIcon.classList.remove('fa-check-circle', 'text-success', 'fa-exclamation-triangle', 'text-danger');
                    toastIcon.classList.add('fa-info-circle'); 
                }
                toastHeader.classList.remove('text-success', 'text-danger');
                notificationToastEl.classList.remove('border-danger', 'border-success');
            }, { once: true });
        }
    }
    
    // Función específica para notificaciones de permisos RBAC
    function showRbacPermissionToast(message, type = 'info', options = {}) {
        if (rbacPermissionToast) {
            const toastBody = rbacPermissionToastEl.querySelector('.toast-body');
            const toastHeader = rbacPermissionToastEl.querySelector('.toast-header');
            const toastIcon = rbacPermissionToastEl.querySelector('.toast-header i');
            
            // Configurar el cuerpo del toast
            if (toastBody) {
                toastBody.innerHTML = message;
            }
            
            // Resetear clases
            toastHeader.className = 'toast-header';
            if (toastIcon) {
                toastIcon.className = 'fas me-2';
            }
            
            // Aplicar estilo según el tipo
            switch (type) {
                case 'success':
                    toastHeader.classList.add('bg-success', 'text-white');
                    if (toastIcon) toastIcon.classList.add('fa-check-circle');
                    rbacPermissionToastEl.classList.add('rbac-toast-success');
                    break;
                case 'error':
                    toastHeader.classList.add('bg-danger', 'text-white');
                    if (toastIcon) toastIcon.classList.add('fa-exclamation-triangle');
                    break;
                case 'warning':
                    toastHeader.classList.add('bg-warning', 'text-dark');
                    if (toastIcon) toastIcon.classList.add('fa-exclamation-circle');
                    break;
                default: // info
                    toastHeader.classList.add('bg-primary', 'text-white');
                    if (toastIcon) toastIcon.classList.add('fa-user-shield');
            }
            
            // Mostrar toast
            rbacPermissionToast.show();
            
            // Limpiar clases después de ocultar
            rbacPermissionToastEl.addEventListener('hidden.bs.toast', () => {
                rbacPermissionToastEl.classList.remove('rbac-toast-success');
            }, { once: true });
        }
    }

    // --- DataTables Initialization for Forms List ---
    let formsTable; 
    // currentUser es definido en admin_dashboard.php mediante un script tag
    // console.log("Current User for JS (admin.js):", currentUser); 

    // --- Cargar todos los usuarios antes de inicializar DataTables ---
    function loadAllUsersForTable(callback) {
        fetch('api/users.php?action=list')
            .then(res => res.json())
            .then(data => { // Corregido: se agregaron paréntesis alrededor de 'data'
                if (data.success && Array.isArray(data.data)) {
                    window.usersById = {};
                    data.data.forEach(u => {
                        window.usersById[u.id] = {
                            username: u.username,
                            role: u.role,
                            // Solo asignar profile_image si realmente existe una imagen válida
                            profile_image: (u.profile_image_url && !u.profile_image_url.includes('default.png')) ? u.profile_image_url : (u.profile_image && !u.profile_image.includes('default.png') ? u.profile_image : null)
                        };
                    });
                } else {
                    window.usersById = {};
                }
                if (typeof callback === 'function') callback();
            })
            .catch((error) => {
                console.error("Error cargando usuarios:", error);
                window.usersById = {};
                if (typeof callback === 'function') callback();
            });
    }

    // Refactorización: Inicialización de DataTables sin extensión Responsive
    if (document.getElementById('formsTable')) { // <--- AÑADIDO: Comprobar si la tabla de formularios existe
        showMainSpinner(); // Mostrar spinner antes de cargar usuarios y DataTable
        loadAllUsersForTable(function() {
            if (typeof $ !== 'undefined' && $.fn.DataTable && typeof currentUser !== 'undefined' && currentUser && currentUser.role) {
                try {
                    if ($.fn.DataTable.isDataTable('#formsTable')) {
                        $('#formsTable').DataTable().destroy(true);
                    }                    formsTable = $('#formsTable').DataTable({
                        processing: true,
                        ajax: {
                            url: 'api/forms.php?action=list',
                            dataSrc: function(json) {
                                // Asegurarse de que los datos están disponibles
                                return json.data || [];
                            },
                            error: function(xhr, error, thrown) {
                                console.error("Error cargando datos para DataTables:", error, thrown);
                                showToast(`Error al cargar formularios: ${xhr.responseJSON?.message || thrown || error}`, true);
                                hideMainSpinner();
                            }
                        },
                        columnDefs: [
                            { targets: 0, visible: false, data: 'id' }, // ID del formulario, oculto
                            {
                                targets: 1, // Corresponde a la columna "Título / Estado"
                                data: 'title',
                                className: '', // Mantener o ajustar según sea necesario
                                render: function(data, type, row) {
                                    // 1. Insignia de recuento de campos
                                    const fieldCount = Array.isArray(row.fields) ? row.fields.length : 0;
                                    const fieldCountText = `${fieldCount} Campo${fieldCount !== 1 ? 's' : ''}`;
                                    const fieldCountBadge = `<span class="badge bg-info me-2" data-bs-toggle="tooltip" data-bs-placement="top" title="${fieldCountText}">${fieldCount}</span>`;

                                    // 2. Título del formulario
                                    const revisedTitle = data || 'Formulario sin título';
                                    const titleWithTooltip = `<div class="fw-bold form-title" data-bs-toggle="tooltip" data-bs-placement="top" title="ID del Formulario: ${row.id}">${revisedTitle}</div>`;

                                    // 3. Combinar elementos
                                    return `<div class="d-flex align-items-center">
                                                ${fieldCountBadge}
                                                ${titleWithTooltip}
                                            </div>`;
                                }
                            },                            // 2. Área
                            {
                                targets: 2,
                                data: 'area_name',
                                className: 'text-center area-column-badge',
                                render: function(data, type, row) {
                                    if (!data) return '<span class="badge bg-secondary">Sin área</span>';
                                    
                                    // Usar el color que viene directamente de la API
                                    const areaColor = row.area_color || '#6c757d'; // Color gris por defecto
                                    const contrastColor = getContrastColor(areaColor);
                                    
                                    return `<span class="area-badge" 
                                               data-auto-contrast="true" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Área: ${data}" 
                                               style="background-color: ${areaColor}; color: ${contrastColor};">
                                               <i class="fas fa-layer-group me-1"></i>${data}
                                           </span>`;
                                }
                            },                            // 3. URL pública
                            {
                                targets: 3,
                                data: 'public_url',
                                className: 'text-center',
                                render: function(data, type, row) {
                                    if (!data) return '<span class="text-muted small fst-italic">N/A</span>';
                                    
                                    const fullUrl = `${window.location.origin}${window.location.pathname.replace('admin_dashboard.php', '')}${data}`;
                                    
                                    return `<div class="btn-group" role="group" aria-label="Acciones de URL">
                                        <button class="btn btn-outline-primary btn-sm rounded-circle me-1 open-form-btn" 
                                                data-url="${fullUrl}" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Abrir formulario en nueva pestaña"
                                                style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-external-link-alt"></i>
                                        </button>
                                        <button class="btn btn-outline-success btn-sm rounded-circle copy-url-btn" 
                                                data-url="${fullUrl}" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Copiar URL del formulario"
                                                style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>`;
                                }
                            },
                            // 4. Respuestas (contador)
                            {
                                targets: 4,
                                data: 'responses_count',
                                className: 'text-center',
                                render: function(data, type, row) {
                                    const count = data !== undefined ? data : 0;
                                    return `<span class="badge bg-primary fs-6 px-3 py-2 shadow-sm" data-bs-toggle="tooltip" title="${count} Respuesta${count === 1 ? '' : 's'}">${count}</span>`;
                                }
                            },
                            // 5. Creado
                            {
                                targets: 5,
                                data: 'created_at',
                                className: 'text-center',
                                render: function(data, type, row) {
                                    try {
                                        return data ? new Date(data).toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' }) : 'N/A';
                                    } catch (e) { return data; }
                                }
                            },
                            // 6. Caduca
                            {
                                targets: 6,
                                data: 'expiration_date',
                                className: 'text-center',
                                render: function(data, type, row) {
                                    if (!data) {
                                        return '<span class="badge bg-secondary">N/A</span>';
                                    }
                                    const parts = data.split('-');
                                    const expirationDate = new Date(parts[0], parts[1] - 1, parts[2]);
                                    expirationDate.setHours(0,0,0,0);
                                    const today = new Date();
                                    today.setHours(0,0,0,0);
                                    const oneDay = 24 * 60 * 60 * 1000;
                                    const diffDays = Math.round((expirationDate.getTime() - today.getTime()) / oneDay);
                                    let displayText = expirationDate.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
                                    if (diffDays < 0) {
                                        return `<span class="badge bg-danger" title="Expirado el ${displayText}">${displayText} (Expirado)</span>`;
                                    } else if (diffDays <= 5) {
                                        let statusText = `Expira en ${diffDays} día(s)`;
                                        if (diffDays === 0) {
                                            statusText = "Expira hoy";
                                        }
                                        return `<span class="badge bg-warning text-dark" data-bs-toggle="tooltip" data-bs-placement="top" title="${statusText} (${displayText})">${displayText}</span>`;
                                    } else {
                                        return `<span class="badge bg-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Vigente hasta ${displayText}">${displayText}</span>`;
                                    }
                                }
                            },                            // 7. Editor (imagen con información completa en tooltip)
                            {
                                targets: 7,
                                data: 'updated_id',
                                className: 'text-center',
                                orderable: false,
                                render: function(data, type, row) {
                                    if (!data || !window.usersById) return '<span class="text-muted">Desconocido</span>';
                                    const user = window.usersById[data];
                                    if (!user) return '<span class="text-muted">Desconocido</span>';
                                    
                                    let borderColorClass = 'border-secondary'; // Color de borde por defecto (gris)
                                    const userRole = user.role ? user.role.toLowerCase() : '';

                                    // Lógica de color de borde basada en el ROL del usuario que actualizó (updated_id)
                                    switch(userRole) {
                                        case 'owner':
                                            borderColorClass = 'border-success'; // Verde para Owner
                                            break;
                                        case 'admin':
                                            borderColorClass = 'border-primary'; // Azul para Admin
                                            break;
                                        case 'editor':
                                            borderColorClass = 'border-warning'; // Amarillo para Editor
                                            break;
                                    }

                                    // Crear tooltip con información completa del editor usando la función auxiliar
                                    const editorTooltip = createEditorTooltipContent(user, row.updated_at);

                                    if (user.profile_image && typeof user.profile_image === 'string' && user.profile_image.trim() !== '' && !user.profile_image.includes('default.png')) {
                                        let imgSrc = user.profile_image;
                                        if (imgSrc && !imgSrc.includes('placehold.co')) {
                                            imgSrc += (imgSrc.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
                                        }
                                        return `<img src="${imgSrc}" alt="Perfil de ${user.username || 'editor'}" 
                                                class="rounded-circle border border-3 ${borderColorClass} profile-image-table" 
                                                style="width:32px;height:32px;object-fit:cover;" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-html="true"
                                                data-bs-custom-class="editor-tooltip"
                                                data-bs-title="${encodeURIComponent(editorTooltip)}" 
                                                loading="lazy">`;                                    } else {
                                        const inicial = user.username ? user.username.charAt(0).toUpperCase() : '?';
                                        // Generar colores dinámicos para la inicial
                                        const colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                                        const colorIndex = user.username ? user.username.length % colors.length : 0;
                                        const bgColor = colors[colorIndex];
                                        
                                        return `<span class="profile-image-placeholder-table rounded-circle border border-3 ${borderColorClass}" 
                                                style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:${bgColor};color:#ffffff;font-weight:bold;font-size:1rem;" 
                                                data-bs-toggle="tooltip" 
                                                data-bs-html="true"
                                                data-bs-custom-class="editor-tooltip"
                                                data-bs-title="${encodeURIComponent(editorTooltip)}">
                                            ${inicial}
                                        </span>`;
                                    }
                                }
                            },// 8. Editores con acceso (permisos cruzados)
                            {
                                targets: 8,
                                data: 'creator_id',
                                className: 'text-center',
                                orderable: false,
                                render: function(data, type, row) {
                                    const creatorIds = Array.isArray(data) ? data : (data ? [data] : []);
                                    if (!creatorIds.length) {
                                        return '<div class="permission-indicator-empty"><span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>Sin editores adicionales</span></div>';
                                    }
                                    
                                    // Limitamos a mostrar máximo 3 avatares
                                    const maxToShow = 3;
                                    let html = '<div class="permission-indicator d-inline-flex align-items-center">';
                                    const totalEditors = creatorIds.length;
                                      // Crear contenido para el popover principal del badge
                                    let mainTooltipContent = `
                                        <div class="permission-popover-header">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="popover-title">
                                                    <i class="fas fa-user-shield text-success me-2"></i>
                                                    <span class="fw-bold">Permisos de Edición</span>
                                                </div>
                                                <span class="badge bg-success rounded-pill px-3 py-2">
                                                    <i class="fas fa-users me-1"></i>${totalEditors}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="permission-popover-body">
                                            <p class="text-muted mb-3 small">
                                                <i class="fas fa-info-circle me-1"></i>
                                                ${totalEditors === 1 ? 'Usuario con acceso' : 'Usuarios con acceso'} de edición:
                                            </p>
                                            <div class="editors-list">`;
                                      // Añadir cada usuario al contenido del popover principal
                                    creatorIds.forEach(editorId => {
                                        if (window.usersById && window.usersById[editorId]) {
                                            const editor = window.usersById[editorId];
                                            const editorName = editor.username || 'Editor';
                                            const editorRole = editor.role || 'desconocido';
                                            let roleBadgeClass = 'editor-badge';
                                            let roleBadgeText = 'Editor';
                                            let roleIcon = '<i class="fas fa-user-pen text-warning"></i>';
                                            
                                            switch(editorRole.toLowerCase()) {
                                                case 'owner':
                                                    roleBadgeClass = 'owner-badge';
                                                    roleBadgeText = 'Propietario';
                                                    roleIcon = '<i class="fas fa-crown text-success"></i>';
                                                    break;
                                                case 'admin':
                                                    roleBadgeClass = 'admin-badge';
                                                    roleBadgeText = 'Admin';
                                                    roleIcon = '<i class="fas fa-user-shield text-primary"></i>';
                                                    break;
                                            }
                                              // Generar avatar: imagen o círculo perfecto con inicial
                                            let avatarHtml = '';
                                            if (editor.profile_image && 
                                                typeof editor.profile_image === 'string' && 
                                                editor.profile_image.trim() !== '' && 
                                                !editor.profile_image.includes('default.png')) {
                                                avatarHtml = `<img src="${editor.profile_image}" alt="${editorName}" 
                                                    class="rounded-circle me-3 editor-avatar-popover" 
                                                    width="32" height="32" 
                                                    style="object-fit:cover;border:2px solid #e9ecef;min-width:32px;max-width:32px;min-height:32px;max-height:32px;">`;
                                            } else {
                                                const inicial = editorName.charAt(0).toUpperCase();
                                                const colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                                                const colorIndex = editorName.length % colors.length;
                                                const bgColor = colors[colorIndex];
                                                avatarHtml = `<div class="rounded-circle me-3 d-flex align-items-center justify-content-center editor-avatar-popover" 
                                                    style="width:32px;height:32px;min-width:32px;max-width:32px;min-height:32px;max-height:32px;background:${bgColor};color:#ffffff;font-weight:600;font-size:0.9rem;border:2px solid #e9ecef;flex-shrink:0;line-height:1;display:flex !important;">${inicial}</div>`;
                                            }
                                            
                                            mainTooltipContent += `
                                                <div class="editor-item d-flex align-items-center mb-2 p-2 rounded" style="background:#f8f9fa;">
                                                    ${avatarHtml}
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-1">
                                                            ${roleIcon}
                                                            <span class="fw-medium ms-2">${editorName}</span>
                                                            <span class="user-role-badge ${roleBadgeClass} ms-auto">${roleBadgeText}</span>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-check-circle text-success me-1"></i>
                                                            Acceso autorizado
                                                        </small>
                                                    </div>
                                                </div>`;
                                        }
                                    });
                                    
                                    mainTooltipContent += `</div></div>`;
                                      // Badge principal con popover
                                    html += `<span class="badge bg-success me-2 permission-badge ${totalEditors > 1 ? 'permission-badge-glow' : ''}" 
                                              data-bs-toggle="popover" 
                                              data-bs-custom-class="permission-popover"
                                              data-bs-html="true"
                                              data-bs-placement="top"
                                              data-bs-trigger="hover focus"
                                              data-bs-title="<i class='fas fa-user-shield me-2'></i>Permisos de Edición"
                                              data-bs-content="${encodeURIComponent(mainTooltipContent)}">
                                        <i class="fas fa-user-check me-1"></i>${totalEditors}
                                    </span>`;
                                    
                                    // Mostrar avatares de editores (limitado) con tooltips individuales
                                    let shownCount = 0;
                                    creatorIds.forEach((editorId, index) => {
                                        if (index < maxToShow && window.usersById && window.usersById[editorId]) {
                                            shownCount++;
                                            const editor = window.usersById[editorId];
                                            const editorName = editor.username || 'Editor';
                                            const editorRole = editor.role || 'desconocido';
                                            
                                            // Color de borde según rol
                                            let borderColorClass = 'border-secondary';
                                            let roleClass = '';
                                            let roleIcon = '';
                                            let roleBadge = '';
                                            
                                            switch(editorRole.toLowerCase()) {
                                                case 'owner':
                                                    borderColorClass = 'border-success';
                                                    roleClass = 'role-owner';
                                                    roleIcon = '<i class="fas fa-crown text-success me-1"></i>';
                                                    roleBadge = '<span class="user-role-badge owner-badge ms-1">Propietario</span>';
                                                    break;
                                                case 'admin':
                                                    borderColorClass = 'border-primary';
                                                    roleClass = 'role-admin';
                                                    roleIcon = '<i class="fas fa-user-shield text-primary me-1"></i>';
                                                    roleBadge = '<span class="user-role-badge admin-badge ms-1">Admin</span>';
                                                    break;
                                                default:
                                                    borderColorClass = 'border-warning';
                                                    roleClass = 'role-editor';
                                                    roleIcon = '<i class="fas fa-user-pen text-warning me-1"></i>';
                                                    roleBadge = '<span class="user-role-badge editor-badge ms-1">Editor</span>';
                                            }
                                            
                                            // Tooltip individual para cada avatar
                                            const individualTooltip = `
                                                <div class="d-flex align-items-center mb-1">
                                                    ${roleIcon}
                                                    <strong>${editorName}</strong>
                                                    ${roleBadge}
                                                </div>
                                                <small class="text-muted d-block">Tiene permisos de edición</small>
                                            `;
                                              // Renderizar avatar o inicial
                                            if (editor.profile_image && 
                                                typeof editor.profile_image === 'string' && 
                                                editor.profile_image.trim() !== '' && 
                                                !editor.profile_image.includes('default.png')) {
                                                html += `<img src="${editor.profile_image}" alt="${editorName}" 
                                                    class="rounded-circle border border-3 ${borderColorClass} me-1 editor-avatar ${roleClass}" 
                                                    style="width:30px;height:30px;object-fit:cover;margin-left:-8px;z-index:${10-index};" 
                                                    data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    data-bs-custom-class="editor-tooltip" 
                                                    data-bs-title="${encodeURIComponent(individualTooltip)}">`;
                                            } else {
                                                const inicial = editorName.charAt(0).toUpperCase();
                                                // Colores de fondo para avatares sin imagen
                                                const colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                                                const colorIndex = editorName.length % colors.length;
                                                const bgColor = colors[colorIndex];
                                                
                                                html += `<span class="rounded-circle border border-3 ${borderColorClass} me-1 editor-avatar ${roleClass}" 
                                                    style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;background:${bgColor};color:#ffffff;font-weight:bold;font-size:0.85rem;margin-left:-8px;z-index:${10-index};" 
                                                    data-bs-toggle="tooltip"
                                                    data-bs-html="true"
                                                    data-bs-custom-class="editor-tooltip"
                                                    data-bs-title="${encodeURIComponent(individualTooltip)}">${inicial}</span>`;
                                            }
                                        }
                                    });
                                    
                                    // Indicador de editores adicionales
                                    if (totalEditors > maxToShow) {
                                        const remaining = totalEditors - maxToShow;
                                        const extraTooltip = `${remaining} editor${remaining !== 1 ? 'es' : ''} adicional${remaining !== 1 ? 'es' : ''}`;
                                        html += `<span class="badge rounded-pill bg-primary editor-extra-badge" 
                                            style="margin-left:-8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-title="${extraTooltip}">+${remaining}</span>`;
                                    }
                                    
                                    html += '</div>';
                                    return html;                                }
                            },
                            // 9. Acciones
                            {
                                targets: 9,
                                data: null,
                                orderable: false,
                                className: 'text-center actions-column',
                                render: function(data, type, row) {
                                    let actionsHtml = '<div class="btn-group" role="group" aria-label="Acciones del formulario">';
                                    
                                    // --- NUEVO: Acciones según permisos dinámicos del backend ---
                                    if (row.can_edit_current_user || row.can_delete_current_user || row.can_duplicate_current_user || row.can_manage_permissions_current_user) {
                                        // Ver Respuestas/Análisis (si puede ver algo, siempre disponible)
                                        actionsHtml += `<button class="btn btn-info btn-sm rounded-circle me-1 hvr-grow view-responses-btn" data-form-id="${row.id}" data-form-title="${row.title}" data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Respuestas/Análisis"><i class="fas fa-chart-bar"></i></button>`;
                                    }
                                    if (row.can_edit_current_user) {
                                        actionsHtml += `<button class="btn btn-warning btn-sm rounded-circle me-1 hvr-grow edit-form-btn" data-form-id="${row.id}" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar Formulario"><i class="fas fa-edit"></i></button>`;
                                    }
                                    if (row.can_duplicate_current_user) {
                                        actionsHtml += `<button class="btn btn-secondary btn-sm rounded-circle me-1 hvr-grow duplicate-form-btn" data-form-id="${row.id}" data-bs-toggle="tooltip" data-bs-placement="top" title="Duplicar Formulario"><i class="fas fa-copy"></i></button>`;
                                    }
                                    if (row.can_delete_current_user) {
                                        actionsHtml += `<button class="btn btn-danger btn-sm rounded-circle me-1 hvr-grow delete-form-btn" data-form-id="${row.id}" data-form-title="${row.title}" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar Formulario"><i class="fas fa-trash-alt"></i></button>`;
                                    }
                                    if (row.can_manage_permissions_current_user) {
                                        // Agregar el indicador visual del número de editores con acceso
                                        const creatorIds = Array.isArray(row.creator_id) ? row.creator_id : (row.creator_id ? [row.creator_id] : []);
                                        const editorsCount = creatorIds.length;
                                        const badgeClass = editorsCount > 0 ? 'bg-success' : 'bg-secondary';
                                        const badgeGlow = editorsCount > 0 ? 'permission-badge-glow' : '';
                                        
                                        // Generar la lista de usuarios con acceso para mostrar en el tooltip
                                        let usersList = '';
                                        if (window.usersById && editorsCount > 0) {
                                            usersList = '<div class="text-start mt-2 permission-users-list"><strong>Usuarios con acceso:</strong><ul class="mb-0 ps-3 mt-1">';
                                            creatorIds.forEach(id => {
                                                const user = window.usersById[id];
                                                if (user) {
                                                    let roleIcon = '<i class="fas fa-user text-secondary"></i>';
                                                    let roleText = 'Usuario';
                                                    
                                                    if (user.role === 'owner') {
                                                        roleIcon = '<i class="fas fa-crown text-success"></i>';
                                                        roleText = 'Propietario';
                                                    } else if (user.role === 'admin') {
                                                        roleIcon = '<i class="fas fa-user-shield text-primary"></i>';
                                                        roleText = 'Administrador';
                                                    } else if (user.role === 'editor') {
                                                        roleIcon = '<i class="fas fa-user-pen text-warning"></i>';
                                                        roleText = 'Editor';
                                                    }
                                                    
                                                    usersList += `<li class="mb-1">${roleIcon} <span class="ms-1">${user.username}</span> <small class="text-muted">(${roleText})</small></li>`;
                                                }
                                            });
                                            usersList += '</ul></div>';
                                        }
                                        
                                        const tooltipContent = `
                                            <div class="permission-tooltip-header"><i class="fas fa-user-shield me-1"></i> Gestionar Permisos de Edición</div>
                                            <div class="text-${editorsCount > 0 ? 'success' : 'secondary'} small mt-1 permission-tooltip-count">
                                                <i class="fas fa-users me-1"></i> 
                                                ${editorsCount} usuario${editorsCount !== 1 ? 's' : ''} con acceso a este formulario
                                            </div>
                                            ${usersList}
                                            ${editorsCount === 0 ? '<div class="text-muted small fst-italic mt-2">Haz clic para asignar permisos de edición</div>' : ''}
                                        `;
                                        
                                        // Estilos CSS para tooltips
                                        if (!document.getElementById('permission-tooltip-styles')) {
                                            const styleEl = document.createElement('style');
                                            styleEl.id = 'permission-tooltip-styles';
                                            styleEl.innerHTML = `
                                                .permission-tooltip {
                                                    --bs-tooltip-max-width: 320px;
                                                }
                                                .permission-badge-glow {
                                                    animation: pulseBadge 2s infinite;
                                                }
                                                @keyframes pulseBadge {
                                                    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.6); }
                                                    70% { box-shadow: 0 0 0 6px rgba(25, 135, 84, 0); }
                                                    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
                                                }
                                            `;
                                            document.head.appendChild(styleEl);
                                        }
                                  actionsHtml += `<button class="btn ${editorsCount > 0 ? 'btn-outline-success' : 'btn-outline-primary'} btn-sm rounded-circle me-1 hvr-grow position-relative manage-permissions-btn" 
                                            data-form-id="${row.id}" 
                                            data-form-title="${row.title}" 
                                            data-creator-ids='${JSON.stringify(row.creator_id)}' 
                                            data-bs-html="true"
                                            data-bs-toggle="popover" 
                                            data-bs-placement="left" 
                                            data-bs-trigger="hover focus"
                                            data-bs-custom-class="permission-popover"
                                            data-bs-boundary="viewport"
                                            data-bs-content="${tooltipContent.replace(/"/g, '&quot;')}">
                                            <i class="fas fa-user-shield"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill ${badgeClass} ${badgeGlow}" style="font-size: 0.6rem;">
                                                ${editorsCount}
                                            </span>                                        </button>`;
                                    }
                                    
                                    // Botón de Permisos Cruzados (solo para owner y admin)
                                    if (currentUser.role === 'owner' || currentUser.role === 'admin') {
                                        actionsHtml += `<button class="btn btn-outline-warning btn-sm rounded-circle me-1 hvr-grow position-relative manage-cross-permissions-btn" 
                                            data-form-id="${row.id}" 
                                            data-form-title="${row.title}"
                                            data-form-area-id="${row.area_id}"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="left" 
                                            title="Gestionar Permisos Cruzados Entre Áreas">
                                            <i class="fas fa-users-between-lines"></i>
                                        </button>`;
                                    }
                                      actionsHtml += '</div>';                                    return actionsHtml || '<span class="text-muted small fst-italic">N/A</span>';
                                }
                            }
                        ],                        columns: [
                            { data: 'id' },
                            { data: 'title', width: '15%' }, // Reducido de 18% a 15%
                            { data: 'area_name', width: '8%' },
                            { data: 'public_url', width: '7%' },
                            { data: 'responses_count', width: '5%' },
                            { data: 'created_at', width: '7%' },
                            { data: 'expiration_date', width: '7%' },
                            { data: 'updated_id', width: '5%' },
                            { data: 'creator_id', width: '8%' }, // Reducido de 10% a 8%
                            { data: null, defaultContent: '', width: '30%' } // Para la columna de acciones - aumentado a 30%
                        ],
                        language: { decimal: "", emptyTable: "No hay formularios creados todavía.", info: "Mostrando _START_ a _END_ de _TOTAL_ formularios", infoEmpty: "Mostrando 0 formularios", infoFiltered: "(filtrado de _MAX_ formularios totales)", lengthMenu: "Mostrar _MENU_ formularios", loadingRecords: "Cargando...", processing: "Procesando...", search: "Buscar:", zeroRecords: "No se encontraron formularios coincidentes", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" } },
                        scrollX: true,
                        autoWidth: false,
                        responsive: true,
                        order: [[5, 'desc']], // Ordenar por fecha de creación descendente por defecto
                        drawCallback: function() {
                            // Inicializar tooltips después de cada redibujado de la tabla
                            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                                // Limpiar tooltip existente antes de crear uno nuevo
                                const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                                if (existingTooltip) {
                                    existingTooltip.dispose();
                                }                                let options = {
                                    html: tooltipTriggerEl.getAttribute('data-bs-html') === 'true',
                                    container: 'body',
                                    trigger: 'hover focus',
                                    boundary: 'window',
                                    delay: { show: 300, hide: 100 },
                                    animation: true,
                                    placement: 'auto'
                                };                                  // Configuración especial para tooltips de editores
                                if (tooltipTriggerEl.getAttribute('data-bs-custom-class') === 'editor-tooltip') {
                                    options = {
                                        ...options,
                                        placement: 'top',
                                        boundary: 'viewport',
                                        container: document.body,
                                        delay: { show: 150, hide: 200 }, // Mejorado para mejor respuesta
                                        trigger: 'hover',
                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                        popperConfig: {
                                            strategy: 'fixed',
                                            modifiers: [
                                                {
                                                    name: 'preventOverflow',
                                                    options: {
                                                        boundary: 'viewport',
                                                        padding: 20, // Aumentado para dar más espacio
                                                        altBoundary: true
                                                    }
                                                },
                                                {
                                                    name: 'flip',
                                                    options: {
                                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                                        allowedAutoPlacements: ['top', 'bottom', 'left', 'right']
                                                    }
                                                },
                                                {
                                                    name: 'offset',
                                                    options: {
                                                        offset: [0, 6]
                                                    }
                                                }
                                            ]
                                        }
                                    };
                                }                                // Configuración especial para tooltips de permisos
                                if (tooltipTriggerEl.getAttribute('data-bs-custom-class') === 'permission-tooltip') {
                                    options = {
                                        ...options,
                                        placement: 'top',
                                        boundary: 'viewport',
                                        container: document.body,
                                        delay: { show: 200, hide: 150 }, // Reducido para mejor respuesta
                                        trigger: 'hover',
                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                        popperConfig: {
                                            strategy: 'fixed',
                                            modifiers: [
                                                {
                                                    name: 'preventOverflow',
                                                    options: {
                                                        boundary: 'viewport',
                                                        padding: 20, // Aumentado para dar más espacio a tooltips más grandes
                                                        altBoundary: true
                                                    }
                                                },
                                                {
                                                    name: 'flip',
                                                    options: {
                                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                                        allowedAutoPlacements: ['top', 'bottom', 'left', 'right']
                                                    }
                                                },
                                                {
                                                    name: 'offset',
                                                    options: {
                                                        offset: [0, 8]
                                                    }
                                                }
                                            ]
                                        }
                                    };
                                }
                                
                                // Manejar título codificado en data-bs-title
                                if (tooltipTriggerEl.getAttribute('data-bs-title') && 
                                    tooltipTriggerEl.getAttribute('data-bs-html') === 'true') {
                                    try {
                                        const decodedTitle = decodeURIComponent(tooltipTriggerEl.getAttribute('data-bs-title'));
                                        tooltipTriggerEl.setAttribute('data-bs-title', decodedTitle);
                                    } catch (e) {
                                        console.warn('Error decodificando data-bs-title:', e);
                                    }
                                }
                                
                                // Manejar título codificado en atributo title
                                if (tooltipTriggerEl.getAttribute('title') && 
                                    tooltipTriggerEl.getAttribute('data-bs-html') === 'true') {
                                    try {
                                        const decodedTitle = decodeURIComponent(tooltipTriggerEl.getAttribute('title'));
                                        tooltipTriggerEl.setAttribute('title', decodedTitle);
                                        // También establecer en data-bs-title para compatibilidad
                                        tooltipTriggerEl.setAttribute('data-bs-title', decodedTitle);
                                    } catch (e) {
                                        console.warn('Error decodificando title:', e);
                                    }
                                }
                                
                                // Agregar clase personalizada si está especificada
                                if (tooltipTriggerEl.getAttribute('data-bs-custom-class')) {
                                    options.customClass = tooltipTriggerEl.getAttribute('data-bs-custom-class');
                                }
                                  return new bootstrap.Tooltip(tooltipTriggerEl, options);
                            });
                            
                            // Inicializar popovers después de cada redibujado de la tabla
                            const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
                            popoverTriggerList.forEach(function (popoverTriggerEl) {
                                // Limpiar popover existente antes de crear uno nuevo
                                const existingPopover = bootstrap.Popover.getInstance(popoverTriggerEl);
                                if (existingPopover) {
                                    existingPopover.dispose();
                                }
                                
                                let popoverOptions = {
                                    html: popoverTriggerEl.getAttribute('data-bs-html') === 'true',
                                    container: 'body',
                                    trigger: popoverTriggerEl.getAttribute('data-bs-trigger') || 'hover focus',
                                    boundary: 'viewport',
                                    delay: { show: 200, hide: 150 },
                                    animation: true,
                                    placement: popoverTriggerEl.getAttribute('data-bs-placement') || 'top',
                                    fallbackPlacements: ['bottom', 'left', 'right']
                                };
                                  // Configuración especial para popovers de permisos
                                if (popoverTriggerEl.getAttribute('data-bs-custom-class') === 'permission-popover') {
                                    popoverOptions = {
                                        ...popoverOptions,
                                        placement: 'top',
                                        boundary: 'viewport',
                                        container: document.body,
                                        delay: { show: 300, hide: 200 },
                                        trigger: 'hover focus',
                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                        popperConfig: {
                                            strategy: 'fixed',
                                            modifiers: [
                                                {
                                                    name: 'preventOverflow',
                                                    options: {
                                                        boundary: 'viewport',
                                                        padding: 20,
                                                        altBoundary: true
                                                    }
                                                },
                                                {
                                                    name: 'flip',
                                                    options: {
                                                        fallbackPlacements: ['bottom', 'left', 'right'],
                                                        allowedAutoPlacements: ['top', 'bottom', 'left', 'right']
                                                    }
                                                },
                                                {
                                                    name: 'offset',
                                                    options: {
                                                        offset: [0, 10]
                                                    }
                                                }
                                            ]
                                        }
                                    };
                                }
                                  // Configuración específica para botones de gestión de permisos
                                if (popoverTriggerEl.classList.contains('manage-permissions-btn')) {
                                    popoverOptions = {
                                        ...popoverOptions,
                                        placement: 'left',
                                        boundary: 'viewport',
                                        container: document.body,
                                        delay: { show: 300, hide: 150 },
                                        trigger: 'hover focus',
                                        fallbackPlacements: ['right', 'top', 'bottom'],
                                        popperConfig: {
                                            strategy: 'fixed',
                                            modifiers: [
                                                {
                                                    name: 'preventOverflow',
                                                    options: {
                                                        boundary: 'viewport',
                                                        padding: 15,
                                                        altBoundary: true
                                                    }
                                                },
                                                {
                                                    name: 'flip',
                                                    options: {
                                                        fallbackPlacements: ['right', 'top', 'bottom'],
                                                        allowedAutoPlacements: ['left', 'right', 'top', 'bottom']
                                                    }
                                                },
                                                {
                                                    name: 'offset',
                                                    options: {
                                                        offset: [0, 8]
                                                    }
                                                }
                                            ]
                                        }
                                    };
                                }
                                
                                // Manejar contenido codificado en data-bs-content
                                if (popoverTriggerEl.getAttribute('data-bs-content') && 
                                    popoverTriggerEl.getAttribute('data-bs-html') === 'true') {
                                    try {
                                        const decodedContent = decodeURIComponent(popoverTriggerEl.getAttribute('data-bs-content'));
                                        popoverTriggerEl.setAttribute('data-bs-content', decodedContent);
                                    } catch (e) {
                                        console.warn('Error decodificando data-bs-content:', e);
                                    }
                                }
                                
                                // Agregar clase personalizada si está especificada
                                if (popoverTriggerEl.getAttribute('data-bs-custom-class')) {
                                    popoverOptions.customClass = popoverTriggerEl.getAttribute('data-bs-custom-class');
                                }
                                
                                return new bootstrap.Popover(popoverTriggerEl, popoverOptions);
                            });
                            
                            // Actualizar los indicadores de permisos después de cada redibujado
                            updatePermissionIndicators();
                            
                            hideMainSpinner(); // Ocultar spinner al terminar de dibujar
                        }
                    });
                } catch (e) {
                    console.error("Error inicializando DataTables:", e);
                    showToast("Error crítico al inicializar la tabla de formularios.", true);
                    hideMainSpinner();
                }
            } else {
                console.warn("jQuery, DataTables o currentUser no están disponibles o currentUser.role no está definido para inicializar formsTable.");
                hideMainSpinner();
            }
        });
    } // <--- AÑADIDO: Cierre del if

    function reloadFormsTable() {
        if (formsTable) {
            formsTable.ajax.reload(null, false); 
        }
    }

    // --- Global variables for current analysis data ---
    let currentFormAllResponsesData = [];
    let currentFormFieldsStructure = [];
    let currentFormTitleForAnalysis = "";
    let currentFormIdForAnalysis = null; 

    const noFieldsMessage = document.getElementById('noFieldsMessage');
    const chartsSection = document.getElementById('chartsSection'); 
    const chartsRenderContainer = document.getElementById('chartsRenderContainer');
    const individualResponsesTableContainer = document.getElementById('individualResponsesTableContainer');
    const exportResponsesCsvBtn = document.getElementById('exportResponsesCsvBtn');
    const closeChartsSectionBtn = document.getElementById('closeChartsSection'); 
    let activeCharts = []; 


    // --- Helper Functions for Analysis Section ---
    function clearCharts() {
        activeCharts.forEach(chart => chart.destroy());
        activeCharts = [];
        if (chartsRenderContainer) chartsRenderContainer.innerHTML = '<p class="text-muted text-center col-12">Selecciona un formulario para ver el análisis gráfico.</p>';
    }

    function clearIndividualResponsesTable() {
        if (individualResponsesTableContainer) individualResponsesTableContainer.innerHTML = '<p class="text-muted text-center">Selecciona un formulario para ver las respuestas individuales.</p>';
    }

    function clearAnalysisData() {
        clearCharts();
        clearIndividualResponsesTable();
        
        const responsesCountBadge = document.getElementById('responsesCountBadge');
        if (responsesCountBadge) responsesCountBadge.textContent = '0';
        
        if (exportResponsesCsvBtn) exportResponsesCsvBtn.classList.add('disabled');

        currentFormAllResponsesData = [];
        currentFormFieldsStructure = [];
        currentFormTitleForAnalysis = "";
        currentFormIdForAnalysis = null; 
    }
    clearAnalysisData(); 


    // --- Event Delegation for Table Actions & Analysis Section ---
    document.body.addEventListener('click', function(event) {
        const target = event.target;        if (target.closest('.copy-url-btn')) {
            const button = target.closest('.copy-url-btn');
            const url = button.dataset.url;
            navigator.clipboard.writeText(url).then(() => {
                showToast('URL copiada al portapapeles: ' + url);
            }).catch(err => {
                console.error('Error al copiar URL: ', err);
                showToast('Error al copiar URL.', true);
            });
        }
        else if (target.closest('.open-form-btn')) {
            const button = target.closest('.open-form-btn');
            const url = button.dataset.url;
            window.open(url, '_blank');
        }
        else if (target.closest('.edit-form-btn')) {
            const button = target.closest('.edit-form-btn');
            const formId = button.dataset.formId;
            document.getElementById('formBuilder').reset();
            document.getElementById('formId').value = formId;
            document.getElementById('createEditFormModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Formulario';
            
            const formFieldsContainer = document.getElementById('formFieldsContainer'); 
            if (formFieldsContainer) formFieldsContainer.innerHTML = ''; 
            if (noFieldsMessage) noFieldsMessage.style.display = 'block';
            
            // Cargar áreas disponibles en el selector
            const areaSelector = document.getElementById('area_id');
            if (areaSelector) {
                loadAvailableAreas(areaSelector);
            }

            fetch(`api/forms.php?action=get&id=${formId}`) 
                .then(response => {
                    if (!response.ok) { 
                        return response.json().then(err => { throw err; }); 
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success && result.data) {
                        const formData = result.data;
                        document.getElementById('formTitle').value = formData.title;
                        document.getElementById('formDescription').value = formData.description || '';
                        document.getElementById('companyName').value = formData.companyName || '';
                        document.getElementById('logoUrl').value = formData.logoUrl || '';
                        document.getElementById('expirationDate').value = formData.expiration_date || ''; 
                        
                        // Seleccionar el área del formulario en el dropdown
                        const areaSelector = document.getElementById('area_id');
                        if (areaSelector && formData.area_id) {
                            // Si el área aún no está cargada en el selector, volver a cargar áreas
                            const areaExists = Array.from(areaSelector.options).some(option => option.value === formData.area_id);
                            if (!areaExists) {
                                loadAvailableAreas(areaSelector, function() {
                                    areaSelector.value = formData.area_id;
                                });
                            } else {
                                areaSelector.value = formData.area_id;
                            }
                        }

                        if (formData.fields && formData.fields.length > 0) {
                            if (noFieldsMessage) noFieldsMessage.style.display = 'none';
                            formData.fields.forEach(fieldData => {
                                addFieldToForm(fieldData.type, fieldData);
                            });
                        }
                        if(createEditFormModal) createEditFormModal.show();
                    } else {
                        showToast('Error al cargar datos del formulario: ' + (result.message || 'No se encontraron datos.'), true);
                    }
                })
                .catch(error => {
                    console.error('Error fetching form data for edit:', error);
                    showToast(error.message || 'Error de conexión al cargar el formulario para editar.', true);
                });
        }
        else if (target.closest('.delete-form-btn')) {
            const button = target.closest('.delete-form-btn');
            const formId = button.dataset.formId;
            const formTitle = button.dataset.formTitle;
            document.getElementById('formNameToDelete').textContent = formTitle;
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            confirmDeleteBtn.dataset.formIdToDelete = formId; 
            if(confirmDeleteModal) confirmDeleteModal.show();
        }
        else if (target.closest('.duplicate-form-btn')) {
            const button = target.closest('.duplicate-form-btn');
            const formId = button.dataset.formId;
            if (!confirm(`¿Estás seguro de que quieres duplicar este formulario (ID: ${formId})?`)) {
                return;
            }
            fetch(`api/forms.php?action=duplicate&id=${formId}`, { method: 'POST' }) 
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    reloadFormsTable();
                    showToast(data.message || 'Formulario duplicado exitosamente.');
                } else {
                    showToast('Error al duplicar: ' + (data.message || 'Error desconocido'), true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error de conexión al duplicar el formulario.', true);
            });
        }
        else if (target.closest('.view-responses-btn')) {
            clearAnalysisData(); 

            const button = target.closest('.view-responses-btn');
            currentFormIdForAnalysis = button.dataset.formId; 
            currentFormTitleForAnalysis = button.dataset.formTitle || "Formulario";
            
            if (!currentFormIdForAnalysis) {
                showToast("Error: No se pudo obtener el ID del formulario para ver las respuestas.", true);
                return; 
            }
            
            document.getElementById('formAnalysisTitle').textContent = currentFormTitleForAnalysis;
            document.getElementById('formAnalysisTitleContext').textContent = currentFormTitleForAnalysis;
            
            if (chartsSection) chartsSection.style.display = 'block';
            chartsSection.scrollIntoView({ behavior: 'smooth' });

            const chartsTabButton = document.getElementById('charts-tab');
            if (chartsTabButton) new bootstrap.Tab(chartsTabButton).show();
            
            fetch(`api/responses.php?action=get_responses&form_id=${currentFormIdForAnalysis}`)
                .then(response => {
                    if (!response.ok) { 
                        return response.json().then(err => { throw err; }); 
                    }
                    return response.json();
                })
                .then(responsesResult => {
                    if (responsesResult.success && responsesResult.data) {
                        currentFormAllResponsesData = responsesResult.data;
                        const responsesCountBadge = document.getElementById('responsesCountBadge');
                        if(responsesCountBadge) responsesCountBadge.textContent = currentFormAllResponsesData.length;

                        if (currentFormAllResponsesData.length === 0) {
                            showToast('No hay respuestas para este formulario aún.');
                        }
                        
                        if (exportResponsesCsvBtn) { 
                           currentFormAllResponsesData.length > 0 ? exportResponsesCsvBtn.classList.remove('disabled') : exportResponsesCsvBtn.classList.add('disabled');
                        }

                        fetch(`api/forms.php?action=get_public&id=${currentFormIdForAnalysis}`) 
                            .then(structureResponse => structureResponse.json())
                            .then(structureResult => {
                                if (structureResult.success && structureResult.data && structureResult.data.fields) {
                                    currentFormFieldsStructure = structureResult.data.fields;
                                    renderCharts(currentFormAllResponsesData, currentFormFieldsStructure, currentFormTitleForAnalysis);
                                    renderIndividualResponsesTable(currentFormAllResponsesData, currentFormFieldsStructure);
                                } else {
                                    showToast('Error al cargar la estructura del formulario para el análisis.', true);
                                    clearAnalysisData(); 
                                }
                            })
                            .catch(structureError => {
                                console.error('Error fetching form structure for analysis:', structureError);
                                showToast('Error de conexión al cargar la estructura del formulario.', true);
                                clearAnalysisData();
                            });
                    } else { 
                        showToast('Error al cargar respuestas: ' + (responsesResult.message || 'No se encontraron datos.'), true);
                        clearAnalysisData(); 
                        const responsesCountBadge = document.getElementById('responsesCountBadge');
                        if(responsesCountBadge) responsesCountBadge.textContent = 0;
                        if (exportResponsesCsvBtn) exportResponsesCsvBtn.classList.add('disabled');
                    }
                })
                .catch(error => {
                    console.error('Error fetching responses:', error);
                    showToast(error.message || 'Error de conexión al cargar las respuestas.', true);
                    clearAnalysisData();
                });
        }
        else if (target.closest('.open-url-btn')) {
            const button = target.closest('.open-url-btn');
            const url = button.dataset.url;
            if (url) {
                window.open(url, '_blank');
            }
        }
    });

    // --- Form Builder (Dynamic Form Creation) ---
    const formBuilder = document.getElementById('formBuilder');
    let fieldCounter = 0; 

    document.querySelectorAll('.add-field-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const fieldType = this.dataset.type;
            addFieldToForm(fieldType); 
            
            const formFieldsContainer = document.getElementById('formFieldsContainer'); 
            if (noFieldsMessage && formFieldsContainer && formFieldsContainer.children.length > 0) {
                noFieldsMessage.style.display = 'none';
            }
        });
    });

    function addFieldToForm(type, existingData = null) {
        fieldCounter++;
        const fieldIdSuffix = existingData && existingData.id ? existingData.id.split('_').pop() : fieldCounter;
        const fieldId = `field_${type}_${fieldIdSuffix}`;
        
        let fieldHtml = `
            <div class="form-field card mb-3 p-3 animate__animated animate__fadeIn" id="field-card-${fieldId}">
                <input type="hidden" name="fields[${fieldId}][id]" value="${fieldId}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="drag-field-handle me-2" style="cursor:move;"><i class="fas fa-grip-vertical"></i></span>
                    <strong class="field-type-label text-primary">${getFieldTypeLabel(type)}</strong>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2 field-config-btn hvr-icon-spin" title="Configurar Campo"><i class="fas fa-cog hvr-icon"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn hvr-buzz-out" data-field-id="${fieldId}" title="Eliminar Campo"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="mb-2">
                    <label for="${fieldId}-label" class="form-label small">Texto de la Pregunta/Etiqueta:</label>
                    <input type="text" class="form-control form-control-sm" id="${fieldId}-label" name="fields[${fieldId}][label]" placeholder="Ej: ¿Cuál es tu nombre?" required value="${existingData?.label || ''}">
                </div>
        `;

        switch (type) {
            case 'text':
                fieldHtml += `<input type="text" class="form-control form-control-sm" name="fields[${fieldId}][placeholder]" placeholder="Texto de ayuda (placeholder)" value="${existingData?.placeholder || ''}">`;
                break;
            case 'textarea':
                fieldHtml += `<textarea class="form-control form-control-sm" name="fields[${fieldId}][placeholder]" rows="2" placeholder="Texto de ayuda (placeholder)">${existingData?.placeholder || ''}</textarea>`;
                break;
            case 'email':
                fieldHtml += `<input type="email" class="form-control form-control-sm" name="fields[${fieldId}][placeholder]" placeholder="ejemplo@correo.com (placeholder)" value="${existingData?.placeholder || ''}">`;
                break;
            case 'tel': 
                fieldHtml += `<input type="tel" class="form-control form-control-sm" name="fields[${fieldId}][placeholder]" placeholder="Ej: 55-1234-5678 (placeholder)" value="${existingData?.placeholder || ''}">`;
                break;
            case 'date':
                fieldHtml += `<input type="date" class="form-control form-control-sm" name="fields[${fieldId}][value]" value="${existingData?.value || ''}">`;
                break;
            case 'radio':
            case 'checkbox':
            case 'select':
                let optionsText = '';
                if (existingData?.options) {
                    if (Array.isArray(existingData.options)) {
                        optionsText = existingData.options.join('\n');
                    } else if (typeof existingData.options === 'string') {
                        optionsText = existingData.options;
                    }
                }
                fieldHtml += `
                    <div class="field-options-container mt-2">
                        <label class="form-label small">Opciones (una por línea):</label>
                        <textarea class="form-control form-control-sm options-input" name="fields[${fieldId}][options]" rows="3" placeholder="Opción 1\nOpción 2\nOpción 3">${optionsText}</textarea>
                    </div>
                `;
                if (type === 'select') {
                     const isMultiple = existingData?.multiple_select ? 'checked' : '';
                     fieldHtml += `<div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="fields[${fieldId}][multiple_select]" id="${fieldId}-multiple" ${isMultiple}><label class="form-check-label small" for="${fieldId}-multiple">Permitir selección múltiple</label></div>`;
                }
                break;
            case 'terms':
                 fieldHtml += `
                    <div class="mb-2">
                        <label for="${fieldId}-terms-text" class="form-label small">Texto de los términos:</label>
                        <textarea class="form-control form-control-sm" id="${fieldId}-terms-text" name="fields[${fieldId}][terms_text]" rows="3" placeholder="Escribe aquí los términos y condiciones..." required>${existingData?.terms_text || ''}</textarea>
                    </div>
                    <div class="mb-2">
                        <label for="${fieldId}-disagreement-message" class="form-label small">Mensaje por desacuerdo (opcional):</label>
                        <input type="text" class="form-control form-control-sm" id="${fieldId}-disagreement-message" name="fields[${fieldId}][disagreement_message]" placeholder="Ej: No podrá continuar si no acepta." value="${existingData?.disagreement_message || ''}">
                    </div>
                    <div class="mt-2 d-none"> 
                        <input type="radio" name="fields[${fieldId}][agreement_response]" value="agree">
                        <input type="radio" name="fields[${fieldId}][agreement_response]" value="disagree">
                    </div>`;
                break;
            case 'file':
                fieldHtml += `<div class="mb-2">
                    <label class="form-label small">Permitir que el usuario cargue un archivo (PDF, DOCX, JPG, PNG, ZIP, etc.)</label>
                    <input type="file" class="form-control form-control-sm" name="fields[${fieldId}][file]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar,.txt">
                    <small class="form-text text-muted">El usuario podrá subir un archivo y luego descargarlo desde la tabla de respuestas.</small>
                </div>`;
                break;
            case 'downloadable':
                fieldHtml += `<div class="mb-2">
                    <label class="form-label small">Archivo para que el usuario descargue (PDF, DOCX, JPG, PNG, ZIP, etc.)</label>
                    <input type="file" class="form-control form-control-sm mb-2 downloadable-file-input" name="fields[${fieldId}][file_uploaded]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar,.txt" ${existingData?.file_url ? 'disabled' : ''}>
                    <input type="url" class="form-control form-control-sm mb-2 downloadable-url-input" name="fields[${fieldId}][file_url]" placeholder="URL del archivo" value="${existingData?.file_url || ''}" ${existingData?.file_uploaded ? 'disabled' : ''}>
                    <textarea class="form-control form-control-sm mb-2" name="fields[${fieldId}][instructions]" placeholder="Instrucciones para el usuario...">${existingData?.instructions || ''}</textarea>
                    <small class="form-text text-muted">Puedes subir un archivo o proporcionar una URL, pero no ambos. Si subes un archivo, la URL se deshabilita y viceversa.</small>
                    <div class="downloadable-preview mt-2"></div>
                    ${existingData?.file_uploaded ? `<input type="hidden" name="fields[${fieldId}][last_uploaded]" value="${existingData.file_uploaded}">` : ''}
                </div>`;
                break;
            case 'accept_only_terms':
                fieldHtml += `<div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="${fieldId}-accept" name="fields[${fieldId}][accept]" value="1" required disabled checked>
                    <label class="form-check-label small" for="${fieldId}-accept">El usuario debe aceptar estos términos para continuar.</label>
                </div>
                <textarea class="form-control form-control-sm mt-2" name="fields[${fieldId}][terms_text]" rows="3" placeholder="Escribe aquí los términos y condiciones..." required>${existingData?.terms_text || ''}</textarea>`;
                break;
            case 'birthdate':
                fieldHtml += `<input type="date" class="form-control form-control-sm" name="fields[${fieldId}][birthdate]" placeholder="Fecha de nacimiento">`;
                break;
            case 'image':
                fieldHtml += `<div class="mb-2">
                    <label class="form-label">Título de la imagen</label>
                    <input type="text" class="form-control form-control-sm mb-2" name="fields[${fieldId}][image_title]" placeholder="Ej: Logo, Foto, etc." value="${existingData?.image_title || ''}">
                    <label class="form-label">Selecciona una imagen</label>
                    <input type="file" class="form-control form-control-sm image-field-input" name="fields[${fieldId}][image_file]" accept="image/*">
                    <div class="image-preview mt-2" style="max-width:100%; max-height:220px; overflow:hidden;"></div>
                    <small class="form-text text-muted">La imagen se mostrará en el formulario, no excederá el ancho de la tarjeta.</small>
                </div>`;
                break;
            case 'number':
                fieldHtml += `<div class="mb-2">
                    <label class="form-label">Placeholder (opcional)</label>
                    <input type="text" class="form-control form-control-sm mb-2" name="fields[${fieldId}][placeholder]" placeholder="Ej: Ingrese un número" value="${existingData?.placeholder || ''}">
                    <label class="form-label">Valor mínimo (opcional)</label>
                    <input type="number" class="form-control form-control-sm mb-2" name="fields[${fieldId}][min]" value="${existingData?.min || ''}">
                    <label class="form-label">Valor máximo (opcional)</label>
                    <input type="number" class="form-control form-control-sm mb-2" name="fields[${fieldId}][max]" value="${existingData?.max || ''}">
                    <small class="form-text text-muted">Solo se aceptarán valores numéricos.</small>
                </div>`;
                break;
        }
        const isRequired = existingData?.required ? 'checked' : '';
        fieldHtml += `
                <input type="hidden" name="fields[${fieldId}][type]" value="${type}">
                <div class="field-config-panel mt-2 p-2 border rounded" style="display:none;">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="${fieldId}-required" name="fields[${fieldId}][required]" ${isRequired}>
                        <label class="form-check-label small" for="${fieldId}-required">Campo Obligatorio</label>
                    </div>
                </div>
            </div>
        `;
        
        const formFieldsContainer = document.getElementById('formFieldsContainer'); 
        if(formFieldsContainer) {
            formFieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
            if (noFieldsMessage && formFieldsContainer.children.length > 0) {
                noFieldsMessage.style.display = 'none';
            }
        }

        // Previsualización de imagen para campos tipo imagen
        setTimeout(() => {
            const card = document.getElementById(`field-card-${fieldId}`);
            if(card && type === 'image') {
                const input = card.querySelector('.image-field-input');
                const preview = card.querySelector('.image-preview');
                if(input && preview) {
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if(file) {
                            const reader = new FileReader();
                            reader.onload = function(ev) {
                                preview.innerHTML = `<img src='${ev.target.result}' alt='Previsualización' class='img-fluid rounded' style='max-width:100%; max-height:220px;'>`;
                            };
                            reader.readAsDataURL(file);
                        } else {
                            preview.innerHTML = '';
                        }
                    });
                }
            }
        }, 100);
    }
    
    function getFieldTypeLabel(type) {
        const labels = { 
            text: "Texto Corto", 
            textarea: "Párrafo", 
            radio: "Opción Múltiple", 
            checkbox: "Casillas", 
            select: "Desplegable", 
            date: "Fecha", 
            email: "Correo Electrónico", 
            tel: "Número de Teléfono", 
            terms: "Aceptación de Términos",
            file: "Cargar Documento",
            downloadable: "Descargar Documento",
            accept_only_terms: "Aceptación de Términos (Solo Aceptar)",
            birthdate: "Fecha de Nacimiento",
            image: "Imagen", // NUEVO
            number: "Número" // NUEVO
        };
        return labels[type] || type.charAt(0).toUpperCase() + type.slice(1);
    }

    const formFieldsContainerGlobal = document.getElementById('formFieldsContainer'); 
    if (formFieldsContainerGlobal) {
        formFieldsContainerGlobal.addEventListener('click', function(e) {
            if (e.target.closest('.remove-field-btn')) {
                const button = e.target.closest('.remove-field-btn');
                const fieldCardId = `field-card-${button.dataset.fieldId}`;
                const fieldCard = document.getElementById(fieldCardId);
                if (fieldCard) {
                    fieldCard.classList.add('animate__animated', 'animate__fadeOutRight');
                    setTimeout(() => {
                        fieldCard.remove();
                        if (noFieldsMessage && formFieldsContainerGlobal.children.length === 0) { 
                             noFieldsMessage.style.display = 'block';
                        }
                    }, 500);
                }
            }
            if (e.target.closest('.field-config-btn')) {
                const button = e.target.closest('.field-config-btn');
                const fieldCard = button.closest('.form-field');
                const configPanel = fieldCard.querySelector('.field-config-panel');
                if (configPanel) {
                    configPanel.style.display = configPanel.style.display === 'none' ? 'block' : 'none';
                }
            }
        });
    }

    if (formBuilder) {        formBuilder.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar que se haya seleccionado un área
            const areaId = document.getElementById('area_id').value;
            if (!areaId) {
                showToast('Debes seleccionar un área para el formulario', true);
                document.getElementById('area_id').focus();
                return;
            }
            
            const formData = new FormData();
            const formIdValue = document.getElementById('formId').value;
            const action = formIdValue ? 'edit' : 'create';// Campos principales
            formData.append('formId', formIdValue);
            formData.append('formTitle', document.getElementById('formTitle').value);
            formData.append('formDescription', document.getElementById('formDescription').value);
            formData.append('companyName', document.getElementById('companyName').value);
            formData.append('logoUrl', document.getElementById('logoUrl').value);
            formData.append('expirationDate', document.getElementById('expirationDate').value);
            formData.append('area_id', document.getElementById('area_id').value);

            // Campos dinámicos (fields)
            const formFieldsContainer = document.getElementById('formFieldsContainer');
            if (formFieldsContainer) {
                const fieldElements = formFieldsContainer.querySelectorAll('.form-field');
                fieldElements.forEach((fieldElement) => {
                    const idInput = fieldElement.querySelector('input[name^="fields["][name$="[id]"]');
                    if (!idInput) return;
                    const fieldFullId = idInput.value;
                    // label y type
                    const labelInput = fieldElement.querySelector(`input[name="fields[${fieldFullId}][label]"]`);
                    const typeInput = fieldElement.querySelector(`input[name="fields[${fieldFullId}][type]"]`);
                    if (labelInput) formData.append(`fields[${fieldFullId}][label]`, labelInput.value);
                    if (typeInput) formData.append(`fields[${fieldFullId}][type]`, typeInput.value);

                    // Otros campos
                    const propInputs = fieldElement.querySelectorAll(`
                        input[name^="fields[${fieldFullId}]"]:not([name$="[id]"]):not([name$="[label]"]):not([name$="[type]"]),
                        textarea[name^="fields[${fieldFullId}]"]:not([name$="[id]"]):not([name$="[label]"]):not([name$="[type]"])
                    `);
                    propInputs.forEach(input => {
                        const nameAttr = input.getAttribute('name');
                        if (input.type === 'file') {
                            if (input.files && input.files.length > 0) {
                                formData.append(nameAttr, input.files[0]);
                            } else {
                                // Si es campo downloadable y hay un last_uploaded, enviar ese valor
                                if (nameAttr.endsWith('[file_uploaded]')) {
                                    const lastUploadedInput = fieldElement.querySelector('input[name="fields[' + fieldFullId + '][last_uploaded]"]');
                                    if (lastUploadedInput && lastUploadedInput.value) {
                                        formData.append('fields[' + fieldFullId + '][last_uploaded]', lastUploadedInput.value);
                                    }
                                }
                            }
                        } else if (input.type === 'checkbox') {
                            formData.append(nameAttr, input.checked ? '1' : '');
                        } else if (input.classList.contains('options-input')) {
                            formData.append(nameAttr, input.value);
                        } else {
                            formData.append(nameAttr, input.value);
                        }
                    });
                });
            }

            // Procesar campos tipo imagen
            document.querySelectorAll('.image-field-input').forEach(input => {
                if(input.files && input.files.length > 0) {
                    formData.append(input.name, input.files[0]);
                }
            });

            const endpoint = action === 'create' ? 'api/forms.php?action=create' : `api/forms.php?action=edit&id=${formIdValue}`;
            fetch(endpoint, {
                method: 'POST',
                body: formData // No establecer Content-Type, el navegador lo hace automáticamente
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if(createEditFormModal) createEditFormModal.hide();
                    reloadFormsTable();
                    showToast(result.message || 'Formulario guardado exitosamente.');
                } else {
                    showToast('Error al guardar: ' + (result.message || 'Error desconocido'), true);
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                showToast('Error de conexión al guardar el formulario.', true);
            });
        });
    }

    // --- Analysis Section: Charts & Individual Responses ---
    function renderCharts(responsesData, formFields, formTitleParam) {
        if (!chartsRenderContainer) return;
        chartsRenderContainer.innerHTML = ''; 

        if (!responsesData || responsesData.length === 0) {
            chartsRenderContainer.innerHTML = `<p class="text-center text-info col-12">No hay respuestas para graficar para el formulario "${formTitleParam}".</p>`;
            return;
        }
        
        const fieldDetailsMap = new Map();
        formFields.forEach(field => {
            fieldDetailsMap.set(field.label, { type: field.type, options: field.options || [] });
        });

        const questionAggregations = {};
        responsesData.forEach(response => {
            for (const questionLabel in response.data) {
                if (!questionAggregations[questionLabel]) {
                    questionAggregations[questionLabel] = [];
                }
                if (Array.isArray(response.data[questionLabel])) {
                    questionAggregations[questionLabel].push(...response.data[questionLabel]);
                } else {
                    questionAggregations[questionLabel].push(response.data[questionLabel]);
                }
            }
        });

        let chartCount = 0;
        for (const questionLabel in questionAggregations) {
            const fieldDetail = fieldDetailsMap.get(questionLabel);
            // Excluir campos que no necesitan gráficos
            const typesToSkipGraphing = ['text', 'textarea', 'downloadable', 'image', 'terms', 'email', 'date', 'tel', 'file', 'accept_only_terms'];
            if (!fieldDetail || typesToSkipGraphing.includes(fieldDetail.type)) {
                continue;
            }

            let dataToGraph = questionAggregations[questionLabel];
            let chartType = 'bar'; 

            if (fieldDetail.type === 'radio' || fieldDetail.type === 'select' || fieldDetail.type === 'checkbox' || fieldDetail.type === 'terms') {
                chartType = 'pie';
            } else if (fieldDetail.type === 'birthdate') {
                // Solo números de edad
                dataToGraph = dataToGraph.filter(x => x !== '' && !isNaN(x));
                chartType = 'bar';
            }


            const answers = dataToGraph;
            const answerCounts = {};
            answers.forEach(answer => {
                answerCounts[answer] = (answerCounts[answer] || 0) + 1;
            });

            if (Object.keys(answerCounts).length === 0) {
                continue; 
            }

            const chartId = `chart-${Date.now()}-${chartCount++}`; 
            
            const chartCardCol = document.createElement('div'); 
            chartCardCol.className = 'col-md-6 mb-4';

            const chartCard = document.createElement('div');
            chartCard.className = 'card h-100 shadow-sm';
            chartCard.innerHTML = `
                <div class="card-header">
                    <h6 class="card-title mb-0 text-truncate" title="${questionLabel}">${questionLabel}</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px;">
                   <canvas id="${chartId}"></canvas>
                </div>
            `;
            chartCardCol.appendChild(chartCard);
            chartsRenderContainer.appendChild(chartCardCol);
            
            const ctx = document.getElementById(chartId).getContext('2d');
            let chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'bottom' } } };

            if (chartType === 'pie' && Object.keys(answerCounts).length > 7) {
                 chartOptions.plugins.legend.display = false;
            }
            
            const chartData = {
                labels: Object.keys(answerCounts),
                datasets: [{
                    label: `Respuestas`, 
                    data: Object.values(answerCounts),
                    backgroundColor: generateColors(Object.keys(answerCounts).length),
                    borderColor: generateColors(Object.keys(answerCounts).length, true), 
                    borderWidth: 1,
                    hoverOffset: 4
                }]
            };
            
            if (chartType === 'bar') { 
                chartOptions.scales = { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }; 
            }

            activeCharts.push(new Chart(ctx, { type: chartType, data: chartData, options: chartOptions }));
        }
        if (chartCount === 0 && chartsRenderContainer.innerHTML === '') { 
             chartsRenderContainer.innerHTML = '<p class="text-center text-info col-12">No hay preguntas con datos adecuados para generar gráficos.</p>';
        }
    }

    function renderIndividualResponsesTable(responsesData, fieldsStructure) {
        individualResponsesTableContainer.innerHTML = '';
        if (!responsesData || responsesData.length === 0) {
            individualResponsesTableContainer.innerHTML = '<p class="text-muted text-center">No hay respuestas registradas para este formulario.</p>';
            return;
        }

        // Crear tabla con clases modernas
        const table = document.createElement('table');
        table.className = 'table table-striped table-hover table-custom align-middle';
        table.style.width = '100%';
        
        const thead = document.createElement('thead');
        const tbody = document.createElement('tbody');
        
        // Crear encabezados fijos
        const headerRow = thead.insertRow();
        
        // Añadir columnas estándar
        const standardColumns = ['ID', 'IP', 'Fecha/Hora'];
        standardColumns.forEach(colName => {
            const th = document.createElement('th');
            th.textContent = colName;
            th.scope = 'col';
            headerRow.appendChild(th);
        });
        
        // Añadir columnas para cada campo del formulario
        fieldsStructure.forEach(field => {
            if (field.type === 'birthdate') {
                // Columna para la fecha de nacimiento
                const thFecha = document.createElement('th');
                thFecha.textContent = 'Fecha de Nacimiento';
                thFecha.scope = 'col';
                headerRow.appendChild(thFecha);
                // Columna para la edad
                const thEdad = document.createElement('th');
                thEdad.textContent = 'Edad';
                thEdad.scope = 'col';
                headerRow.appendChild(thEdad);
            } else {
                const th = document.createElement('th');
                th.textContent = field.label;
                th.scope = 'col';
                headerRow.appendChild(th);
            }
        });
        
        // Añadir filas de datos con animación
        responsesData.forEach((response, index) => {
            const row = tbody.insertRow();
            row.className = 'animate__animated animate__fadeIn';
            row.style.animationDelay = `${index * 0.05}s`;
            
            
            // Columnas estándar
            let cell = row.insertCell();
            cell.textContent = response.submission_id || 'N/A';
            
            cell = row.insertCell();
            cell.textContent = response.ip_address || 'N/A';
            
            cell = row.insertCell();
            try {
                cell.textContent = response.submitted_at ? new Date(response.submitted_at).toLocaleString('es-ES') : 'N/A';
            } catch(e) { cell.textContent = response.submitted_at; }
            
            // Columnas de respuestas

            fieldsStructure.forEach(field => {
                if (field.type === 'birthdate') {
                    // Fecha de nacimiento original (si existe en los datos crudos)
                    let fechaNacimiento = '';
                    let edad = '';
                    // Buscar en los datos crudos si existe la fecha original
                    if (response.data && response.data._raw_birthdate) {
                        fechaNacimiento = response.data._raw_birthdate;
                    } else if (response.data && response.data[field.label + ' (fecha)']) {
                        fechaNacimiento = response.data[field.label + ' (fecha)'];
                    }
                    // Edad (el valor guardado)
                    edad = response.data[field.label];
                    // Columna fecha
                    let cellFecha = row.insertCell();
                    if (fechaNacimiento) {
                        cellFecha.innerHTML = `<span class='badge bg-info text-dark'>${fechaNacimiento}</span>`;
                    } else {
                        cellFecha.innerHTML = `<span class='badge bg-secondary'>N/D</span>`;
                    }
                    // Columna edad
                    let cellEdad = row.insertCell();
                    if (edad !== undefined && edad !== null && edad !== '') {
                        cellEdad.innerHTML = `<span class='badge bg-primary'>${edad} años</span>`;
                    } else {
                        cellEdad.innerHTML = `<span class='badge bg-secondary'>N/D</span>`;
                    }
                } else if (field.type === 'file') {
                    let answer = response.data[field.label];
                    let cell = row.insertCell();
                    if (answer) {
                        cell.innerHTML = `<a href="uploads/${answer}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i> Descargar</a>`;
                    } else {
                        cell.textContent = '';
                    }
                } else {
                    let answer = response.data[field.label];
                    let cell = row.insertCell();
                    if (Array.isArray(answer)) {
                        cell.textContent = answer.join(', ');
                    } else {
                        cell.textContent = answer ?? '';
                    }
                }
            });
        });
        
        // Ensamblar la tabla
        table.appendChild(thead);
        table.appendChild(tbody);
        individualResponsesTableContainer.innerHTML = '';
        individualResponsesTableContainer.appendChild(table);
        // Inicializar DataTables para mejoras responsivas y de búsqueda
        try {
            if ($.fn.DataTable.isDataTable(table)) {
                $(table).DataTable().destroy(true);
            }
            $(table).DataTable({
                language: {
                    decimal: "",
                    emptyTable: "No hay respuestas disponibles",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ respuestas",
                    infoEmpty: "Mostrando 0 respuestas",
                    infoFiltered: "(filtrado de _MAX_ respuestas totales)",
                    lengthMenu: "Mostrar _MENU_ respuestas",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron respuestas coincidentes",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                }
            });
        } catch (e) {
            console.error("Error inicializando DataTables para respuestas individuales:", e);
        }

        // Inicializar tooltips Bootstrap tras cada draw de la tabla de respuestas individuales:
        const tooltipTriggerList = document.querySelectorAll('#individualResponsesTable [data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    function generateColors(count, forBorder = false) {
        const colors = [];
        const baseColors = [
            'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199,  0.7)', 'rgba(83, 102, 255, 0.7)', 'rgba(40, 159, 64, 0.7)',
            'rgba(210, 99, 132, 0.7)' 
        ];
         const borderColors = [ 
            'rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(255, 206, 86)',
            'rgb(75, 192, 192)', 'rgb(153, 102, 255)', 'rgb(255, 159, 64)',
            'rgb(199, 199, 199)', 'rgb(83, 102, 255)', 'rgb(40, 159, 64)',
            'rgb(210, 99, 132)'
        ];
        const source = forBorder ? borderColors : baseColors;
        for (let i = 0; i < count; i++) {
            colors.push(source[i % source.length]);
        }
        return colors;
    }

    if (exportResponsesCsvBtn) {
        exportResponsesCsvBtn.addEventListener('click', function() {
            if (this.classList.contains('disabled') || !currentFormIdForAnalysis) {
                showToast('No hay formulario seleccionado o no hay respuestas para exportar.', true);
                return;
            }
            window.location.href = `api/responses.php?action=export_csv&form_id=${currentFormIdForAnalysis}`;
        });
    }

    if (closeChartsSectionBtn && chartsSection) { 
        closeChartsSectionBtn.addEventListener('click', () => {
            if (chartsSection) chartsSection.style.display = 'none'; 
            clearAnalysisData(); 
        });
    }

    // --- Profile Image Navbar Updater ---
    function updateUserNavbarProfileImage() {
        const profileImageElement = document.getElementById('userProfileImage'); // CORREGIDO ID
        const profileIconElement = document.getElementById('userProfileIconPlaceholder'); // CORREGIDO ID

        if (!profileImageElement || !profileIconElement) {
            console.error('Navbar image or icon element NOT FOUND'); // Keep error for critical issues
            return; 
        }

        // Asumimos que currentUser.profileImageUrl ya tiene el timestamp si es necesario
        // y que currentUser.userInitial ya está disponible
        const hasImage = profileImageElement.getAttribute('data-has-image') === 'true';
        const imageUrl = profileImageElement.src; // Obtener la URL actual de la imagen

        if (currentUser && typeof currentUser.profileImageUrl !== 'undefined') { // Verificar si la info del usuario está cargada
            if (currentUser.profileImageUrl && currentUser.profileImageUrl.trim() !== '' && hasImage) {
                if (profileImageElement.src !== currentUser.profileImageUrl) { // Solo actualizar si la URL es diferente
                    profileImageElement.src = currentUser.profileImageUrl;
                }
                profileImageElement.style.display = 'block'; // O 'inline-block' o lo que corresponda
                profileIconElement.style.display = 'none';
            } else {
                profileImageElement.style.display = 'none';
                profileIconElement.style.display = 'block'; // O 'inline-block'
                if (currentUser.userInitial && profileIconElement.textContent !== currentUser.userInitial) {
                    profileIconElement.textContent = currentUser.userInitial;
                }
            }
        } else {
            // Si currentUser no está listo, confiar en lo que PHP renderizó inicialmente
            // o mostrar el icono por defecto si la imagen falló al cargar (manejado por onerror en HTML)
            // No hacer nada aquí explícitamente para evitar sobreescribir el estado inicial antes de tiempo
            // console.warn('currentUser data not available yet for navbar update.');
        }
    }    // Llamar a la función para establecer la imagen de perfil en la carga inicial
    // Esto se ejecutará después de que el DOM esté listo y currentUser (definido en el HTML) esté disponible.
    updateUserNavbarProfileImage();    // Event listener para cerrar popovers del botón "Gestionar Permisos" al hacer clic fuera
    document.addEventListener('click', function(e) {
        // Verificar si el clic es fuera de un popover de gestión de permisos
        if (!e.target.closest('.manage-permissions-btn') && !e.target.closest('.popover')) {
            const managePermissionsButtons = document.querySelectorAll('.manage-permissions-btn[data-bs-toggle="popover"]');
            managePermissionsButtons.forEach(function(btn) {
                const popover = bootstrap.Popover.getInstance(btn);
                if (popover && popover._element.getAttribute('data-bs-trigger') === 'click') {
                    popover.hide();
                }
            });
        }
    });

    // Lógica para que solo uno de los dos métodos esté activo en el campo 'downloadable'
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('downloadable-file-input')) {
            const fileInput = e.target;
            const urlInput = fileInput.closest('.mb-2').querySelector('.downloadable-url-input');
            if (fileInput.files && fileInput.files.length > 0) {
                urlInput.value = '';
                urlInput.disabled = true;
            } else {
                urlInput.disabled = false;
            }
        }
        if (e.target.classList.contains('downloadable-url-input')) {
            const urlInput = e.target;
            const fileInput = urlInput.closest('.mb-2').querySelector('.downloadable-file-input');
            if (urlInput.value.trim() !== '') {
                fileInput.value = '';
                fileInput.disabled = true;
            } else {
                fileInput.disabled = false;
            }
        }
    });    // --- Gestión de Permisos de Edición (Owner/Admin) ---
    let currentFormIdForPermissions = null;
    let allEditorsList = [];    // --- Gestión de Permisos Cruzados Entre Áreas ---
    let currentFormIdForCrossPermissions = null;
    let currentFormAreaId = null;
    let availableEditorsForCrossPermissions = [];
    let activeCrossPermissions = [];
    let crossPermissionsHistory = [];
    let allAreasData = [];// Modal dinámico para gestión de permisos
    function ensurePermissionsModal() {
        if (document.getElementById('managePermissionsModal')) return;
        const modalHtml = `
        <div class="modal fade" id="managePermissionsModal" tabindex="-1" aria-labelledby="managePermissionsModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
              <div class="modal-header bg-gradient bg-primary text-white py-3">
                <h5 class="modal-title" id="managePermissionsModalLabel"><i class="fas fa-user-shield me-2"></i>Gestionar Permisos de Edición</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body p-4">
                <!-- Banner de información mejorado -->
                <div class="alert alert-info d-flex position-relative overflow-hidden" style="border-left: 4px solid #0dcaf0;">
                  <div class="flex-shrink-0 me-3">
                    <i class="fas fa-info-circle fa-2x text-info"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h5 class="alert-heading fw-bold">Sistema RBAC Multi-Área</h5>
                    <p class="mb-1" id="permissionsModalHelp">Selecciona qué usuarios tendrán acceso de edición a este formulario. Los cambios se aplican automáticamente.</p>
                    <small class="d-block mb-2">Los permisos de edición permiten a otros usuarios modificar este formulario aunque no sean sus creadores originales.</small>
                    
                    <!-- Badges informativos con mejor diseño -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                      <span class="badge bg-success d-inline-flex align-items-center px-3 py-2">
                        <i class="fas fa-check-circle me-2"></i> Edición compartida
                      </span>
                      <span class="badge bg-info d-inline-flex align-items-center px-3 py-2">
                        <i class="fas fa-shield-alt me-2"></i> Permisos auditables
                      </span>
                      <span class="badge bg-warning text-dark d-inline-flex align-items-center px-3 py-2">
                        <i class="fas fa-user-edit me-2"></i> Colaboración segura
                      </span>
                    </div>
                  </div>
                  
                  <!-- Decoración de fondo -->
                  <div class="position-absolute opacity-10" style="right: -20px; bottom: -20px;">
                    <i class="fas fa-shield-alt fa-5x"></i>
                  </div>
                </div>
                
                <!-- Buscador de editores mejorado -->
                <div class="input-group mb-4">
                  <span class="input-group-text border-end-0"><i class="fas fa-search text-muted"></i></span>
                  <input type="text" id="searchEditors" class="form-control border-start-0 shadow-none" placeholder="Buscar usuarios por nombre...">
                  <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchEditors" title="Limpiar búsqueda">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                
                <!-- Filtro rápido por rol con diseño mejorado -->
                <div class="mb-4">
                  <div class="btn-group btn-group-sm w-100" role="group" aria-label="Filtrar por rol">
                    <button type="button" class="btn btn-outline-secondary filter-role-btn active py-2" data-role="all">
                      <i class="fas fa-users me-1"></i> Todos 
                      <span class="badge bg-secondary ms-1" id="count-all">0</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary filter-role-btn py-2" data-role="admin">
                      <i class="fas fa-user-shield me-1"></i> Administradores 
                      <span class="badge bg-primary ms-1" id="count-admin">0</span>
                    </button>
                    <button type="button" class="btn btn-outline-warning filter-role-btn py-2" data-role="editor">
                      <i class="fas fa-user-pen me-1"></i> Editores 
                      <span class="badge bg-warning text-dark ms-1" id="count-editor">0</span>
                    </button>
                  </div>
                </div>
                
                <!-- Lista de editores con mejor estilo -->
                <div id="permissionsEditorsList" class="row g-3"></div>
                
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                  <div class="form-text d-flex align-items-center">
                    <span class="text-warning me-2"><i class="fas fa-lightbulb"></i></span>
                    <small>Los cambios de permisos se guardan automáticamente y son auditables.</small>
                  </div>
                  <div>
                    <span class="badge bg-success d-flex align-items-center px-3 py-2 shadow-sm" id="editorsSelectedCount">
                      <i class="fas fa-users me-2"></i> 0 seleccionados
                    </span>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                  <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                  <i class="fas fa-check me-1"></i> Finalizar
                </button>
              </div>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Añadir event listeners para la búsqueda y filtros
        const searchInput = document.getElementById('searchEditors');
        if (searchInput) {
            searchInput.addEventListener('input', filterEditorsList);
        }
        
        const clearButton = document.getElementById('clearSearchEditors');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                filterEditorsList();
            });
        }
        
        // Event listeners para botones de filtro
        document.querySelectorAll('.filter-role-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-role-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterEditorsList();
            });
        });
    }
    
    // Función para filtrar la lista de editores
    function filterEditorsList() {
        const searchText = document.getElementById('searchEditors')?.value.toLowerCase() || '';
        const selectedRole = document.querySelector('.filter-role-btn.active')?.dataset.role || 'all';
        
        let countAll = 0;
        let countAdmin = 0;
        let countEditor = 0;
        let countSelected = 0;
        
        document.querySelectorAll('#permissionsEditorsList .editor-item').forEach(item => {
            const username = item.querySelector('.editor-username')?.textContent.toLowerCase() || '';
            const role = item.dataset.role;
            const isChecked = item.querySelector('.perm-editor-checkbox')?.checked || false;
            
            const matchesSearch = username.includes(searchText);
            const matchesFilter = selectedRole === 'all' || role === selectedRole;
            
            if (matchesSearch && matchesFilter) {
                item.style.display = '';
                
                // Actualizar contadores
                countAll++;
                if (role === 'admin') countAdmin++;
                if (role === 'editor') countEditor++;
                if (isChecked) countSelected++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Actualizar badges de conteo
        document.getElementById('count-all').textContent = countAll;
        document.getElementById('count-admin').textContent = countAdmin;
        document.getElementById('count-editor').textContent = countEditor;
        
        // Actualizar contador de seleccionados con mejor formato y estilo
        const selectedEl = document.getElementById('editorsSelectedCount');
        if (selectedEl) {
            selectedEl.innerHTML = `<i class="fas fa-users me-2"></i> ${countSelected} usuario${countSelected !== 1 ? 's' : ''} con acceso`;
            
            if (countSelected > 0) {
                selectedEl.classList.remove('bg-secondary');
                selectedEl.classList.add('bg-success');
            } else {
                selectedEl.classList.remove('bg-success');
                selectedEl.classList.add('bg-secondary');
            }
        }
    }    // Función para cargar la lista de editores y mostrar el modal de permisos
    function loadEditorsListAndShow(formId, currentEditorIds, formTitle) {
        // Asegurar que el modal exista
        ensurePermissionsModal();
        
        // Almacenar IDs para uso global
        currentFormIdForPermissions = formId;
        
        // Actualizar título del modal
        const modalLabel = document.getElementById('managePermissionsModalLabel');
        if (modalLabel) {
            modalLabel.innerHTML = `<i class="fas fa-user-shield me-2"></i>Gestionar Permisos: ${formTitle}`;
        }
        
        // Actualizar texto de ayuda
        const helpText = document.getElementById('permissionsModalHelp');
        if (helpText) {
            helpText.textContent = `Selecciona qué usuarios tendrán acceso de edición al formulario "${formTitle}". Los cambios se aplican automáticamente.`;
        }
        
        // Cargar lista de usuarios
        fetch('api/users.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar usuarios');
                }
                
                allEditorsList = data.data || [];
                const editorsList = document.getElementById('permissionsEditorsList');
                
                if (!editorsList) {
                    throw new Error('No se encontró el contenedor de editores');
                }
                
                // Limpiar lista
                editorsList.innerHTML = '';
                
                // Filtrar usuarios válidos (admin y editor)
                const validUsers = allEditorsList.filter(user => 
                    user.role === 'admin' || user.role === 'editor'
                );
                
                if (validUsers.length === 0) {
                    editorsList.innerHTML = '<div class="col-12"><div class="alert alert-warning">No hay usuarios disponibles para asignar permisos.</div></div>';
                    return;
                }
                
                // Renderizar cada usuario
                validUsers.forEach(user => {
                    const isSelected = currentEditorIds.includes(user.id);
                    const roleIcon = user.role === 'admin' ? 'fas fa-user-shield text-primary' : 'fas fa-user-pen text-warning';
                    const roleText = user.role === 'admin' ? 'Administrador' : 'Editor';
                    const roleBadgeClass = user.role === 'admin' ? 'bg-primary' : 'bg-warning text-dark';
                      const userCard = `
                        <div class="col-md-6 editor-item" data-role="${user.role}">
                            <div class="card h-100 ${isSelected ? 'border-success bg-success bg-opacity-10' : 'border-light'} shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">                                        <div class="flex-shrink-0 me-3">
                                            <div class="position-relative">
                                                ${user.profile_image_url ? `
                                                    <img src="${user.profile_image_url}" 
                                                        alt="${user.username}" 
                                                        class="rounded-circle" 
                                                        width="40" height="40" 
                                                        style="object-fit: cover;"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="rounded-circle bg-primary text-white d-none align-items-center justify-content-center" 
                                                        style="width: 40px; height: 40px; font-size: 16px; font-weight: bold;">
                                                        ${user.username.charAt(0).toUpperCase()}
                                                    </div>
                                                ` : `
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                        style="width: 40px; height: 40px; font-size: 16px; font-weight: bold;">
                                                        ${user.username.charAt(0).toUpperCase()}
                                                    </div>
                                                `}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-1 editor-username">${user.username}</h6>
                                            <span class="badge ${roleBadgeClass}">
                                                <i class="${roleIcon} me-1"></i>${roleText}
                                            </span>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div class="form-check">
                                                <input class="form-check-input perm-editor-checkbox" 
                                                    type="checkbox" 
                                                    value="${user.id}" 
                                                    id="editor_${user.id}"
                                                    ${isSelected ? 'checked' : ''}>
                                                <label class="form-check-label" for="editor_${user.id}">
                                                    <span class="visually-hidden">Permitir edición</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge ${isSelected ? 'bg-success' : 'bg-secondary'} ms-2 py-2 px-3">
                                            <i class="fas ${isSelected ? 'fa-check-circle' : 'fa-times-circle'} me-1"></i>
                                            ${isSelected ? 'Con acceso' : 'Sin acceso'}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    editorsList.insertAdjacentHTML('beforeend', userCard);
                });
                
                // Actualizar contadores
                filterEditorsList();
                
                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('managePermissionsModal'));
                modal.show();
                
            })
            .catch(error => {
                console.error('Error al cargar editores:', error);
                showToast(`Error al cargar usuarios: ${error.message}`, true);
            });
    }    // Delegar click en el botón Permisos
    $(document).on('click', '.manage-permissions-btn', function() {
        // Ocultar el popover antes de abrir el modal
        const popover = bootstrap.Popover.getInstance(this);
        if (popover) {
            popover.hide();
        }
        
        const formId = $(this).data('form-id');
        const formTitle = $(this).data('form-title');
        let creatorIds = $(this).data('creator-ids');
        if (typeof creatorIds === 'string') {
            try { creatorIds = JSON.parse(creatorIds); } catch { creatorIds = []; }
        }
        loadEditorsListAndShow(formId, creatorIds, formTitle);
    });// Delegar cambios en los checkboxes de permisos
    $(document).on('change', '.perm-editor-checkbox', function() {
        const editorId = this.value;
        const checked = this.checked;
        if (!currentFormIdForPermissions) return;
        
        // Referencias a elementos para feedback visual
        const cardElement = this.closest('.card');
        const statusBadge = cardElement.querySelector('.badge.ms-2');
        const originalCardClass = cardElement.className;
        const editorName = cardElement.querySelector('.editor-username')?.textContent || 'el usuario';
        
        // Actualizar visualmente la interfaz de inmediato (optimistic UI)
        if (checked) {
            cardElement.classList.add('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            if (statusBadge) {
                statusBadge.className = 'badge bg-success ms-2 py-2 px-3 shadow-sm';
                statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Con acceso';
            }
        } else {
            cardElement.classList.remove('border-success', 'bg-success', 'bg-opacity-10', 'shadow-sm');
            cardElement.classList.add('border-light');
            if (statusBadge) {
                statusBadge.className = 'badge bg-secondary ms-2 py-2 px-3 opacity-50 shadow-sm';
                statusBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>Sin acceso';
            }
        }
        
        // Mostrar indicador de carga en el checkbox con animación mejorada
        this.disabled = true;
        const spinnerSize = 12; // Tamaño del spinner en px
        const originalPadding = window.getComputedStyle(this).padding;
        this.style.padding = `${spinnerSize}px`;
        this.style.backgroundImage = 'url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\' viewBox=\'0 0 40 40\'%3E%3Cpath opacity=\'.2\' fill=\'%23007bff\' d=\'M20.201 5.169c-8.254 0-14.946 6.692-14.946 14.946 0 8.255 6.692 14.946 14.946 14.946s14.946-6.691 14.946-14.946c-.001-8.254-6.692-14.946-14.946-14.946zm0 26.58c-6.425 0-11.634-5.208-11.634-11.634 0-6.425 5.209-11.634 11.634-11.634 6.425 0 11.633 5.209 11.633 11.634 0 6.426-5.208 11.634-11.633 11.634z\'/%3E%3Cpath fill=\'%23007bff\' d=\'M26.013 10.047l1.654-2.866a14.855 14.855 0 00-7.466-2.012v3.312c2.119 0 4.1.576 5.812 1.566z\'%3E%3CanimateTransform attributeName=\'transform\' type=\'rotate\' from=\'0 20 20\' to=\'360 20 20\' dur=\'0.8s\' repeatCount=\'indefinite\'/%3E%3C/path%3E%3C/svg%3E")';
        this.style.backgroundSize = `${spinnerSize*2}px`;
        this.style.backgroundPosition = 'center';
        this.style.backgroundRepeat = 'no-repeat';
          
        // Actualizar contador de seleccionados con formato mejorado
        const selectedCount = document.querySelectorAll('.perm-editor-checkbox:checked').length;
        const countBadge = document.getElementById('editorsSelectedCount');
        if (countBadge) {
            countBadge.innerHTML = `<i class="fas fa-users me-2"></i> ${selectedCount} usuario${selectedCount !== 1 ? 's' : ''} con acceso`;
            
            // Aplicar clase según la cantidad seleccionada
            if (selectedCount > 0) {
                countBadge.classList.remove('bg-secondary');
                countBadge.classList.add('bg-success');
            } else {
                countBadge.classList.remove('bg-success');
                countBadge.classList.add('bg-secondary');
            }
        }
        
        // Ejecutar acción en el servidor
        const url = checked ? 
            `api/forms.php?action=add_editor&form_id=${currentFormIdForPermissions}` : 
            `api/forms.php?action=remove_editor&form_id=${currentFormIdForPermissions}`;
        
        const formData = new FormData();
        formData.append('user_id', editorId);
        
        fetch(url, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    // Restaurar estilo normal del checkbox pero mantener el estado
                    this.disabled = false;
                    this.style.padding = originalPadding;
                    this.style.backgroundImage = '';
                    
                    // Notificar éxito con toast mejorado y feedback más completo
                    if (checked) {
                        showToast(`
                            <div class="d-flex align-items-start">
                                <div class="me-3 fs-4"><i class="fas fa-user-check text-success"></i></div>
                                <div>
                                    <strong class="d-block mb-1 fs-5">Permiso concedido</strong>
                                    <p class="mb-0">Se ha otorgado acceso de edición a <strong>${editorName}</strong>.</p>
                                    <small class="text-muted mt-1 d-block">El usuario recibirá notificación sobre este cambio.</small>
                                </div>
                            </div>
                        `, false, true);
                    } else {
                        showToast(`
                            <div class="d-flex align-items-start">
                                <div class="me-3 fs-4"><i class="fas fa-user-slash text-warning"></i></div>
                                <div>
                                    <strong class="d-block mb-1 fs-5">Permiso revocado</strong>
                                    <p class="mb-0">Se ha revocado el acceso de edición a <strong>${editorName}</strong>.</p>
                                    <small class="text-muted mt-1 d-block">El usuario recibirá notificación sobre este cambio.</small>
                                </div>
                            </div>
                        `, false, true);
                    }
                        
                    // Recargar tabla en segundo plano
                    reloadFormsTable();
                } else {
                    // Restaurar estado anterior en caso de error
                    this.checked = !checked;
                    cardElement.className = originalCardClass;
                    
                    if (statusBadge) {
                        if (!checked) {
                            statusBadge.className = 'badge bg-success ms-2 py-2 px-3 shadow-sm';
                            statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Con acceso';
                        } else {
                            statusBadge.className = 'badge bg-secondary ms-2 py-2 px-3 opacity-50 shadow-sm';
                            statusBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>Sin acceso';
                        }
                    }
                    
                    // Restaurar estilo normal del checkbox
                    this.disabled = false;
                    this.style.padding = originalPadding;
                    this.style.backgroundImage = '';
                    
                    // Notificar error
                    showToast(`
                        <div class="d-flex align-items-start">
                            <div class="me-3 fs-4"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                            <div>
                                <strong class="d-block mb-1 fs-5">Error al actualizar permisos</strong>
                                <p class="mb-0">${result.message || 'No se pudo cambiar el permiso de edición. Inténtalo de nuevo.'}</p>
                            </div>
                        </div>
                    `, true, true);
                }
            })
            .catch(() => {
                // Restaurar estado anterior en caso de error
                this.checked = !checked;
                cardElement.className = originalCardClass;
                
                if (statusBadge) {
                    if (!checked) {
                        statusBadge.className = 'badge bg-success ms-2 py-2 px-3 shadow-sm';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Con acceso';
                    } else {
                        statusBadge.className = 'badge bg-secondary ms-2 py-2 px-3 opacity-50 shadow-sm';
                        statusBadge.innerHTML = '<i class="fas fa-times-circle me-1"></i>Sin acceso';
                    }
                }
                
                // Restaurar estilo normal del checkbox
                this.disabled = false;
                this.style.padding = originalPadding;
                this.style.backgroundImage = '';
                
                // Notificar error de conexión
                showToast(`
                    <div class="d-flex align-items-start">
                        <div class="me-3 fs-4"><i class="fas fa-wifi-slash text-danger"></i></div>
                        <div>
                            <strong class="d-block mb-1 fs-5">Error de conexión</strong>
                            <p class="mb-0">No se pudo establecer conexión con el servidor. Verifica tu conexión e inténtalo nuevamente.</p>
                        </div>
                    </div>
                `, true, true);            });
    });

    // --- GESTIÓN DE PERMISOS CRUZADOS ENTRE ÁREAS ---

    // Función para cargar datos iniciales de áreas
    function loadAreasData() {
        return fetch('api/areas.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allAreasData = data.data || [];
                    return allAreasData;
                } else {
                    throw new Error(data.message || 'Error al cargar áreas');
                }
            });
    }

    // Función para cargar editores disponibles para permisos cruzados
    function loadAvailableEditorsForCrossPermissions(excludeAreaId) {
        return fetch('api/users.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar usuarios');
                }
                
                const allUsers = data.data || [];
                
                // Filtrar solo editores que NO pertenezcan al área del formulario
                availableEditorsForCrossPermissions = allUsers.filter(user => {
                    // Solo editores
                    if (user.role !== 'editor') return false;
                    
                    // Excluir editores del área del formulario
                    const userAreas = user.areas_editor || [];
                    return !userAreas.includes(excludeAreaId);
                });
                
                return availableEditorsForCrossPermissions;
            });
    }

    // Función para cargar permisos cruzados existentes
    function loadCrossPermissions(formId) {
        return fetch(`api/forms.php?action=get_cross_area_permissions&form_id=${formId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const permissionsData = data.data;
                    activeCrossPermissions = permissionsData.permisos_activos || [];
                    crossPermissionsHistory = permissionsData.historial_completo || [];
                    return permissionsData;
                } else {
                    throw new Error(data.message || 'Error al cargar permisos cruzados');
                }
            });
    }

    // Función para renderizar la lista de editores disponibles
    function renderAvailableEditorsForCross() {
        const container = document.getElementById('crossPermissionsUsersList');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (availableEditorsForCrossPermissions.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay editores de otras áreas disponibles para asignar permisos cruzados.
                    </div>
                </div>
            `;
            return;
        }
        
        availableEditorsForCrossPermissions.forEach(user => {
            // Verificar si ya tiene permiso activo
            const hasActivePermission = activeCrossPermissions.some(p => p.user_id === user.id);
            
            // Obtener áreas del usuario
            const userAreas = user.areas_editor || [];
            const areasText = userAreas.map(areaId => {
                const area = allAreasData.find(a => a.id === areaId);
                return area ? area.name : areaId;
            }).join(', ') || 'Sin área asignada';
            
            const userCard = `
                <div class="col-md-6">
                    <div class="card h-100 ${hasActivePermission ? 'border-warning bg-warning bg-opacity-10' : 'border-light'} shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="position-relative">
                                        ${user.profile_image_url ? `
                                            <img src="${user.profile_image_url}" 
                                                alt="${user.username}" 
                                                class="rounded-circle" 
                                                width="40" height="40" 
                                                style="object-fit: cover;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="rounded-circle bg-secondary text-white d-none align-items-center justify-content-center" 
                                                style="width: 40px; height: 40px; font-size: 16px; font-weight: bold;">
                                                ${user.username.charAt(0).toUpperCase()}
                                            </div>
                                        ` : `
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                style="width: 40px; height: 40px; font-size: 16px; font-weight: bold;">
                                                ${user.username.charAt(0).toUpperCase()}
                                            </div>
                                        `}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">${user.username}</h6>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-user-pen me-1"></i>Editor
                                    </span>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-layer-group me-1"></i>${areasText}
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="btn ${hasActivePermission ? 'btn-outline-danger' : 'btn-outline-warning'} btn-sm cross-permission-toggle-btn"
                                        data-user-id="${user.id}"
                                        data-username="${user.username}"
                                        data-action="${hasActivePermission ? 'revoke' : 'assign'}">
                                        <i class="fas ${hasActivePermission ? 'fa-user-minus' : 'fa-user-plus'} me-1"></i>
                                        ${hasActivePermission ? 'Revocar' : 'Asignar'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', userCard);
        });
    }

    // Función para renderizar permisos activos
    function renderActiveCrossPermissions() {
        const container = document.getElementById('crossPermissionsActiveList');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (activeCrossPermissions.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-user-slash fs-2 mb-2"></i>
                        <p>No hay permisos cruzados activos para este formulario.</p>
                    </div>
                </div>
            `;
            return;
        }
        
        activeCrossPermissions.forEach(permission => {
            const assignedDate = new Date(permission.fecha).toLocaleDateString('es-ES', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
            
            const permissionCard = `
                <div class="col-md-6">
                    <div class="card border-warning bg-warning bg-opacity-10">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" 
                                        style="width: 35px; height: 35px;">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">${permission.username}</h6>
                                    <small class="text-muted">
                                        Asignado por ${permission.asignado_por_username}<br>
                                        ${assignedDate}
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <button class="btn btn-outline-danger btn-sm cross-permission-toggle-btn"
                                        data-user-id="${permission.user_id}"
                                        data-username="${permission.username}"
                                        data-action="revoke">
                                        <i class="fas fa-user-minus me-1"></i>Revocar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', permissionCard);
        });
    }

    // Función para renderizar historial de permisos
    function renderCrossPermissionsHistory() {
        const container = document.getElementById('crossPermissionsHistoryList');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (crossPermissionsHistory.length === 0) {
            container.innerHTML = `
                <div class="text-muted text-center py-3">
                    <i class="fas fa-history fs-3 mb-2"></i>
                    <p>No hay historial de permisos cruzados para este formulario.</p>
                </div>
            `;
            return;
        }
        
        crossPermissionsHistory.forEach((entry, index) => {
            const entryDate = new Date(entry.fecha).toLocaleDateString('es-ES', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
            
            const isAssigned = entry.accion === 'asignado';
            const iconClass = isAssigned ? 'fa-user-plus text-success' : 'fa-user-minus text-danger';
            const actionText = isAssigned ? 'Asignado' : 'Revocado';
            
            const historyEntry = `
                <div class="d-flex align-items-center py-2 ${index !== crossPermissionsHistory.length - 1 ? 'border-bottom' : ''}">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas ${iconClass} fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${actionText} a ${entry.username}</div>
                        <small class="text-muted">
                            Por ${entry.asignado_por_username} - ${entryDate}
                        </small>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', historyEntry);
        });
    }

    // Función para cargar filtros de áreas
    function loadAreaFilters() {
        const areaFilter = document.getElementById('crossPermissionsAreaFilter');
        if (!areaFilter) return;
        
        areaFilter.innerHTML = '<option value="">Todas las áreas</option>';
        
        allAreasData.forEach(area => {
            areaFilter.innerHTML += `<option value="${area.id}">${area.name}</option>`;
        });
    }

    // Función para filtrar editores
    function filterCrossPermissionsEditors() {
        const areaFilter = document.getElementById('crossPermissionsAreaFilter').value;
        const userFilter = document.getElementById('crossPermissionsUserFilter').value.toLowerCase();
        
        const cards = document.querySelectorAll('#crossPermissionsUsersList .col-md-6');
        
        cards.forEach(card => {
            const username = card.querySelector('.card-title').textContent.toLowerCase();
            const areasText = card.querySelector('.text-muted').textContent.toLowerCase();
            
            let shouldShow = true;
            
            // Filtro por área
            if (areaFilter) {
                const area = allAreasData.find(a => a.id === areaFilter);
                if (area) {
                    shouldShow = shouldShow && areasText.includes(area.name.toLowerCase());
                }
            }
            
            // Filtro por nombre
            if (userFilter) {
                shouldShow = shouldShow && username.includes(userFilter);
            }
            
            card.style.display = shouldShow ? 'block' : 'none';
        });
    }

    // Función principal para mostrar el modal de permisos cruzados
    function showCrossPermissionsModal(formId, formTitle, formAreaId) {
        currentFormIdForCrossPermissions = formId;
        currentFormAreaId = formAreaId;
        
        // Actualizar información del formulario en el modal
        document.getElementById('crossPermissionsFormTitle').textContent = formTitle;
        
        // Cargar nombre del área
        const area = allAreasData.find(a => a.id === formAreaId);
        document.getElementById('crossPermissionsFormArea').textContent = 
            `Área: ${area ? area.name : 'Área desconocida'}`;
        
        // Mostrar spinner/loading
        const modal = new bootstrap.Modal(document.getElementById('manageCrossPermissionsModal'));
        modal.show();
          // Cargar datos
        Promise.all([
            loadAreasData(),
            loadAvailableEditorsForCrossPermissions(formAreaId),
            loadCrossPermissions(formId)
        ])
        .then(() => {
            renderAvailableEditorsForCross();
            renderActiveCrossPermissions();
            renderCrossPermissionsHistory();
            loadAreaFilters();
        })
        .catch(error => {
            console.error('Error al cargar datos de permisos cruzados:', error);
            showToast(`Error al cargar datos: ${error.message}`, true);
        });
    }

    // Función para asignar/revocar permiso cruzado
    function toggleCrossPermission(userId, username, action) {
        if (!currentFormIdForCrossPermissions) return;
        
        const data = new FormData();
        data.append('form_id', currentFormIdForCrossPermissions);
        data.append('user_id', userId);
        data.append('action_type', action); // Cambiado de 'action' a 'action_type'
        
        return fetch('api/forms.php?action=assign_cross_area_permission', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(`
                    <div class="d-flex align-items-start">
                        <div class="me-3 fs-4"><i class="fas fa-check-circle text-success"></i></div>
                        <div>
                            <strong class="d-block mb-1 fs-5">Permiso ${action === 'assign' ? 'asignado' : 'revocado'}</strong>
                            <p class="mb-0">${result.message}</p>
                        </div>
                    </div>
                `, false, true);
                
                // Recargar datos
                return loadCrossPermissions(currentFormIdForCrossPermissions)
                    .then(() => {
                        renderAvailableEditorsForCross();
                        renderActiveCrossPermissions();
                        renderCrossPermissionsHistory();
                        
                        // Recargar tabla principal si existe
                        if (typeof formsTable !== 'undefined' && formsTable) {
                            formsTable.ajax.reload(null, false);
                        }
                    });
            } else {
                throw new Error(result.message || 'Error al procesar el permiso');
            }
        })
        .catch(error => {
            console.error('Error al gestionar permiso cruzado:', error);
            showToast(`Error: ${error.message}`, true);
        });
    }

    // Event Listeners para permisos cruzados

    // Click en botón de gestionar permisos cruzados
    $(document).on('click', '.manage-cross-permissions-btn', function() {
        const formId = $(this).data('form-id');
        const formTitle = $(this).data('form-title');
        const formAreaId = $(this).data('form-area-id');
        
        // Cargar áreas primero, luego mostrar modal
        loadAreasData()
            .then(() => {
                showCrossPermissionsModal(formId, formTitle, formAreaId);
            })
            .catch(error => {
                console.error('Error al cargar áreas:', error);
                showToast(`Error al cargar datos: ${error.message}`, true);
            });
    });

    // Click en botón de asignar/revocar permiso cruzado
    $(document).on('click', '.cross-permission-toggle-btn', function() {
        const userId = $(this).data('user-id');
        const username = $(this).data('username');
        const action = $(this).data('action');
        
        const btn = $(this);
        const originalContent = btn.html();
        
        // Mostrar loading
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Procesando...');
        
        toggleCrossPermission(userId, username, action)
            .finally(() => {
                // Restaurar botón (se actualizará en el renderizado)
                btn.prop('disabled', false);
            });
    });

    // Filtros de permisos cruzados
    $(document).on('change keyup', '#crossPermissionsAreaFilter, #crossPermissionsUserFilter', function() {
        filterCrossPermissionsEditors();
    });

    // Botón de actualizar permisos cruzados
    $(document).on('click', '#refreshCrossPermissionsBtn', function() {
        if (currentFormIdForCrossPermissions && currentFormAreaId) {
            const btn = $(this);
            const originalContent = btn.html();
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Actualizando...');
            
            Promise.all([
                loadAvailableEditorsForCrossPermissions(currentFormAreaId),
                loadCrossPermissions(currentFormIdForCrossPermissions)
            ])
            .then(() => {
                renderAvailableEditorsForCross();
                renderActiveCrossPermissions();
                renderCrossPermissionsHistory();
                showToast('Datos actualizados correctamente', false);
            })
            .catch(error => {
                console.error('Error al actualizar:', error);
                showToast(`Error al actualizar: ${error.message}`, true);
            })
            .finally(() => {
                btn.prop('disabled', false).html(originalContent);
            });
        }
    });

    // --- Toggle compact/expand fields ---
    const toggleFieldsCompactBtn = document.getElementById('toggleFieldsCompactBtn');
    if (toggleFieldsCompactBtn) {
        let compactMode = false;
        toggleFieldsCompactBtn.addEventListener('click', function() {
            compactMode = !compactMode;
            const fields = document.querySelectorAll('#formFieldsContainer .form-field');
            fields.forEach(field => {
                // Oculta o muestra todo excepto el header (d-flex ...)
                Array.from(field.children).forEach((child, idx) => {
                    if (idx === 0) {
                        child.style.display = '';
                    } else {
                        child.style.display = compactMode ? 'none' : '';
                    }
                });
            });
            this.classList.toggle('btn-outline-secondary', !compactMode);
            this.classList.toggle('btn-outline-primary', compactMode);
            this.innerHTML = compactMode ? '<i class="fas fa-layer-group"></i> Expandir' : '<i class="fas fa-layer-group"></i> Compactar';
        });
    }

}); // Cierre del DOMContentLoaded listener

// --- Spinner helpers ---
function showMainSpinner() {
  const spinner = document.getElementById('mainSpinner');
  if (spinner) spinner.style.display = 'flex';
}
function hideMainSpinner() {
  const spinner = document.getElementById('mainSpinner');
  if (spinner) spinner.style.display = 'none';
}

// --- Mejoras de interacción para permisos RBAC ---
function updatePermissionIndicators() {
    // Esta función actualiza los indicadores visuales de permisos
    document.querySelectorAll('.permission-indicator').forEach(indicator => {
        // Añadir efectos visuales mejorados
        const badge = indicator.querySelector('.permission-badge');
        if (badge) {
            // Animación suave al pasar el mouse
            badge.addEventListener('mouseenter', () => {
                badge.classList.add('shadow-sm');
            });
            badge.addEventListener('mouseleave', () => {
                badge.classList.remove('shadow-sm');
            });
        }
        
        // Mejorar interacción con avatares
        const avatars = indicator.querySelectorAll('.editor-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('mouseenter', () => {
                avatar.style.transform = 'translateY(-5px)';
                avatar.style.zIndex = '20';
            });
            avatar.addEventListener('mouseleave', () => {
                avatar.style.transform = '';
                // Volver al z-index original después de un delay para animación suave
                setTimeout(() => {
                    avatar.style.zIndex = '';
                }, 300);
            });
        });
    });
}

// Llamar a la función cuando el DOM esté listo y después de cada redibujado de tablas
document.addEventListener('DOMContentLoaded', function() {
    // La tabla se inicializará más tarde, así que solo llamamos a la función directamente
    updatePermissionIndicators();
    
    // Añadiremos el evento draw después de que la tabla esté inicializada
    // en la función de inicialización de DataTables
});

// --- Funciones auxiliares globales ---

// Función para calcular el color de contraste óptimo (blanco o negro) para un color de fondo
function getContrastColor(hexColor) {
    // Si hexColor no es un código de color válido, devolver blanco (predeterminado seguro)
    if (!hexColor || typeof hexColor !== 'string' || !hexColor.match(/^#([0-9A-F]{3}){1,2}$/i)) {
        return '#FFFFFF';
    }

    // Convertir hex a RGB
    let r, g, b;
    if (hexColor.length === 4) {
        // Color hexadecimal de 3 dígitos (#RGB)
        r = parseInt(hexColor[1] + hexColor[1], 16);
        g = parseInt(hexColor[2] + hexColor[2], 16);
        b = parseInt(hexColor[3] + hexColor[3], 16);
    } else {
        // Color hexadecimal de 6 dígitos (#RRGGBB)
        r = parseInt(hexColor.substring(1, 3), 16);
        g = parseInt(hexColor.substring(3, 5), 16);
        b = parseInt(hexColor.substring(5, 7), 16);
    }

    // Calcular luminosidad según estándar WCAG
    // https://www.w3.org/TR/WCAG20-TECHS/G17.html#G17-tests
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Si la luminosidad es mayor a 0.5, el fondo es claro y necesitamos texto oscuro
    return luminance > 0.5 ? '#000000' : '#FFFFFF';
}

/**
 * Formatea una fecha para mostrar en tooltips
 * @param {string} dateString - Fecha en formato ISO
 * @returns {string} - Fecha formateada en español
 */
function formatTooltipDate(dateString) {
    if (!dateString) return 'Fecha no disponible';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        console.warn('Error formateando fecha para tooltip:', e);
        return 'Fecha inválida';
    }
}

/**
 * Crea el contenido HTML para tooltip de editor
 * @param {Object} user - Objeto del usuario
 * @param {string} updatedAt - Fecha de última actualización
 * @returns {string} - HTML del tooltip
 */
function createEditorTooltipContent(user, updatedAt = null) {
    if (!user) return 'Editor desconocido';
    
    let roleIcon = '';
    let roleText = '';
    let roleBadge = '';
    const userRole = user.role ? user.role.toLowerCase() : '';

    // Definir iconos y textos según el rol
    switch(userRole) {
        case 'owner':
            roleIcon = '<i class="fas fa-crown text-success me-1"></i>';
            roleText = 'Propietario';
            roleBadge = '<span class="user-role-badge owner-badge ms-1">Propietario</span>';
            break;
        case 'admin':
            roleIcon = '<i class="fas fa-user-shield text-primary me-1"></i>';
            roleText = 'Administrador';
            roleBadge = '<span class="user-role-badge admin-badge ms-1">Admin</span>';
            break;
        case 'editor':
            roleIcon = '<i class="fas fa-user-pen text-warning me-1"></i>';
            roleText = 'Editor';
            roleBadge = '<span class="user-role-badge editor-badge ms-1">Editor</span>';
            break;
        default:
            roleIcon = '<i class="fas fa-user text-secondary me-1"></i>';
            roleText = 'Usuario';
            roleBadge = '<span class="user-role-badge user-badge ms-1">Usuario</span>';
    }

    return `
        <div class="editor-tooltip-header">
            <div class="d-flex align-items-center mb-2">
                ${roleIcon}
                <strong>${user.username || 'Usuario desconocido'}</strong>
                ${roleBadge}
            </div>
            <small class="text-muted d-block">
                <i class="fas fa-edit me-1"></i>Último editor del formulario
            </small>
            ${updatedAt ? `<small class="text-muted d-block mt-1">
                <i class="fas fa-clock me-1"></i>Editado: ${formatTooltipDate(updatedAt)}
            </small>` : ''}
        </div>
    `;
}
