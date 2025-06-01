// js/admin_users.js
$(function() { // <--- Usar solo jQuery document ready en el nivel superior
    console.log('admin_users.js cargado y listo.');

    // Variables globales para tracking
    let areasLoaded = false;

    // Función auxiliar para filtrar valores null/undefined de los arrays de áreas
    function cleanAreaIds(areaIds) {
        if (!Array.isArray(areaIds)) return [];
        return areaIds.filter(id => id !== null && id !== undefined && id !== '');
    }

    // Función para cargar las áreas desde la API (si no están ya cargadas)
    function preloadAreas() {
        return new Promise((resolve, reject) => {
            if (window.formAreas && Object.keys(window.formAreas).length > 0) {
                console.log('Usando información de áreas previamente cargada:', Object.keys(window.formAreas).length);
                areasLoaded = true;
                resolve(window.formAreas);
                return;
            }
            
            console.log('Cargando información de áreas...');
            fetch('api/areas.php?action=list')
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`Error ${res.status}: ${res.statusText}`);
                    }
                    return res.json();
                })
                .then(data => {
                    // Si aún no se ha cargado window.formAreas desde admin.js, lo inicializamos aquí
                    window.formAreas = window.formAreas || {};
                    
                    if (data.success && Array.isArray(data.data)) {
                        data.data.forEach(area => {
                            window.formAreas[area.id] = {
                                id: area.id,
                                name: area.name || 'Área sin nombre',
                                color: area.color || '#4285F4',
                                description: area.description || '',
                                admins: area.admins || [],
                                editors: area.editors || []
                            };
                        });
                        console.log('Áreas cargadas correctamente:', Object.keys(window.formAreas).length);
                        areasLoaded = true;
                        resolve(window.formAreas);
                    } else {
                        const error = new Error('No se recibieron datos válidos de áreas');
                        console.error(error);
                        reject(error);
                    }
                })
                .catch(error => {
                    console.error('Error cargando información de áreas:', error);
                    reject(error);
                });
        });
    }

    // Función para determinar el color de contraste (texto) basado en el color de fondo
    function getContrastColor(hexColor) {
        // Si no hay color, devolver blanco
        if (!hexColor) return '#FFFFFF';
        
        // Eliminar el # si existe
        hexColor = hexColor.replace('#', '');
        
        // Si es formato corto (3 dígitos), convertir a formato largo (6 dígitos)
        if (hexColor.length === 3) {
            hexColor = hexColor[0] + hexColor[0] + hexColor[1] + hexColor[1] + hexColor[2] + hexColor[2];
        }
        
        // Convertir a RGB
        const r = parseInt(hexColor.substr(0, 2), 16);
        const g = parseInt(hexColor.substr(2, 2), 16);
        const b = parseInt(hexColor.substr(4, 2), 16);
        
        // Calcular la luminosidad
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        
        // Retornar blanco o negro según la luminosidad
        return (luminance > 0.5) ? '#000000' : '#FFFFFF';
    }

    // Modals
    const createUserModalEl = document.getElementById('createUserModal');
    const createUserModal = createUserModalEl ? new bootstrap.Modal(createUserModalEl) : null;
    const editUserRoleModalEl = document.getElementById('editUserRoleModal');
    const editUserRoleModal = editUserRoleModalEl ? new bootstrap.Modal(editUserRoleModalEl) : null;
    const confirmDeleteUserModalEl = document.getElementById('confirmDeleteUserModal');
    const confirmDeleteUserModal = confirmDeleteUserModalEl ? new bootstrap.Modal(confirmDeleteUserModalEl) : null;
    
    // Toast Notifications
    const notificationToastEl = document.getElementById('notificationToast'); 
    const notificationToast = notificationToastEl ? new bootstrap.Toast(notificationToastEl, { delay: 3500 }) : null;

    function showUserManagementToast(message, isError = false) {
        if (notificationToast) {
            const toastBody = notificationToastEl.querySelector('.toast-body');
            const toastHeader = notificationToastEl.querySelector('.toast-header');
            const toastIcon = notificationToastEl.querySelector('.toast-header i');

            if (toastBody) toastBody.textContent = message;
            
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
        } else {
            alert((isError ? "Error: " : "Éxito: ") + message);
        }
    }

    let usersTable;
    // --- Spinner helpers ---
    function showMainSpinner() {
      const spinner = document.getElementById('mainSpinner');
      if (spinner) spinner.style.display = 'flex';
    }
    function hideMainSpinner() {
      const spinner = document.getElementById('mainSpinner');
      if (spinner) spinner.style.display = 'none';
    }

    // Inicialización principal - Usar la precarga de áreas antes de inicializar la tabla
    function initializeUsersTable() {
        if (typeof $ !== 'undefined' && $.fn.DataTable && typeof currentUserForAdminUsers !== 'undefined') {
            try {
                showMainSpinner(); // Mostrar spinner antes de cargar DataTable
                if ($.fn.DataTable.isDataTable('#usersTable')) {
                    $('#usersTable').DataTable().destroy(true);
                }
                usersTable = $('#usersTable').DataTable({
                    processing: true,
                    ajax: {
                        url: 'api/users.php?action=list',
                        dataSrc: 'data',
                        error: function(xhr, error, thrown) {
                               showUserManagementToast(`Error al cargar usuarios: ${xhr.responseJSON?.message || thrown || error}`, true);
                            hideMainSpinner();
                        }
                    },
                columns: [
                    { 
                        data: 'profile_image_url', 
                        orderable: false,
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            const placeholderIcon = `<div class="profile-image-placeholder-table" style="width:38px; height:38px; font-size:1.2rem; display:flex; align-items:center; justify-content:center; font-weight:bold; background:#e9ecef; color:#7C3AED; border-radius:50%;">${row.username ? row.username.charAt(0).toUpperCase() : '?'}</div>`;
                            if (data && data !== '/form/profile_images/default.png') {
                                return `<img src="${data}?t=${new Date().getTime()}" alt="Perfil" class="profile-image-table" style="width:38px; height:38px;">`;
                            }
                            return placeholderIcon;
                        }
                    },
                    { 
                        data: 'username',
                        className: 'text-break align-middle',
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let userIcon = '';
                                let roleTitle = '';
                                if (row.role === 'owner') {
                                    userIcon = '<i class="fa-solid fa-crown text-success me-2"></i>';
                                    roleTitle = 'Propietario';
                                } else if (row.role === 'admin') {
                                    userIcon = '<i class="fa-solid fa-user-shield text-primary me-2"></i>';
                                    roleTitle = 'Administrador';
                                } else if (row.role === 'editor') {
                                    userIcon = '<i class="fa-solid fa-user-pen text-warning me-2"></i>';
                                    roleTitle = 'Editor';
                                } else {
                                    userIcon = '<i class="fa-solid fa-user text-secondary me-2"></i>';
                                    roleTitle = 'Usuario';
                                }
                                return `<div class="d-flex align-items-center">${userIcon}<span class="username-tooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="ID: ${row.id} (${roleTitle})">${data}</span></div>`;
                            }
                            return data;
                        }
                    },
                    { 
                        data: 'role',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            let badgeClass = 'bg-secondary';
                            let roleText = data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Desconocido';
                            let icon = '';
                            if (data === 'owner') {
                                badgeClass = 'bg-success'; // Verde para propietario
                                roleText = 'Propietario';
                                icon = '<i class="fa-solid fa-crown me-1"></i>';
                            } else if (data === 'admin') {
                                badgeClass = 'bg-primary'; // Azul para admin
                                roleText = 'Administrador';
                                icon = '<i class="fa-solid fa-user-shield me-1"></i>';
                            } else if (data === 'editor') {
                                badgeClass = 'bg-warning text-dark'; // Amarillo para editor
                                roleText = 'Editor';
                                icon = '<i class="fa-solid fa-user-pen me-1"></i>';
                            }
                            return `<span class="badge ${badgeClass} fs-6 px-3 py-2 shadow-sm">${icon}${roleText}</span>`;
                        }
                    },
                    // Nueva columna para áreas
                    { 
                        data: null,
                        className: 'text-center align-middle',
                        orderable: false,
                        render: function(data, type, row) {
                            // Si es propietario, mostrar un badge con ícono de acceso global
                            if (row.role === 'owner') {
                                return '<span class="badge bg-secondary fs-6 px-3 py-2 shadow-sm"><i class="fas fa-globe me-1"></i>Acceso global</span>';
                            }
                            
                            // Determinar qué array de áreas usar según el rol
                            // Filtrar valores null/undefined de los arrays de áreas
                            const rawAreaIds = row.role === 'admin' ? row.areas_admin || [] : row.areas_editor || [];
                            const areaIds = cleanAreaIds(rawAreaIds);
                            
                            if (areaIds.length === 0) {
                                return '<span class="badge bg-danger fs-6 px-3 py-2 shadow-sm"><i class="fas fa-exclamation-triangle me-1"></i>Sin áreas</span>';
                            }
                            
                            // Recolectar información de áreas para el tooltip
                            const areasInfo = [];
                            const areasDetails = [];
                            let primaryAreaColor = '#4285F4'; // Color por defecto
                            let primaryAreaName = "Áreas";
                            
                            areaIds.forEach((areaId, index) => {
                                // Intentar obtener datos de área de formAreas (definido en admin.js)
                                const area = window.formAreas && window.formAreas[areaId] 
                                    ? window.formAreas[areaId] 
                                    : { id: areaId, name: areaId, color: '#4285F4' };
                                
                                const areaName = area.name || "Área";
                                const areaColor = area.color || '#4285F4';
                                const contrastColor = getContrastColor(areaColor);
                                
                                if (areaName && areaName !== "null" && areaName !== "undefined") {
                                    areasInfo.push(areaName);
                                    
                                    // Crear elemento visual mejorado para el tooltip con íconos más grandes
                                    areasDetails.push(`
                                        <div class="d-flex align-items-center mb-2 p-1 rounded hover-effect" 
                                             style="transition: all 0.2s ease; border-left: 3px solid ${areaColor}; background-color: rgba(${parseInt(areaColor.slice(1, 3), 16)}, ${parseInt(areaColor.slice(3, 5), 16)}, ${parseInt(areaColor.slice(5, 7), 16)}, 0.1);">
                                            <span class="d-inline-block me-2" style="width:16px; height:16px; border-radius:50%; background-color:${areaColor}; box-shadow: 0 2px 4px rgba(0,0,0,0.2);"></span>
                                            <span class="fw-medium">${areaName}</span>
                                        </div>
                                    `);
                                    
                                    // Usar el color y nombre del primer área como principal
                                    if (index === 0) {
                                        primaryAreaColor = areaColor;
                                        primaryAreaName = areaName;
                                    }
                                }
                            });
                            
                            // Calcular color de contraste para el texto
                            const contrastColor = getContrastColor(primaryAreaColor);
                            
                            // Crear contenido avanzado para tooltip con círculos de color e información mejorada
                            // Escapar las comillas para evitar problemas con el atributo title
                            const areasListHtml = areasDetails.join('').replace(/"/g, '&quot;');
                            const tooltipContent = `
                                <div class="tooltip-areas-list py-2 px-1">
                                    <div class="fw-bold mb-2 border-bottom pb-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Áreas asignadas:
                                    </div>
                                    ${areasListHtml}
                                </div>
                            `.replace(/"/g, '&quot;').replace(/'/g, "&#39;").replace(/(\r\n|\n|\r)/gm, "");
                            
                            // Crear badge principal mejorado, igual para ambos roles (admin y editor)
                            let badgeContent = '';
                            let roleIcon = row.role === 'admin' ? 
                                '<i class="fas fa-user-shield me-1"></i>' : 
                                '<i class="fas fa-user-pen me-1"></i>';
                                
                            if (areasInfo.length === 1) {
                                // Solo un área, mostrar su nombre
                                badgeContent = `${roleIcon}<i class="fas fa-layer-group me-1"></i>${primaryAreaName}`;
                            } else {
                                // Múltiples áreas, mostrar contador con efecto de pill badge
                                badgeContent = `${roleIcon}<i class="fas fa-layer-group me-1"></i>${primaryAreaName} <span class="badge ms-1 rounded-pill text-bg-light" style="font-size: 0.75rem;">${areasInfo.length}</span>`;
                            }
                            
                            // Generar un ID único para este badge para poder asociar el tooltip después
                            const badgeId = `area-badge-${row.id}-${Math.floor(Math.random() * 10000)}`;
                            
                            return `<span id="${badgeId}" class="badge fs-6 px-3 py-2 shadow-sm area-tooltip" 
                                data-bs-toggle="tooltip" 
                                data-bs-html="true"
                                data-bs-placement="top" 
                                data-tooltip-content="${tooltipContent}"
                                style="background-color: ${primaryAreaColor}; color: ${contrastColor}; cursor: pointer; transition: all 0.3s ease;">
                                ${badgeContent}
                            </span>`;
                        }
                    },
                    { 
                        data: 'created_at',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            try {
                                return data ? new Date(data).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'N/A';
                            } catch(e) { return data; }
                        }
                    },
                    { 
                        data: null,
                        orderable: false,
                        className: 'text-center align-middle actions-column',
                        render: function(data, type, row) {
                            let actions = '<div class="btn-group" role="group" aria-label="Acciones de usuario">';
                            if (currentUserForAdminUsers && currentUserForAdminUsers.role === 'owner' && row.id !== currentUserForAdminUsers.id) {
                                actions += `<button class="btn btn-info btn-sm rounded-circle me-1 hvr-grow edit-user-role-btn" data-user-id="${row.id}" data-username="${row.username}" data-role="${row.role}" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar Usuario"><i class="fas fa-edit"></i></button>`;
                                actions += `<button class="btn btn-danger btn-sm rounded-circle hvr-grow delete-user-btn" data-user-id="${row.id}" data-username="${row.username}" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar Usuario"><i class="fas fa-trash-alt"></i></button>`;
                            } else if (currentUserForAdminUsers && currentUserForAdminUsers.role === 'admin') {
                                // Filtrar valores null/undefined de las áreas del administrador
                                const adminAreas = Array.isArray(currentUserForAdminUsers.areas_admin) 
                                    ? currentUserForAdminUsers.areas_admin.filter(id => id !== null && id !== undefined)
                                    : [];
                                    
                                // Solo puede editar/eliminar editores de sus áreas
                                if (row.role === 'editor' && row.areas_editor && row.areas_editor.some(area => 
                                    area !== null && area !== undefined && adminAreas.includes(area))) {
                                    actions += `<button class="btn btn-info btn-sm rounded-circle me-1 hvr-grow edit-user-role-btn" data-user-id="${row.id}" data-username="${row.username}" data-role="${row.role}" data-bs-toggle="tooltip" data-bs-placement="top" title="Editar Usuario"><i class="fas fa-edit"></i></button>`;
                                    actions += `<button class="btn btn-danger btn-sm rounded-circle hvr-grow delete-user-btn" data-user-id="${row.id}" data-username="${row.username}" data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar Usuario"><i class="fas fa-trash-alt"></i></button>`;
                                } else if (row.role === 'owner') {
                                    actions = '<span class="d-inline-block rounded-circle bg-success text-white" style="width:36px;height:36px;line-height:36px;text-align:center;"><i class="fa-solid fa-crown"></i></span>';
                                } else {
                                    actions = '<span class="text-muted fst-italic small">N/A</span>';
                                }
                            } else if (row.role === 'owner') {
                                actions = '<span class="d-inline-block rounded-circle bg-success text-white" style="width:36px;height:36px;line-height:36px;text-align:center;"><i class="fa-solid fa-crown"></i></span>';
                            } else {
                                actions = '<span class="text-muted fst-italic small">N/A</span>';
                            }
                            actions += '</div>';
                            return actions;
                        }
                    }
                ],
                language: { 
                    decimal: "", emptyTable: "No hay usuarios registrados.", 
                    info: "Mostrando _START_ a _END_ de _TOTAL_ usuarios", 
                    infoEmpty: "Mostrando 0 usuarios", 
                    infoFiltered: "(filtrado de _MAX_ usuarios totales)", 
                    lengthMenu: "Mostrar _MENU_ usuarios", 
                    loadingRecords: "Cargando...", 
                    processing: "Procesando...", 
                    search: "Buscar:", 
                    zeroRecords: "No se encontraron usuarios coincidentes", 
                    paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
                },
                drawCallback: function () {
                    // Inicializar tooltips normales
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.area-tooltip)'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        // Asegurarse de que no se creen múltiples tooltips en el mismo elemento
                        let tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (tooltip) {
                            tooltip.dispose();
                        }
                        // Crear un nuevo tooltip con opciones mejoradas
                        tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
                            delay: { show: 100, hide: 300 },
                            html: true,
                            boundary: 'window',
                            container: 'body'
                        });
                    });
                    
                    // Inicializar tooltips de áreas con contenido personalizado
                    const areaTooltipTriggerList = [].slice.call(document.querySelectorAll('.area-tooltip[data-tooltip-content]'));
                    areaTooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        // Asegurarse de que no se creen múltiples tooltips en el mismo elemento
                        let tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (tooltip) {
                            tooltip.dispose();
                        }
                        
                        // Obtener el contenido del tooltip del atributo personalizado
                        const tooltipContent = tooltipTriggerEl.getAttribute('data-tooltip-content') || '';
                        
                        // Crear un nuevo tooltip con contenido personalizado
                        tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
                            delay: { show: 100, hide: 300 },
                            html: true,
                            boundary: 'window',
                            container: 'body',
                            title: tooltipContent,
                            trigger: 'hover focus' // Asegurar que solo se active al pasar el mouse
                        });
                    });
                    
                    hideMainSpinner(); // Ocultar spinner al terminar de dibujar
                },
                // Añadido para asegurar que la tabla use el 100% del ancho y Bootstrap maneje la responsividad
                width: '100%', 
                // Quitar cualquier opción de responsive de DataTables si existiera
                // responsive: false, // O simplemente no definirla
            });
        } catch (e) {
            console.error("Error inicializando DataTables para usuarios:", e);
            showUserManagementToast("Error crítico al inicializar la tabla de usuarios.", true);
            hideMainSpinner();
        }
    } else {
         console.warn("jQuery, DataTables o currentUserForAdminUsers no están disponibles para inicializar usersTable.");
         hideMainSpinner();
    }
  }

  // Iniciar la aplicación con precarga de áreas
  preloadAreas()
    .then(() => {
        // Una vez cargadas las áreas, inicializar la tabla
        initializeUsersTable();
        // También cargar usuarios para el objeto global
        loadAllUsersForTable();
    })
    .catch(error => {
        console.error("Error en la precarga de áreas:", error);
        // Inicializar la tabla de todos modos, aunque las áreas no estén cargadas
        initializeUsersTable();
    });

    function reloadUsersTable() {
        if (usersTable) {
            // Destruir tooltips existentes antes de recargar para evitar duplicados
            const existingTooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            existingTooltips.forEach(el => {
                const tooltip = bootstrap.Tooltip.getInstance(el);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
            
            // Mostrar spinner durante la recarga
            showMainSpinner();
            
            // Recargar tabla y actualizar datos globales
            usersTable.ajax.reload(function() {
                // Callback después de que la tabla se haya recargado
                loadAllUsersForTable(function() {
                    // Una vez que la tabla se ha recargado, reinicializar los tooltips
                    setTimeout(() => {
                        // Inicializar tooltips normales
                        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.area-tooltip)'));
                        tooltipTriggerList.forEach(initializeTooltip);
                        
                        // Inicializar tooltips de áreas con contenido personalizado
                        const areaTooltipTriggerList = [].slice.call(document.querySelectorAll('.area-tooltip[data-tooltip-content]'));
                        areaTooltipTriggerList.forEach(initializeAreaTooltip);
                        
                        // Ocultar spinner cuando todo esté listo
                        hideMainSpinner();
                    }, 100); // Pequeño retraso para asegurar que el DOM está actualizado
                });
            }, false);
        }
    }
    
    // Función auxiliar para inicializar tooltips normales
    function initializeTooltip(tooltipTriggerEl) {
        // Asegurarse de que no se creen múltiples tooltips en el mismo elemento
        let tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (tooltip) {
            tooltip.dispose();
        }
        // Crear un nuevo tooltip con opciones mejoradas
        tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 100, hide: 300 },
            html: true,
            boundary: 'window',
            container: 'body'
        });
    }
    
    // Función auxiliar para inicializar tooltips de áreas
    function initializeAreaTooltip(tooltipTriggerEl) {
        // Asegurarse de que no se creen múltiples tooltips en el mismo elemento
        let tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (tooltip) {
            tooltip.dispose();
        }
        
        // Obtener el contenido del tooltip del atributo personalizado
        const tooltipContent = tooltipTriggerEl.getAttribute('data-tooltip-content') || '';
        
        // Crear un nuevo tooltip con contenido personalizado
        tooltip = new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 100, hide: 300 },
            html: true,
            boundary: 'window',
            container: 'body',
            title: tooltipContent,
            trigger: 'hover focus' // Asegurar que solo se active al pasar el mouse
        });
    }

    // --- Lógica para mostrar y poblar el select de área en edición de usuario ---
    function loadAreasForEditSelect(selectedAreaIds = []) {
        // Filtrar valores null/undefined de selectedAreaIds
        const filteredSelectedAreaIds = Array.isArray(selectedAreaIds) 
            ? selectedAreaIds.filter(id => id !== null && id !== undefined)
            : [];
            
        // Mostrar un indicador de carga en el select
        const select = document.getElementById('editAreaId');
        if (select) {
            select.innerHTML = '<option value="" disabled selected>Cargando áreas...</option>';
            select.setAttribute('disabled', 'disabled');
        }

        fetch('api/areas.php?action=list')
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Error ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                const areas = data.data || [];
                if (!select) return;
                
                // Limpiar el select y habilitar
                select.innerHTML = '';
                select.removeAttribute('disabled');
                
                // Opción por defecto
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.disabled = true;
                defaultOption.textContent = "Selecciona un área";
                
                if (!selectedAreaIds || selectedAreaIds.length === 0) {
                    defaultOption.selected = true;
                }
                
                select.appendChild(defaultOption);
                
                if (areas.length > 0) {
                    console.log('Editando áreas, seleccionadas:', selectedAreaIds);
                    
                    // Si es admin, filtrar solo las áreas que administra
                    if (currentUserForAdminUsers && currentUserForAdminUsers.role === 'admin' && Array.isArray(currentUserForAdminUsers.areas_admin)) {
                        // Filtrar valores null/undefined de las áreas del administrador
                        const adminAreas = currentUserForAdminUsers.areas_admin.filter(id => id !== null && id !== undefined);
                        console.log('Áreas del administrador (edit):', adminAreas);
                        let areasAdded = 0;
                        
                        areas.forEach(area => {
                            if (adminAreas.includes(area.id)) {
                                // Crear opción con mejor estilo visual
                                const opt = createStyledAreaOption(area);
                                
                                // Marcar como seleccionada si está en selectedAreaIds
                                if (selectedAreaIds.includes(area.id)) {
                                    opt.selected = true;
                                }
                                
                                select.appendChild(opt);
                                areasAdded++;
                            }
                        });
                        
                        console.log('Áreas añadidas al select de edición:', areasAdded);
                        
                        // Si no hay áreas disponibles para este administrador
                        if (areasAdded === 0) {
                            defaultOption.textContent = "No tienes áreas disponibles";
                            showUserManagementToast('No tienes áreas asignadas para editar', true);
                        }
                    } else {
                        // Si es owner, mostrar todas las áreas
                        areas.forEach(area => {
                            // Crear opción con mejor estilo visual
                            const opt = createStyledAreaOption(area);
                            
                            // Marcar como seleccionada si está en selectedAreaIds
                            if (selectedAreaIds.includes(area.id)) {
                                opt.selected = true;
                            }
                            
                            select.appendChild(opt);
                        });
                    }
                    
                    // Aplicar select2 o selección mejorada si está disponible
                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        try {
                            $(select).select2({
                                templateResult: formatAreaOption,
                                templateSelection: formatAreaOption
                            });
                        } catch (e) {
                            console.warn('No se pudo inicializar Select2:', e);
                        }
                    }
                } else {
                    defaultOption.textContent = "No hay áreas disponibles";
                    showUserManagementToast('No hay áreas disponibles para asignar', true);
                }
            })
            .catch(error => {
                console.error('Error cargando áreas para edición:', error);
                if (select) {
                    select.innerHTML = '<option value="" disabled selected>Error al cargar áreas</option>';
                    select.removeAttribute('disabled');
                }
                showUserManagementToast(`Error al cargar áreas: ${error.message}`, true);
            });
    }

    // Función para cargar áreas en el select para nuevos usuarios
    function loadAreasForNewSelect() {
        // Mostrar un indicador de carga en el select
        const select = document.getElementById('newAreaId');
        if (select) {
            select.innerHTML = '<option value="" disabled selected>Cargando áreas...</option>';
            select.setAttribute('disabled', 'disabled');
        }

        fetch('api/areas.php?action=list')
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Error ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                const areas = data.data || [];
                if (!select) return;
                
                // Limpiar el select y habilitar
                select.innerHTML = '';
                select.removeAttribute('disabled');
                
                // Mensaje por defecto
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.disabled = true;
                defaultOption.selected = true;
                
                // Si hay áreas disponibles
                if (areas.length > 0) {
                    defaultOption.textContent = "Selecciona un área";
                    select.appendChild(defaultOption);
                    
                    console.log('Rol de usuario actual:', currentUserForAdminUsers?.role);
                    console.log('Áreas disponibles en total:', areas.length);
                    
                    // Si es admin, filtrar solo las áreas que administra
                    if (currentUserForAdminUsers && currentUserForAdminUsers.role === 'admin' && Array.isArray(currentUserForAdminUsers.areas_admin)) {
                        // Filtrar valores null/undefined de las áreas del administrador
                        const adminAreas = currentUserForAdminUsers.areas_admin.filter(id => id !== null && id !== undefined);
                        console.log('Áreas del administrador:', adminAreas);
                        let areasAdded = 0;
                        
                        areas.forEach(area => {
                            if (adminAreas.includes(area.id)) {
                                // Crear opción con mejor estilo visual
                                const opt = createStyledAreaOption(area);
                                select.appendChild(opt);
                                areasAdded++;
                            }
                        });
                        
                        console.log('Áreas añadidas al select:', areasAdded);
                        
                        // Si no hay áreas disponibles para este administrador
                        if (areasAdded === 0) {
                            defaultOption.textContent = "No tienes áreas disponibles";
                            showUserManagementToast('No tienes áreas asignadas para crear editores', true);
                        }
                    } else {
                        // Si es owner, mostrar todas las áreas
                        areas.forEach(area => {
                            // Crear opción con mejor estilo visual
                            const opt = createStyledAreaOption(area);
                            select.appendChild(opt);
                        });
                    }
                    
                    // Aplicar select2 o selección mejorada si está disponible
                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        try {
                            $(select).select2({
                                templateResult: formatAreaOption,
                                templateSelection: formatAreaOption
                            });
                        } catch (e) {
                            console.warn('No se pudo inicializar Select2:', e);
                        }
                    }
                } else {
                    // No hay áreas en el sistema
                    defaultOption.textContent = "No hay áreas disponibles";
                    select.appendChild(defaultOption);
                    showUserManagementToast('No hay áreas disponibles en el sistema', true);
                }
            })
            .catch(error => {
                console.error('Error cargando áreas:', error);
                if (select) {
                    select.innerHTML = '<option value="" disabled selected>Error al cargar áreas</option>';
                    select.removeAttribute('disabled');
                }
                showUserManagementToast(`Error al cargar áreas: ${error.message}`, true);
            });
    }
    
    // Función para crear opciones de área con estilo
    function createStyledAreaOption(area) {
        const opt = document.createElement('option');
        opt.value = area.id;
        
        // Mostrar el nombre del área con su color
        const areaColor = area.color || '#4285F4';
        const contrastColor = getContrastColor(areaColor);
        opt.style.backgroundColor = areaColor;
        opt.style.color = contrastColor;
        opt.textContent = area.name || 'Área sin nombre';
        
        // Añadir una clase CSS para un mejor aspecto visual
        opt.classList.add('area-option');
        opt.style.padding = '8px';
        opt.style.margin = '2px 0';
        opt.style.borderRadius = '4px';
        opt.style.fontWeight = '500';
        
        // Agregar datos adicionales como atributos para acceso fácil
        opt.setAttribute('data-color', areaColor);
        opt.setAttribute('data-description', area.description || '');
        
        return opt;
    }
    
    // Función para formatear opciones en select2 (si está disponible)
    function formatAreaOption(area) {
        if (!area.id) {
            return area.text;
        }
        
        const color = area.element ? $(area.element).attr('data-color') : '#4285F4';
        const contrastColor = getContrastColor(color);
        
        // Crear un elemento con mejor diseño visual
        const $option = $(
            `<span class="d-flex align-items-center p-1">
                <span class="d-inline-block me-2" style="width:12px; height:12px; border-radius:50%; background-color:${color}"></span>
                <span style="color:${contrastColor}; background-color:${color}; padding: 2px 8px; border-radius: 4px;">${area.text}</span>
            </span>`
        );
        
        return $option;
    }

    // Crear Nuevo Usuario
    const createUserForm = document.getElementById('createUserForm');
    if (createUserForm) {
        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(createUserForm); 

            fetch('api/users.php?action=create', {
                method: 'POST',
                body: formData 
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if(createUserModal) createUserModal.hide();
                    reloadUsersTable();
                    showUserManagementToast(result.message || 'Usuario creado exitosamente.');
                    createUserForm.reset();
                } else {
                    showUserManagementToast('Error: ' + (result.message || 'No se pudo crear el usuario.'), true);
                }
            })
            .catch(error => {
                console.error('Error creando usuario:', error);
                showUserManagementToast('Error de conexión al crear usuario.', true);
            });
        });
    }

    // Editar Rol de Usuario - Poblar Modal
    document.body.addEventListener('click', function(event) { 
        const editBtn = event.target.closest('.edit-user-role-btn');
        if (editBtn) {
            const userId = editBtn.dataset.userId;
            const username = editBtn.dataset.username;
            const currentRole = editBtn.dataset.role;

            const userRow = editBtn.closest('tr');
            const profileImageElement = userRow.querySelector('.profile-image-table');
            // Obtener la URL original de la imagen, si existe, sin el timestamp
            const currentImageUrl = profileImageElement ? profileImageElement.src.split('?t=')[0] : null;


            document.getElementById('editUserId').value = userId;
            document.getElementById('editUsernameDisplay').textContent = username;
            document.getElementById('editRole').value = currentRole;

            const imagePreview = document.getElementById('editProfileImagePreview');
            const imagePlaceholder = document.getElementById('editProfileImagePlaceholder');
            const imageInput = document.getElementById('editUserProfileImage');
            imageInput.value = ''; 

            if (currentImageUrl && currentImageUrl !== '/form/profile_images/default.png' && !currentImageUrl.includes('placehold.co')) { 
                imagePreview.src = currentImageUrl; 
                imagePreview.classList.remove('d-none');
                imagePlaceholder.classList.add('d-none');
            } else {
                imagePreview.classList.add('d-none');
                imagePreview.src = '';
                imagePlaceholder.classList.remove('d-none');
            }
            
            if(editUserRoleModal) editUserRoleModal.show();
        }

        const deleteBtn = event.target.closest('.delete-user-btn');
        if (deleteBtn) {
            const userId = deleteBtn.dataset.userId;
            const username = deleteBtn.dataset.username;
            document.getElementById('userNameToDeleteDisplay').textContent = username;
            const confirmDeleteUserBtn = document.getElementById('confirmDeleteUserBtn');
            confirmDeleteUserBtn.dataset.userIdToDelete = userId;
            if(confirmDeleteUserModal) confirmDeleteUserModal.show();
        }
    });
    
    // Vista previa para la imagen en el modal de edición
    const editUserProfileImageInput = document.getElementById('editUserProfileImage');
    const editProfileImagePreview = document.getElementById('editProfileImagePreview');
    const editProfileImagePlaceholder = document.getElementById('editProfileImagePlaceholder');

    if (editUserProfileImageInput && editProfileImagePreview && editProfileImagePlaceholder) {
        editUserProfileImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    editProfileImagePreview.src = e.target.result;
                    editProfileImagePreview.classList.remove('d-none');
                    editProfileImagePlaceholder.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Mostrar/ocultar el select de area_id según el rol en la edición
    const editRoleSelect = document.getElementById('editRole');
    const editAreaIdGroup = document.getElementById('editAreaIdGroup');
    const editAreaIdSelect = document.getElementById('editAreaId');
    if (editRoleSelect && editAreaIdGroup) {
        editRoleSelect.addEventListener('change', function() {
            if (this.value === 'admin' || this.value === 'editor') {
                editAreaIdGroup.classList.remove('d-none');
                loadAreasForEditSelect();
                editAreaIdSelect.required = true;
            } else {
                editAreaIdGroup.classList.add('d-none');
                editAreaIdSelect.required = false;
            }
        });
    }
    
    // Al abrir el modal de edición, setear el select de área si corresponde
    document.body.addEventListener('click', function(event) {
        const editBtn = event.target.closest('.edit-user-role-btn');
        if (editBtn) {
            const userId = editBtn.dataset.userId;
            const username = editBtn.dataset.username;
            const currentRole = editBtn.dataset.role;
            // Buscar las áreas actuales del usuario (si es editor o admin)
            let currentAreaIds = [];
            if (window.usersById && window.usersById[userId]) {
                currentAreaIds = window.usersById[userId].areas_editor || [];
            }
            document.getElementById('editRole').value = currentRole;
            if (currentRole === 'admin' || currentRole === 'editor') {
                editAreaIdGroup.classList.remove('d-none');
                loadAreasForEditSelect(currentAreaIds);
                editAreaIdSelect.required = true;
            } else {
                editAreaIdGroup.classList.add('d-none');
                editAreaIdSelect.required = false;
            }
        }
    });
    
    // Gestionar el select de role al crear un nuevo usuario
    const newRoleSelect = document.getElementById('newRole');
    const newAreaIdGroup = document.getElementById('newAreaIdGroup');
    const newAreaIdSelect = document.getElementById('newAreaId');
    
    if (newRoleSelect && newAreaIdGroup && newAreaIdSelect) {
        // Al cambiar el rol en la creación de usuario
        newRoleSelect.addEventListener('change', function() {
            if (this.value === 'editor' || this.value === 'admin') {
                newAreaIdGroup.classList.remove('d-none');
                loadAreasForNewSelect();
                newAreaIdSelect.required = true;
            } else {
                newAreaIdGroup.classList.add('d-none');
                newAreaIdSelect.required = false;
            }
        });
        
        // Al abrir el modal para crear usuario
        document.getElementById('createUserModal').addEventListener('show.bs.modal', function() {
            // Reset del formulario
            createUserForm.reset();
            // Ocultar el select de área inicialmente
            newAreaIdGroup.classList.add('d-none');
            newAreaIdSelect.required = false;
        });
    }

    // Editar Rol de Usuario - Enviar Formulario
    const editUserRoleForm = document.getElementById('editUserRoleForm');
    if (editUserRoleForm) {
        editUserRoleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(editUserRoleForm);
            if (editRoleSelect.value !== 'admin' && editRoleSelect.value !== 'editor') {
                formData.delete('area_id');
            }
            fetch('api/users.php?action=update_user', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if(editUserRoleModal) editUserRoleModal.hide();
                    reloadUsersTable();
                    showUserManagementToast(result.message || 'Usuario actualizado exitosamente.');
                } else {
                    showUserManagementToast('Error: ' + (result.message || 'No se pudo actualizar el usuario.'), true);
                    // Scroll al toast para máxima visibilidad
                    if (notificationToastEl) notificationToastEl.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            })
            .catch(error => {
                console.error('Error actualizando usuario:', error);
                showUserManagementToast('Error de conexión al actualizar usuario.', true);
                if (notificationToastEl) notificationToastEl.scrollIntoView({behavior: 'smooth', block: 'center'});
            });
        });
    }

    // Eliminar Usuario
    const confirmDeleteUserBtn = document.getElementById('confirmDeleteUserBtn');
    if (confirmDeleteUserBtn) {
        confirmDeleteUserBtn.addEventListener('click', function() {
            const userId = this.dataset.userIdToDelete;
            fetch('api/users.php?action=delete_user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if(confirmDeleteUserModal) confirmDeleteUserModal.hide();
                    reloadUsersTable();
                    showUserManagementToast(result.message || 'Usuario eliminado exitosamente.');
                } else {
                    showUserManagementToast('Error: ' + (result.message || 'No se pudo eliminar el usuario.'), true);
                    if (notificationToastEl) notificationToastEl.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            })
            .catch(error => {
                console.error('Error eliminando usuario:', error);
                showUserManagementToast('Error de conexión al eliminar usuario.', true);
                if (notificationToastEl) notificationToastEl.scrollIntoView({behavior: 'smooth', block: 'center'});
            });
        });
    }

    function loadAllUsersForTable(callback) {
        fetch('api/users.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    window.usersById = {};
                    data.data.forEach(u => {
                        window.usersById[u.id] = {
                            username: u.username,
                            role: u.role,
                            areas_admin: u.areas_admin || [],
                            areas_editor: u.areas_editor || [],
                            profile_image: u.profile_image_url || u.profile_image || '/form/profile_images/default.png'
                        };
                    });
                } else {
                    window.usersById = {};
                }
                if (typeof callback === 'function') callback();
            })
            .catch(() => {
                window.usersById = {};
                if (typeof callback === 'function') callback();
            });
    }
}); // <--- Cierre de jQuery document ready
