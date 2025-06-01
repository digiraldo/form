// admin_areas.js
// Lógica JS para gestión de áreas (listado, creación, edición, eliminación)

document.addEventListener('DOMContentLoaded', function() {
    // Verificar si la variable de usuario actual existe
    let currentUser = window.currentUserForAreas || { role: 'unknown', isOwner: false };

    // Función para inicializar variables globales y el usuario actual
    function inicializarGlobales() {
        // Inicializar currentUser
        if (typeof window.currentUserForAreas !== 'undefined' && window.currentUserForAreas) {
            currentUser = window.currentUserForAreas;
            console.log('[DEBUG] currentUser inicializado desde window.currentUserForAreas:', currentUser);
        } else {
            console.error('[ERROR] window.currentUserForAreas no está definido. Usando valores por defecto.');
            currentUser = { role: 'unknown', isOwner: false };
        }
        
        // Hacer disponible currentUser globalmente para todas las funciones
        window.currentUser = currentUser;
    }

    // Ajustar la interfaz según el rol del usuario
    function ajustarInterfazSegunRol() {
        if (!currentUser.isOwner && currentUser.role === 'admin') {
            // Es un administrador (no propietario)
            // Ocultar botón de crear área ya que solo los propietarios pueden crear áreas
            const btnCreateArea = document.querySelector('.create-area-btn');
            if (btnCreateArea) {
                btnCreateArea.style.display = 'none';
            }
        }
    }

    // Inicializar configuración global
    inicializarGlobales();
    
    const createAreaModalEl = document.getElementById('createAreaModal');
    const form = document.getElementById('createAreaForm');
    const modalTitleEl = document.getElementById('modalAreaTitle');
    const submitButton = createAreaModalEl.querySelector('form button[type="submit"]');
    
    // Elementos para la previsualización de color
    const areaColorInput = document.getElementById('areaColor');
    const colorPreview = document.getElementById('colorPreview');
    
    // Función para actualizar la previsualización del color
    function updateColorPreview() {
        const selectedColor = areaColorInput.value;
        const contrastColor = getContrastColor(selectedColor);
        
        if (colorPreview) {
            colorPreview.style.backgroundColor = selectedColor;
            colorPreview.style.color = contrastColor;
        }
    }
      // Actualizar previsualización cuando cambie el color
    if (areaColorInput) {
        areaColorInput.addEventListener('input', updateColorPreview);
        // Inicializar la previsualización
        updateColorPreview();    }

    // Ejecutar ajustes de interfaz al cargar
    ajustarInterfazSegunRol();

    // Mostrar spinner al cargar usuarios y áreas
    showMainSpinner();
    let usuariosCargados = false;
    let areasCargadas = false;
    function checkAndHideSpinner() {
        if (usuariosCargados && areasCargadas) hideMainSpinner();
    }
    cargarUsuariosParaSelects(function() {
        usuariosCargados = true;
        checkAndHideSpinner();
    });
    cargarAreas(function() {
        areasCargadas = true;
        checkAndHideSpinner();
    });

    createAreaModalEl.addEventListener('show.bs.modal', function (event) {
        const editId = form.getAttribute('data-edit-id');
        console.log('[DEBUG show.bs.modal] Evento disparado. editId:', editId);

        if (!editId) { 
            console.log('[DEBUG show.bs.modal] Modo Creación.');
            modalTitleEl.textContent = 'Crear Nueva Área';
            submitButton.textContent = 'Crear Área';
            form.reset(); 
            
            // Limpiar checkboxes en modo creación
            document.querySelectorAll('#areaAdminsList input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('#areaEditorsList input[type="checkbox"]').forEach(cb => cb.checked = false);
            console.log('[DEBUG show.bs.modal] Checkboxes limpiados para modo creación.');
        } else {
            console.log('[DEBUG show.bs.modal] Modo Edición. La carga y selección de checkboxes se maneja en abrirModalEditarArea.');
        }
    });

    createAreaModalEl.addEventListener('hidden.bs.modal', function () {
        form.removeAttribute('data-edit-id'); // Siempre limpiar el ID de edición al cerrar
        // Opcional: form.reset() si se quiere limpiar siempre el formulario al cerrar.
        // Pero el modo creación en show.bs.modal ya lo hace.
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const editId = this.getAttribute('data-edit-id');
        
        // Asegurar que currentUser esté disponible
        const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
        
        // Recolectar IDs de checkboxes seleccionados
        const adminIds = Array.from(document.querySelectorAll('#areaAdminsList input[type="checkbox"]:checked')).map(cb => cb.value);
        const editorIds = Array.from(document.querySelectorAll('#areaEditorsList input[type="checkbox"]:checked')).map(cb => cb.value);

        // Verificar permisos según el rol del usuario actual
        if (!userActual.isOwner && userActual.role === 'admin') {
            // Administradores solo pueden editar, no crear
            if (!editId) {
                mostrarToast('No tienes permisos para crear áreas nuevas', 'error');
                return;
            }
            
            // Verificar que el administrador actual está en la lista de administradores del área
            fetch(`api/areas.php?action=list`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !Array.isArray(data.data)) {
                        throw new Error('Error al verificar permisos del área');
                    }
                      // Buscar el área actual
                    const areaActual = data.data.find(area => area.id === editId);
                    if (!areaActual) {
                        throw new Error('El área que intentas editar no existe');
                    }
                    
                    // Verificar si el usuario actual es administrador de esta área
                    const isAreaAdmin = Array.isArray(areaActual.admins) && areaActual.admins.includes(userActual.id);
                    if (!isAreaAdmin) {
                        throw new Error('No tienes permisos para editar esta área');
                    }
                    
                    // Para administradores, conservar la lista original de administradores
                    // No permitir modificaciones a los administradores
                    const adminIdsOriginal = areaActual.admins || [];
                    
                    // Proceder con el envío del formulario con administradores originales y editores seleccionados
                    const formDataFinal = new FormData(form);
                    
                    // Limpiar arrays previos
                    for (const key of Array.from(formDataFinal.keys())) {
                        if (key === 'admins[]' || key === 'editors[]') {
                            formDataFinal.delete(key);
                        }
                    }
                    
                    // Añadir administradores originales (no modificables por admins)
                    adminIdsOriginal.forEach(id => formDataFinal.append('admins[]', id));
                    
                    // Añadir editores seleccionados
                    editorIds.forEach(id => formDataFinal.append('editors[]', id));
                    
                    // Si no hay editores seleccionados, enviar array vacío
                    if (editorIds.length === 0) {
                        formDataFinal.append('editors[]', '');
                    }
                    
                    // Enviar formulario
                    let actionUrl = 'api/areas.php?action=';
                    if (editId) {
                        formDataFinal.append('id', editId); 
                        actionUrl += 'update';
                    } else {
                        actionUrl += 'create';
                    }

                    fetch(actionUrl, {
                        method: 'POST',
                        body: formDataFinal
                    })
                    .then(response => {
                        if (!response.ok) {
                            // Intenta parsear el JSON de error si es posible
                            return response.json().then(errData => {
                                throw new Error(errData.error || `Error HTTP ${response.status}`);
                            }).catch(() => {
                                // Si el cuerpo del error no es JSON o está vacío
                                throw new Error(`Error HTTP ${response.status} - ${response.statusText}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            mostrarToast(editId ? 'Área actualizada correctamente' : 'Área creada correctamente', 'success');
                            cargarAreas();
                            const modal = bootstrap.Modal.getInstance(document.getElementById('createAreaModal'));
                            modal.hide();
                        } else {
                            mostrarToast(data.error || 'Error al procesar la solicitud', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error en submit:', error);
                        mostrarToast(error.message || 'Error de conexión o respuesta inesperada.', 'error');
                    });
                })
                .catch(error => {
                    console.error('Error en verificación de permisos:', error);
                    mostrarToast(error.message || 'Error al verificar permisos', 'error');
                });
        } else {
            // Propietarios pueden hacer todo, continuar normalmente
            // Añadir a formData manualmente porque los checkboxes no se envían si no están en un form tradicional con name
            adminIds.forEach(id => formData.append('admins[]', id));
            editorIds.forEach(id => formData.append('editors[]', id));

            // Si no hay ninguno seleccionado, enviar un array vacío para que el backend pueda desasignar
            if (adminIds.length === 0) {
                formData.append('admins[]', '');
            }
            if (editorIds.length === 0) {
                formData.append('editors[]', '');
            }
            
            // Enviar formulario
            let actionUrl = 'api/areas.php?action=';
            if (editId) {
                formData.append('id', editId); 
                actionUrl += 'update';
            } else {
                actionUrl += 'create';
            }

            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Intenta parsear el JSON de error si es posible
                    return response.json().then(errData => {
                        throw new Error(errData.error || `Error HTTP ${response.status}`);
                    }).catch(() => {
                        // Si el cuerpo del error no es JSON o está vacío
                        throw new Error(`Error HTTP ${response.status} - ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    mostrarToast(editId ? 'Área actualizada correctamente' : 'Área creada correctamente', 'success');
                    cargarAreas();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createAreaModal'));
                    modal.hide();
                } else {
                    mostrarToast(data.error || 'Error al procesar la solicitud', 'error');
                }
            })
            .catch(error => {
                console.error('Error en submit:', error);
                mostrarToast(error.message || 'Error de conexión o respuesta inesperada.', 'error');
            });
        }
    });
});

function cargarUsuariosParaSelects(callback) { // Renombrar a cargarUsuariosParaCheckboxes o similar sería más preciso
    console.log('[DEBUG cargarUsuariosParaCheckboxes] Iniciando carga. Callback presente:', !!callback);
    
    // Asegurar que currentUser esté disponible
    const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
    
    fetch('api/users.php?action=list')
        .then(r => {
            if (!r.ok) throw new Error(`Error al cargar usuarios: ${r.status} ${r.statusText}`);
            return r.json();
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) { 
                console.error("Error en la respuesta de la API de usuarios:", data.error || "Formato de datos incorrecto");
                throw new Error(data.error || "No se pudieron cargar los usuarios correctamente.");
            }
            const users = data.data;
              // Filtrar según el rol del usuario actual
            let admins = [];
            let editors = [];
            
            if (userActual.isOwner) {
                // Propietarios pueden ver y asignar todos los usuarios
                admins = users.filter(u => u.role === 'admin');
                editors = users.filter(u => u.role === 'editor');
            } else if (userActual.role === 'admin') {
                // Administradores solo pueden asignar editores a sus áreas, no pueden cambiar admins
                editors = users.filter(u => u.role === 'editor');
                // Los admins solo verán a ellos mismos en la lista de admins (no editable)
                admins = users.filter(u => u.id === userActual.id);
            } else {
                // Otros roles (por seguridad) no ven usuarios
                admins = [];
                editors = [];
            }
            
            const adminsList = document.getElementById('areaAdminsList');
            const editorsList = document.getElementById('areaEditorsList');
            
            adminsList.innerHTML = ''; 
            editorsList.innerHTML = '';

            const createCheckboxItem = (user, type) => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item';
                
                const input = document.createElement('input');
                input.className = 'form-check-input me-1';
                input.type = 'checkbox';
                input.value = user.id;
                input.id = `${type}Checkbox_${user.id}`;
                // El atributo name no es estrictamente necesario aquí si recolectamos los valores manualmente

                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = input.id;
                label.textContent = user.username;

                listItem.appendChild(input);
                listItem.appendChild(label);
                return listItem;
            };

            admins.forEach(u => {
                adminsList.appendChild(createCheckboxItem(u, 'admin'));
            });
            editors.forEach(u => {
                editorsList.appendChild(createCheckboxItem(u, 'editor'));
            });

            if (typeof callback === 'function') callback();
        })
        .catch(error => {
            console.error("Error cargando usuarios para checkboxes:", error);
            mostrarToast(error.message || "Error al cargar lista de usuarios.", "error");
            if (typeof callback === 'function') callback(error);
        });
}

function cargarAreas(callback) {
    fetch('api/areas.php?action=list')
        .then(r => {
            if (!r.ok) {
                return r.json().then(errData => {
                    throw new Error(errData.error || `Error HTTP ${r.status} al cargar áreas`);
                }).catch(() => {
                    throw new Error(`Error HTTP ${r.status} - ${r.statusText} al cargar áreas`);
                });
            }
            return r.json();
        })        .then(data => {
            const tbody = document.querySelector('#areasTable tbody');
            tbody.innerHTML = ''; 
            if (!data.success || !Array.isArray(data.data)) {
                console.warn("No se pudieron cargar las áreas:", data.error || 'Respuesta no válida de la API.');
                tbody.innerHTML = `<tr><td colspan="5" class="text-center">No hay áreas para mostrar o error al cargar. (${data.error || 'Respuesta no válida'})</td></tr>`;
                if (typeof callback === 'function') callback(new Error(data.error || 'Respuesta no válida de la API para áreas'));
                return;
            }
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay áreas definidas.</td></tr>';
                if (typeof callback === 'function') callback();
                return;
            }            data.data.forEach(area => {                // Componemos las celdas para los administradores
                let adminContent = '';
                if (Array.isArray(area.admins_data) && area.admins_data.length > 0) {
                    adminContent = area.admins_data.map(admin => {
                        let avatarHtml = '';
                        // Verificar si tiene imagen válida (no default.png)
                        if (admin.profile_image_url && 
                            admin.profile_image_url.trim() !== '' && 
                            !admin.profile_image_url.includes('default.png') &&
                            !admin.profile_image_url.includes('placehold.co')) {
                            // Usar imagen real con timestamp para evitar cache
                            let imgSrc = admin.profile_image_url;
                            if (!imgSrc.includes('?t=')) {
                                imgSrc += (imgSrc.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
                            }
                            avatarHtml = `<img src="${imgSrc}" alt="${admin.username}" 
                                class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">`;
                        } else {
                            // Generar círculo con inicial
                            const inicial = admin.username ? admin.username.charAt(0).toUpperCase() : '?';
                            const colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                            const colorIndex = admin.username ? admin.username.length % colors.length : 0;
                            const bgColor = colors[colorIndex];
                            
                            avatarHtml = `<span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center" 
                                style="width:32px;height:32px;background:${bgColor};color:#ffffff;font-weight:bold;font-size:1rem;">${inicial}</span>`;
                        }
                        
                        return `
                            <div class="d-flex align-items-center mb-2">
                                ${avatarHtml}
                                <span>${admin.username}</span>
                            </div>
                        `;
                    }).join('');
                } else {
                    adminContent = '<span class="text-muted">N/A</span>';
                }
                
                // Componemos las celdas para los editores
                let editorContent = '';
                if (Array.isArray(area.editors_data) && area.editors_data.length > 0) {
                    editorContent = area.editors_data.map(editor => {
                        let avatarHtml = '';
                        // Verificar si tiene imagen válida (no default.png)
                        if (editor.profile_image_url && 
                            editor.profile_image_url.trim() !== '' && 
                            !editor.profile_image_url.includes('default.png') &&
                            !editor.profile_image_url.includes('placehold.co')) {
                            // Usar imagen real con timestamp para evitar cache
                            let imgSrc = editor.profile_image_url;
                            if (!imgSrc.includes('?t=')) {
                                imgSrc += (imgSrc.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
                            }
                            avatarHtml = `<img src="${imgSrc}" alt="${editor.username}" 
                                class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">`;
                        } else {
                            // Generar círculo con inicial
                            const inicial = editor.username ? editor.username.charAt(0).toUpperCase() : '?';
                            const colors = ['#4F46E5', '#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#06B6D4', '#3B82F6'];
                            const colorIndex = editor.username ? editor.username.length % colors.length : 0;
                            const bgColor = colors[colorIndex];
                            
                            avatarHtml = `<span class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center" 
                                style="width:32px;height:32px;background:${bgColor};color:#ffffff;font-weight:bold;font-size:1rem;">${inicial}</span>`;
                        }
                        
                        return `
                            <div class="d-flex align-items-center mb-2">
                                ${avatarHtml}
                                <span>${editor.username}</span>
                            </div>
                        `;
                    }).join('');
                } else {
                    editorContent = '<span class="text-muted">N/A</span>';
                }
                
                // Obtener el color del área con valor por defecto
                const areaColor = area.color || '#4285F4';
                const contrastColor = getContrastColor(areaColor);

                const row = tbody.insertRow();
                row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="area-badge me-2" style="background-color: ${areaColor}; color: ${contrastColor};">
                                <i class="fas fa-layer-group me-1"></i>
                            </span>
                            ${area.name || 'Sin nombre'}
                        </div>                    </td>
                    <td>${area.description || 'Sin descripción'}</td>                    <td>${adminContent}</td>
                    <td>${editorContent}</td>
                    <td>
                        ${renderAccionesArea(area)}
                    </td>
                `;
            });
            if (typeof callback === 'function') callback();
        })
        .catch(error => {
            console.error("Error crítico cargando áreas:", error);
            const tbody = document.querySelector('#areasTable tbody');
            if (tbody) {
                 tbody.innerHTML = '<tr><td colspan="5" class="text-center">Error crítico al cargar las áreas. Revise la consola.</td></tr>';
            }
            mostrarToast(error.message || "Error crítico al cargar áreas.", "error");
            if (typeof callback === 'function') callback(error);
        });
}

function abrirModalEditarArea(area) {
    // Asegurar que currentUser esté disponible
    const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
    
    // Verificar permisos para editar el área
    if (!userActual.isOwner) {
        // Para administradores, verificar si son admin de esta área
        if (userActual.role === 'admin') {
            const isAreaAdmin = Array.isArray(area.admins) && area.admins.includes(userActual.id);
            if (!isAreaAdmin) {
                mostrarToast("No tienes permisos para editar esta área", "error");
                return;
            }
        } else {
            // Otros roles no pueden editar
            mostrarToast("No tienes permisos para editar áreas", "error");
            return;
        }
    }

    const form = document.getElementById('createAreaForm');
    const modalTitleEl = document.getElementById('modalAreaTitle');
    const submitButton = form.querySelector('button[type="submit"]');
    const modalEl = document.getElementById('createAreaModal');

    form.setAttribute('data-edit-id', area.id);
    modalTitleEl.innerHTML = '<i class="fas fa-edit me-2"></i>Editar Área';
    submitButton.textContent = 'Guardar Cambios';        console.log("[DEBUG] Área recibida en abrirModalEditarArea:", JSON.parse(JSON.stringify(area)));

        // Cargar usuarios y luego establecer selecciones
        cargarUsuariosParaSelects(function(err) { // Debería ser cargarUsuariosParaCheckboxes
            if (err) {
                mostrarToast("Error al preparar modal: no se pudieron cargar usuarios.", "error");
                return;
            }

            document.getElementById('areaName').value = area.name || '';
            document.getElementById('areaDescription').value = area.description || '';
            
            // Establecer el color si existe, o usar un valor por defecto
            if (area.color) {
                document.getElementById('areaColor').value = area.color;
            } else {
                document.getElementById('areaColor').value = '#4285F4'; // Color predeterminado
            }
            
            // Limpiar selecciones previas (checkboxes)
        document.querySelectorAll('#areaAdminsList input[type="checkbox"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('#areaEditorsList input[type="checkbox"]').forEach(cb => cb.checked = false);

        let adminIds = [];
        if (Array.isArray(area.admins)) {
            adminIds = area.admins.map(admin => (typeof admin === 'object' && admin.id) ? admin.id : admin).filter(id => id);
        }
        
        let editorIds = [];
        if (Array.isArray(area.editors)) {
            editorIds = area.editors.map(editor => (typeof editor === 'object' && editor.id) ? editor.id : editor).filter(id => id);
        }

        console.log("[DEBUG] IDs de Admin a seleccionar (checkboxes):", adminIds);
        console.log("[DEBUG] IDs de Editor a seleccionar (checkboxes):", editorIds);

        adminIds.forEach(id => {
            const checkbox = document.getElementById(`adminCheckbox_${id}`);
            if (checkbox) checkbox.checked = true;
        });

        editorIds.forEach(id => {
            const checkbox = document.getElementById(`editorCheckbox_${id}`);
            if (checkbox) checkbox.checked = true;
        });
        
        console.log("[DEBUG] Checkboxes seleccionados.");

        // No se necesita el listener de shown.bs.modal para checkboxes de esta manera
        // La selección se hace directamente después de cargar los usuarios.

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });
}

function eliminarArea(id, nombre) {
    // Asegurar que currentUser esté disponible
    const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
    
    // Verificar si es propietario - solo propietarios pueden eliminar áreas
    if (!userActual.isOwner) {
        mostrarToast("Solo los propietarios pueden eliminar áreas", "error");
        return;
    }
    
    const nombreEscapado = String(nombre || 'esta área').replace(/"/g, '\\"');
    if (!confirm(`¿Seguro que deseas eliminar el área "${nombreEscapado}"? Esta acción no se puede deshacer.`)) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('api/areas.php?action=delete', {
        method: 'POST',
        body: formData
    })
    .then(r => {
        if (!r.ok) {
             return r.json().then(errData => { throw new Error(errData.error || `Error HTTP ${r.status}`); });
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            mostrarToast('Área eliminada correctamente', 'success');
            cargarAreas();
        } else {
            mostrarToast(data.error || 'Error al eliminar área', 'error');
        }
    })
    .catch(error => {
        console.error("Error al eliminar área:", error);
        mostrarToast(error.message || "Error de conexión al eliminar área.", "error");
    });
}

function mostrarToast(msg, type = 'info') {
    const toastEl = document.getElementById('notificationToast');
    if (!toastEl) {
        console.warn("Elemento Toast no encontrado. Mensaje:", msg);
        alert(msg); 
        return;
    }
    const toastBody = toastEl.querySelector('.toast-body');
    const toastHeaderIconEl = toastEl.querySelector('.toast-header i.fas');

    if (toastBody) toastBody.textContent = msg;
    
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-info', 'bg-warning', 'text-white', 'text-dark');
    let iconClass = 'fa-info-circle';

    switch (type) {
        case 'success':
            toastEl.classList.add('bg-success', 'text-white');
            iconClass = 'fa-check-circle';
            break;
        case 'error':
            toastEl.classList.add('bg-danger', 'text-white');
            iconClass = 'fa-exclamation-circle';
            break;
        case 'warning':
            toastEl.classList.add('bg-warning', 'text-dark');
            iconClass = 'fa-exclamation-triangle';
            break;
        default: // info y otros
            // Usar clases por defecto del toast o especificar para 'info'
            // toastEl.classList.add('bg-light', 'text-dark'); // Ejemplo para info
            break; 
    }
    
    if (toastHeaderIconEl) {
        toastHeaderIconEl.className = `fas ${iconClass} me-2`;
    }

    const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
    toast.show();
}

// --- Spinner helpers globales para overlay principal ---
function showMainSpinner() {
  const spinner = document.getElementById('mainSpinner');
  if (spinner) spinner.style.display = 'flex';
}
function hideMainSpinner() {
  const spinner = document.getElementById('mainSpinner');
  if (spinner) spinner.style.display = 'none';
}

// Función para renderizar los botones de acción según el rol del usuario
function renderAccionesArea(area) {
    // Asegurar que currentUser esté disponible
    const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
    
    // Verificar si el usuario es propietario
    if (userActual.isOwner) {
        // Los propietarios pueden hacer todo
        return `
            <button class="btn btn-sm btn-outline-primary me-1" onclick='abrirModalEditarArea(${JSON.stringify(area)})' title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick='eliminarArea("${area.id}", "${area.name.replace(/"/g, '\\\\\\"').replace(/'/g, "\\\\'")}")' title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        `;
    } else if (userActual.role === 'admin') {
        // Verificar si el admin actual está en la lista de admins del área
        const isAreaAdmin = Array.isArray(area.admins) && area.admins.includes(userActual.id);
        
        if (isAreaAdmin) {
            // Administradores pueden editar sus propias áreas pero no eliminarlas
            return `
                <button class="btn btn-sm btn-outline-primary me-1" onclick='abrirModalEditarArea(${JSON.stringify(area)})' title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" disabled title="Solo propietarios pueden eliminar áreas">
                    <i class="fas fa-lock"></i>
                </button>
            `;
        } else {
            // Para áreas donde no son admin, no pueden hacer nada
            return `
                <span class="text-muted">Sin permisos</span>
            `;
        }
    } else {
        // Para otros roles, no se muestran acciones
        return `<span class="text-muted">Sin permisos</span>`;
    }
}

// Hacer funciones globales si se llaman desde HTML onclick
window.abrirModalEditarArea = abrirModalEditarArea;
window.eliminarArea = eliminarArea;

// Aplicar restricciones adicionales al modal según el rol
document.addEventListener('DOMContentLoaded', function() {
    // Asegurar que currentUser esté disponible
    const userActual = window.currentUser || window.currentUserForAreas || { role: 'unknown', isOwner: false };
    
    const adminsSection = document.querySelector('.form-group-admins');
    const createAreaBtn = document.querySelector('#btnCreateArea');
    
    // Verificar si el usuario es administrador y no propietario
    if (!userActual.isOwner && userActual.role === 'admin') {
        // Deshabilitar la sección de administradores para los administradores
        // Los administradores solo pueden gestionar editores, no otros administradores
        if (adminsSection) {
            const adminsLabel = adminsSection.querySelector('label');
            if (adminsLabel) {
                adminsLabel.innerHTML += ' <small class="text-danger">(Solo lectura)</small>';
            }
            
            // Hacer que los checkboxes de admins estén deshabilitados
            document.addEventListener('click', function(e) {
                if (e.target && e.target.matches('#areaAdminsList input[type="checkbox"]')) {
                    // Prevenir cambios en administradores para usuarios admin
                    if (!userActual.isOwner) {
                        e.preventDefault();
                        e.stopPropagation();
                        mostrarToast("Solo los propietarios pueden modificar administradores de áreas", "warning");
                        return false;
                    }
                }
            }, true);        }
        
        // No mostrar el botón de crear área para administradores
        if (createAreaBtn) {
            createAreaBtn.style.display = 'none';
        }
    }
    
    // Asegurar que las funciones estén disponibles globalmente
    window.abrirModalEditarArea = abrirModalEditarArea;
    window.eliminarArea = eliminarArea;
});
