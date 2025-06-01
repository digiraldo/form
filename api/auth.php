<?php
// api/auth.php
session_start();

define('DATA_USERS_FILE_AUTH', __DIR__ . '/../data/users.json');

// Reutilizar la definición de PROFILE_IMG_BASE_URL si es posible
if (!defined('PROFILE_IMG_DIR_FROM_ROOT')) {
    define('PROFILE_IMG_DIR_FROM_ROOT', '/profile_images/');
}
if (!defined('PROFILE_IMG_BASE_URL')) {
    $protocol_auth = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host_auth = $_SERVER['HTTP_HOST'];
    // Ajuste para obtener correctamente la raíz del proyecto cuando está en un subdirectorio
    $script_path_auth = dirname($_SERVER['PHP_SELF']); // Directorio del script actual (api)
    $project_root_path_auth = dirname($script_path_auth); // Subir un nivel para llegar a la raíz del proyecto
    $project_root_path_auth = ($project_root_path_auth === '/' || $project_root_path_auth === '\\') ? '' : $project_root_path_auth; 
    define('PROFILE_IMG_BASE_URL', rtrim($protocol_auth . $host_auth . $project_root_path_auth, '/') . PROFILE_IMG_DIR_FROM_ROOT);
}


function get_all_users_auth() {
    if (!file_exists(DATA_USERS_FILE_AUTH)) return [];
    $json_data = file_get_contents(DATA_USERS_FILE_AUTH);
    if ($json_data === false) return [];
    $users = json_decode($json_data, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($users)) ? $users : [];
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = $_POST['username'] ?? null;
    $input_password = $_POST['password'] ?? null;

    if (empty(trim($input_username)) || empty($input_password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nombre de usuario y contraseña son requeridos.']);
        exit;
    }

    $users = get_all_users_auth();
    $username = trim($input_username);
    $found_user = null;

    foreach ($users as $user) {
        if (strtolower($user['username']) === strtolower($username)) {
            $found_user = $user;
            break;
        }
    }

    if ($found_user && password_verify($input_password, $found_user['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $found_user['id'];
        $_SESSION['admin_username'] = $found_user['username'];
        $_SESSION['user_role'] = $found_user['role'];

        // Establecer la URL de la imagen de perfil en la sesión
        if (!empty($found_user['profile_image_filename'])) {
            $_SESSION['profile_image_url'] = PROFILE_IMG_BASE_URL . $found_user['profile_image_filename'];
        } else {
            $_SESSION['profile_image_url'] = null; 
        }
        
        error_log("[API Auth] Login exitoso para " . $found_user['username'] . ". Profile Image URL en sesión: " . ($_SESSION['profile_image_url'] ?? 'NINGUNA'));

        echo json_encode([
            'success' => true, 
            'message' => 'Inicio de sesión exitoso.',
            'user' => [
                'id' => $found_user['id'],
                'username' => $found_user['username'],
                'role' => $found_user['role'],
                'profile_image_url' => $_SESSION['profile_image_url']
            ]
        ]);
    } else {
        http_response_code(401);
        error_log("[API Auth] Fallo de login para usuario: " . htmlspecialchars($username));
        echo json_encode(['success' => false, 'message' => 'Nombre de usuario o contraseña incorrectos.']);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>