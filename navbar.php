<?php
// navbar.php: Navbar unificado para panel de administración
if (session_status() === PHP_SESSION_NONE) session_start();

$admin_username = $_SESSION['admin_username'] ?? 'Usuario';
$user_email = $_SESSION['user_email'] ?? 'usuario@example.com';
$user_role = $_SESSION['user_role'] ?? 'editor';

// Mapeo de roles a español
$roles_en_espanol = [
    'owner' => 'Propietario',
    'admin' => 'Administrador',
    'editor' => 'Editor'
];
$user_role_display = $roles_en_espanol[strtolower($user_role)] ?? ucfirst($user_role);

// Priorizar la URL completa de la sesión si existe (establecida en login.php/api/auth.php)
$session_profile_image_url = $_SESSION['profile_image_url'] ?? null;
$profile_image_filename = $_SESSION['profile_image_filename'] ?? ''; // Mantener por si se usa en otro lado o como fallback

$profile_image_to_display = '';
$data_has_image = 'false';
$img_initial_display = 'none';
$icon_initial_display = 'inline-block';
$user_initial_for_span = !empty($admin_username) ? strtoupper(substr($admin_username, 0, 1)) : '?';

if (!empty($session_profile_image_url)) {
    // Si la URL de sesión existe, la usamos directamente.
    // Asumimos que esta URL ya está verificada y es accesible.
    $profile_image_to_display = htmlspecialchars($session_profile_image_url);
    // Para data_has_image, podríamos inferir de la URL o necesitar el nombre del archivo.
    // Si $profile_image_filename está en sesión y no está vacío, es una buena indicación.
    if (!empty($profile_image_filename)) { 
        $data_has_image = 'true';
        $img_initial_display = 'inline-block';
        $icon_initial_display = 'none';
    } else {
        // Si solo tenemos la URL de sesión pero no el nombre del archivo, es más difícil 
        // estar 100% seguro de que es una imagen de perfil subida vs un placeholder de placehold.co.
        // Por ahora, si hay URL de sesión, asumimos que es una imagen válida.
        // Esto podría necesitar un ajuste si placehold.co se guarda en $_SESSION['profile_image_url']
        $data_has_image = 'true'; // Asumir true si hay URL
        $img_initial_display = 'inline-block';
        $icon_initial_display = 'none';
    }
} elseif (!empty($profile_image_filename) && file_exists(__DIR__ . '/profile_images/' . $profile_image_filename)) {
    // Fallback: si no hay URL en sesión, pero sí nombre de archivo y el archivo existe localmente
    $profile_image_to_display = 'profile_images/' . htmlspecialchars($profile_image_filename) . '?t=' . time();
    $data_has_image = 'true';
    $img_initial_display = 'inline-block';
    $icon_initial_display = 'none';
} else {
    // Si no hay ni URL en sesión ni archivo local válido, no hay imagen que mostrar.
    // $profile_image_to_display se queda vacía, se mostrará el placeholder.
    // $img_initial_display = 'none';
    // $icon_initial_display = 'inline-block';
}

$form_count = $GLOBALS['form_count'] ?? 0; 
$user_count = $GLOBALS['user_count'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);

