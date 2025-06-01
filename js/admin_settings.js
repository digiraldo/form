// js/admin_settings.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('admin_settings.js cargado.');

    const profileImagePreview = document.getElementById('profileImagePreview');
    const profileImagePlaceholder = document.getElementById('profileImagePlaceholder');
    const profileImageInput = document.getElementById('profileImageInput');
    const updateProfileForm = document.getElementById('updateProfileForm');
    const usernameInput = document.getElementById('username');
    
    // Toast (asumiendo que el HTML del toast está en admin_settings.php)
    const notificationToastEl = document.getElementById('notificationToast');
    const notificationToast = notificationToastEl ? new bootstrap.Toast(notificationToastEl, { delay: 3500 }) : null;

    function showSettingsToast(message, isError = false) {
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

    // Cargar datos del usuario actual (incluyendo imagen de perfil)
    function loadUserProfile() {
        // Asumimos que 'currentUserForAdminUsers' (o un objeto similar con el ID) está disponible globalmente
        // o que el user_id se pasa de otra manera a esta página.
        // Para este ejemplo, usaremos el ID del formulario si está presente.
        const userIdField = document.querySelector('input[name="user_id"]');
        if (!userIdField || !userIdField.value) {
            console.error("No se pudo obtener el ID del usuario para cargar el perfil.");
            // Podríamos intentar obtenerlo de una variable global si se define en admin_settings.php
            // if (typeof currentLoggedInUser !== 'undefined' && currentLoggedInUser.id) {
            //     // ...
            // }
            return;
        }
        const currentUserId = userIdField.value;

        fetch(`api/users.php?action=get_profile&user_id=${currentUserId}`) // Necesitaremos una nueva acción en api/users.php
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    if(usernameInput) usernameInput.value = result.data.username;
                    if (result.data.profile_image_url) {
                        if(profileImagePreview) profileImagePreview.src = result.data.profile_image_url + '?t=' + new Date().getTime();
                        if(profileImagePreview) profileImagePreview.style.display = 'block';
                        if(profileImagePlaceholder) profileImagePlaceholder.style.display = 'none';
                        // Actualizar también la imagen del navbar
                        const navbarImg = document.getElementById('navbarProfileImage');
                        const navbarIcon = document.getElementById('navbarProfileIcon');
                        if (navbarImg) {
                            navbarImg.src = result.data.profile_image_url + '?t=' + new Date().getTime();
                            navbarImg.style.display = 'inline-block';
                        }
                        if (navbarIcon) navbarIcon.style.display = 'none';

                    } else {
                        if(profileImagePreview) profileImagePreview.style.display = 'none';
                        if(profileImagePlaceholder) profileImagePlaceholder.style.display = 'flex';
                         const navbarImg = document.getElementById('navbarProfileImage');
                         const navbarIcon = document.getElementById('navbarProfileIcon');
                         if(navbarImg) navbarImg.style.display = 'none';
                         if(navbarIcon) navbarIcon.style.display = 'inline-block'; // Mostrar icono si no hay imagen
                    }
                    // Actualizar nombre de usuario en navbar
                    const navbarUsername = document.getElementById('navbarUsername');
                    if(navbarUsername) navbarUsername.textContent = result.data.username;

                } else {
                    showSettingsToast(result.message || "Error al cargar datos del perfil.", true);
                }
            })
            .catch(error => {
                console.error("Error cargando perfil:", error);
                showSettingsToast("Error de conexión al cargar el perfil.", true);
            });
    }
    
    // Llamar a loadUserProfile si estamos en la página de configuración
    if (document.getElementById('updateProfileForm')) { // Verifica si estamos en la página correcta
        loadUserProfile();
    }


    // Previsualización de imagen de perfil
    if (profileImageInput && profileImagePreview && profileImagePlaceholder) {
        profileImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImagePreview.src = e.target.result;
                    profileImagePreview.style.display = 'block';
                    profileImagePlaceholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
        // Permitir clic en la imagen/placeholder para seleccionar archivo
        [profileImagePreview, profileImagePlaceholder].forEach(el => {
            el.addEventListener('click', () => profileImageInput.click());
        });
    }

    // Enviar formulario de actualización de perfil
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(updateProfileForm);

            // Validar contraseñas si se están cambiando
            const newPassword = formData.get('new_password');
            const confirmNewPassword = formData.get('confirm_new_password');
            const currentPassword = formData.get('current_password');

            if (newPassword || confirmNewPassword || currentPassword) { // Si se intenta cambiar la contraseña
                 if (!currentPassword) {
                    showSettingsToast("Debe ingresar su contraseña actual para cambiarla.", true);
                    return;
                }
                if (newPassword.length > 0 && newPassword.length < 6) {
                    showSettingsToast("La nueva contraseña debe tener al menos 6 caracteres.", true);
                    return;
                }
                if (newPassword !== confirmNewPassword) {
                    showSettingsToast("Las nuevas contraseñas no coinciden.", true);
                    return;
                }
            }
             if (!newPassword && !confirmNewPassword && currentPassword) {
                // Si solo se ingresó la actual pero no la nueva, no hacer nada con la contraseña
                // Opcional: mostrar un aviso. Por ahora, se ignora el cambio de contraseña.
                formData.delete('current_password'); // No enviar si no hay nueva contraseña
            }


            fetch('api/users.php?action=update_profile', { // Necesitaremos una nueva acción en api/users.php
                method: 'POST',
                body: formData // FormData maneja multipart/form-data automáticamente
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showSettingsToast(result.message || 'Perfil actualizado exitosamente.');
                    if (result.data) { // Si la API devuelve datos actualizados
                        if (result.data.username) {
                            document.getElementById('username').value = result.data.username;
                            // Actualizar nombre en navbar
                            const navbarUsername = document.getElementById('navbarUsername');
                            if(navbarUsername) navbarUsername.textContent = result.data.username;
                            // Actualizar en sesión (esto debería hacerlo el backend, pero para UI inmediata)
                             if (typeof currentUserForAdminUsers !== 'undefined' && currentUserForAdminUsers.id === result.data.id) {
                                currentUserForAdminUsers.username = result.data.username;
                            }
                        }
                        if (result.data.profile_image_url) {
                            profileImagePreview.src = result.data.profile_image_url + '?t=' + new Date().getTime();
                            profileImagePreview.style.display = 'block';
                            profileImagePlaceholder.style.display = 'none';
                             // Actualizar también la imagen del navbar
                            const navbarImg = document.getElementById('navbarProfileImage');
                            const navbarIcon = document.getElementById('navbarProfileIcon');
                            if (navbarImg) {
                                navbarImg.src = result.data.profile_image_url + '?t=' + new Date().getTime();
                                navbarImg.style.display = 'inline-block';
                            }
                            if (navbarIcon) navbarIcon.style.display = 'none';
                        }
                    }
                    // Limpiar campos de contraseña
                    document.getElementById('current_password').value = '';
                    document.getElementById('new_password').value = '';
                    document.getElementById('confirm_new_password').value = '';
                } else {
                    showSettingsToast(result.message || 'Error al actualizar el perfil.', true);
                }
            })
            .catch(error => {
                console.error('Error actualizando perfil:', error);
                showSettingsToast('Error de conexión al actualizar el perfil.', true);
            });
        });
    }

    // Lógica para cargar la imagen de perfil en el navbar en todas las páginas de admin
    // Esto podría estar en un script global o repetirse/adaptarse
    function loadNavbarProfileImage() {
        // Esta función asume que hay una forma de obtener la URL de la imagen del usuario actual
        // Por ejemplo, si 'currentUser' (de admin_dashboard) o 'currentUserForAdminUsers' (de admin_users) está disponible y tiene profile_image_url
        let userProfileUrl = null;
        if (typeof currentUser !== 'undefined' && currentUser.profileImageUrl) { // Asumiendo que 'currentUser' es el objeto global
            userProfileUrl = currentUser.profileImageUrl;
        } else if (typeof currentUserForAdminUsers !== 'undefined' && currentUserForAdminUsers.profileImageUrl) {
             userProfileUrl = currentUserForAdminUsers.profileImageUrl;
        }
        // Si no, podríamos hacer un fetch rápido para obtener solo la imagen del usuario actual
        // fetch('api/users.php?action=get_current_user_profile') ...

        // Simulación: si la API de get_profile en admin_settings.js ya lo hizo, no necesitamos más.
        // Pero si queremos que se cargue en CADA página del admin al inicio:
        const navbarImg = document.getElementById('navbarProfileImage');
        const navbarIcon = document.getElementById('navbarProfileIcon');
        const navbarUsernameEl = document.getElementById('navbarUsername');

        // Intentar obtener datos del perfil del usuario actual (ej. para el navbar)
        // Esto es un poco redundante si loadUserProfile() ya lo hace en admin_settings.js
        // pero es para asegurar que el navbar se actualice en todas las páginas del admin.
        if (navbarImg && navbarIcon && navbarUsernameEl) { // Solo si los elementos del navbar existen
            fetch('api/users.php?action=get_profile') // Asume que sin user_id, devuelve el perfil del usuario en sesión
                .then(res => res.json())
                .then(profile => {
                    if (profile.success && profile.data) {
                        if (navbarUsernameEl) navbarUsernameEl.textContent = profile.data.username;
                        if (profile.data.profile_image_url) {
                            navbarImg.src = profile.data.profile_image_url + '?t=' + new Date().getTime();
                            navbarImg.style.display = 'inline-block';
                            navbarIcon.style.display = 'none';
                        } else {
                            navbarImg.style.display = 'none';
                            navbarIcon.style.display = 'inline-block';
                        }
                    }
                }).catch(err => console.warn("No se pudo cargar la imagen del perfil para el navbar", err));
        }
    }
    loadNavbarProfileImage(); // Cargar al inicio para todas las páginas de admin que incluyan este JS (o un JS global)

});