?>
<!-- Bootstrap 5 y Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; transition: background-color 0.3s, color 0.3s; }
.navbar { transition: background-color 0.3s, border-color 0.3s; }
#theme-toggler { border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; padding: 0; }
#theme-toggler i { font-size: 1.2rem; transition: transform 0.4s cubic-bezier(0.68,-0.55,0.27,1.55); }
.profile-image { width: 36px; height: 36px; object-fit: cover; border-radius: 50%; border: 2px solid #adb5bd; background: #fff; }
.user-profile-name { font-weight: 500; font-size: 1.05rem; color: var(--bs-body-color, #222); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.role-badge { display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; width: 32px; height: 32px; font-size: 1.25rem; margin-left: 0.5rem; box-shadow: 0 2px 8px rgba(13,110,253,0.10); }
.role-badge.owner { background: #d1f7d6; color: #198754; border: 2px solid #198754; }
.role-badge.admin { background: #e7f1ff; color: #0d6efd; border: 2px solid #0d6efd; }
.role-badge.editor { background: #fffbe6; color: #ffc107; border: 2px solid #ffc107; }
@media (max-width: 991.98px) {
  .user-profile-name { font-size: 0.9rem; }
  .navbar-collapse .d-flex.align-items-center { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--bs-border-color-translucent); }
  .role-badge { width: 36px; height: 36px; font-size: 1.4rem; }
}
.navbar.shadow-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important; }
</style>
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">
            <i class="bi bi-journal-richtext me-2"></i>Formularios
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavContent" aria-controls="navbarNavContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'admin_dashboard.php') ? 'active' : ''; ?>" href="admin_dashboard.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        <span class="badge rounded-pill bg-primary ms-1 <?php echo ($form_count == 0) ? 'd-none' : ''; ?>"><?php echo $form_count; ?></span>
                    </a>
                </li>                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['owner', 'admin'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'admin_users.php') ? 'active' : ''; ?>" href="admin_users.php">
                        <i class="bi bi-people-fill me-1"></i>Usuarios
                        <span class="badge rounded-pill bg-success ms-1 <?php echo ($user_count == 0) ? 'd-none' : ''; ?>"><?php echo $user_count; ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'admin_backup.php') ? 'active' : ''; ?>" href="admin_backup.php">
                        <i class="bi bi-cloud-arrow-down me-1"></i>Backups
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'admin_settings.php') ? 'active' : ''; ?>" href="admin_settings.php">
                        <i class="bi bi-person-gear me-1"></i>Mi Cuenta
                    </a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center ms-lg-auto">
                <button class="btn me-2 me-lg-3" id="theme-toggler" type="button" aria-label="Cambiar tema">
                    <i class="bi bi-sun-fill"></i> <!-- Icono inicial, JS lo cambiará -->
                </button>

                <div class="d-flex align-items-center text-decoration-none flex-nowrap" id="user-profile-container">
                    <a href="admin_settings.php" 
                       class="d-flex align-items-center text-decoration-none flex-nowrap me-3" 
                       id="user-profile-link" 
                       data-bs-toggle="tooltip" 
                       data-bs-placement="bottom" 
                       title="<?php echo htmlspecialchars($user_role_display); ?>">
                        <img src="<?php echo $profile_image_to_display; ?>" 
                             id="userProfileImage" 
                             data-has-image="<?php echo $data_has_image; ?>"
                             class="rounded-circle me-2 profile-image" 
                             alt="Imagen de perfil"
                             style="display: <?php echo $img_initial_display; ?>;"
                             onerror="this.style.display='none'; document.getElementById('userProfileIconPlaceholder').style.display='inline-block'; this.src='';">
                        <span id="userProfileIconPlaceholder" 
                              class="rounded-circle me-2 profile-image-placeholder" 
                              style="display: <?php echo $icon_initial_display; ?>; width: 36px; height: 36px; background-color: #7C3AED; color: white; text-align: center; line-height: 36px; font-weight: bold; font-size: 1.1rem;">
                          <?php echo $user_initial_for_span; ?>
                        </span>
                        <span class="user-profile-name d-none d-sm-inline text-body-emphasis"><?php echo htmlspecialchars($admin_username); ?></span>
                        <?php
                        // Icono de rol visual junto al nombre
                        $role_icon = '';
                        $role_icon_class = '';
                        if ($user_role === 'owner') {
                            $role_icon = 'fa-user-tie';
                            $role_icon_class = 'text-success';
                        } elseif ($user_role === 'admin') {
                            $role_icon = 'fa-user-shield';
                            $role_icon_class = 'text-primary';
                        } elseif ($user_role === 'editor') {
                            $role_icon = 'fa-user-pen';
                            $role_icon_class = 'text-warning';
                        }
                        if ($role_icon) {
                            echo '<span class="ms-2 d-inline-block align-middle"><i class="fa-solid ' . $role_icon . ' ' . $role_icon_class . '" style="font-size:1.3rem;vertical-align:middle;"></i></span>';
                        }
                        ?>
                    </a>
                </div>
                <a href="logout.php" class="btn btn-outline-danger d-flex align-items-center" title="Cerrar Sesión" id="logout-button">
                    <i class="bi bi-box-arrow-right fs-5 me-1 me-sm-2"></i><span class="d-none d-sm-inline">Salir</span>
                </a> 
            </div>
        </div>
    </div>
</nav>
<!-- El script inline de theme toggler se elimina para evitar conflicto con js/navbar.js -->
