<?php
// api/users.php
// Iniciar sesión solo si no está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DATA_USERS_FILE', __DIR__ . '/../data/users.json');
define('PROFILE_IMG_DIR_FROM_ROOT', '/profile_images/'); 
define('PROFILE_IMG_DIR_ABSOLUTE', dirname(__DIR__) . PROFILE_IMG_DIR_FROM_ROOT); 

// Verificar que las variables del servidor estén disponibles
$server_port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $server_port == 443) ? "https://" : "http://";
$host = $http_host;
$project_root_path = dirname($php_self, 2); 
$project_root_path = ($project_root_path === '/' || $project_root_path === '\\') ? '' : $project_root_path; 
define('PROFILE_IMG_BASE_URL', rtrim($protocol . $host . $project_root_path, '/') . PROFILE_IMG_DIR_FROM_ROOT);


function ensure_owner() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'owner') {
        http_response_code(403); 
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Esta acción requiere privilegios de propietario.']);
        exit;
    }
}
function ensure_logged_in() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['user_id'])) {
        http_response_code(401); 
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Debes iniciar sesión.']);
        exit;
    }
}

function get_all_users() {
    if (!file_exists(DATA_USERS_FILE)) return [];
    $json_data = file_get_contents(DATA_USERS_FILE);
    if ($json_data === false) return [];
    $users = json_decode($json_data, true);
    return (json_last_error() === JSON_ERROR_NONE && is_array($users)) ? $users : [];
}

function save_all_users($users) {
    if (!is_dir(dirname(DATA_USERS_FILE))) {
        if(!mkdir(dirname(DATA_USERS_FILE), 0775, true) && !is_dir(dirname(DATA_USERS_FILE))) {
            error_log("Error: No se pudo crear el directorio de datos: " . dirname(DATA_USERS_FILE));
            return false;
        }
    }
    $json_data = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_data === false) return false;
    return file_put_contents(DATA_USERS_FILE, $json_data) !== false;
}

function generate_user_id() {
    return 'user_' . bin2hex(random_bytes(3)); 
}

function get_user_by_id($user_id) {
    $users = get_all_users();
    foreach ($users as $user) {
        if ($user['id'] === $user_id) {
            return $user;
        }
    }
    return null;
}

// Solo ejecutar la lógica de routing si se accede directamente a este archivo
if (basename($_SERVER['PHP_SELF']) === 'users.php') {
    $action = $_REQUEST['action'] ?? null;
$current_session_user_id = $_SESSION['user_id'] ?? null;
$current_session_user_role = $_SESSION['user_role'] ?? null;

// Asegurarse de que 'update_user' y 'delete_user' estén en la lista de acciones protegidas
if (in_array($action, ['list', 'create', 'edit_role', 'delete', 'update_profile', 'get_profile', 'update_user', 'delete_user'])) { 
    ensure_logged_in(); 
    // Solo el owner puede crear admins, pero los admins pueden crear editores
    if ($action === 'create') {
        if ($current_session_user_role === 'owner') {
            // Owner puede crear cualquier usuario
        } elseif ($current_session_user_role === 'admin') {
            // Admin solo puede crear editores
            if (isset($_POST['role']) && $_POST['role'] !== 'editor') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Solo puedes crear usuarios editores.']);
                exit;
            }
        } else {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para crear usuarios.']);
            exit;
        }
    } else if (in_array($action, ['edit_role']) && $current_session_user_role !== 'owner') { 
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos insuficientes para la acción del usuario.']);
        exit;
    }
    // Para update_user y delete_user, la validación fina se hace dentro de cada case
}


switch ($action) {
    case 'list':
        header('Content-Type: application/json');        $users = get_all_users();
        $current_user_id = $_SESSION['user_id'] ?? null;
        $current_user_role = $_SESSION['user_role'] ?? null;
        $user_areas = null;
        foreach ($users as $u) {
            if ($u['id'] === $current_user_id) {
                // Filtrar valores null/undefined de los arrays de áreas
                $areas_admin = isset($u['areas_admin']) && is_array($u['areas_admin']) 
                    ? array_filter($u['areas_admin'], function($area) { return $area !== null && $area !== ''; }) 
                    : [];
                $areas_editor = isset($u['areas_editor']) && is_array($u['areas_editor']) 
                    ? array_filter($u['areas_editor'], function($area) { return $area !== null && $area !== ''; }) 
                    : [];
                    
                $user_areas = [
                    'areas_admin' => $areas_admin,
                    'areas_editor' => $areas_editor
                ];
                break;
            }        }
        $users_safe = array_filter($users, function($user) use ($current_user_role, $user_areas, $current_user_id) {
            if ($current_user_role === 'owner') return true;
            if ($current_user_role === 'admin') {
                // Solo ver editores de sus áreas
                if ($user['role'] === 'editor') {
                    $editor_areas = isset($user['areas_editor']) && is_array($user['areas_editor']) 
                        ? array_filter($user['areas_editor'], function($area) { return $area !== null && $area !== ''; }) 
                        : [];
                    $admin_areas = $user_areas['areas_admin'];
                    
                    foreach ($editor_areas as $area) {
                        if (in_array($area, $admin_areas)) return true;
                    }
                }
                // También puede verse a sí mismo
                if ($user['id'] === $current_user_id) return true;
                return false;
            }
            if ($current_user_role === 'editor') {
                // Solo puede verse a sí mismo
                return $user['id'] === $current_user_id;
            }
            return false;
        });
        $users_safe = array_map(function($user) {
            unset($user['password']);
            if (!empty($user['profile_image_filename'])) {
                $user['profile_image_url'] = PROFILE_IMG_BASE_URL . $user['profile_image_filename'];
            } else {
                $user['profile_image_url'] = null; 
            }
            return $user;
        }, $users_safe);
        echo json_encode(['success' => true, 'data' => array_values($users_safe)]);
        break;

    case 'create_initial_owner':
        header('Content-Type: application/json');

        // Verificar si ya existen usuarios
        $existing_users = get_all_users();
        if (!empty($existing_users)) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'La configuración inicial ya se ha realizado. Ya existen usuarios.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_username = $_POST['username'] ?? null;
            $input_password = $_POST['password'] ?? null;
            // El rol es fijo a 'owner' para la configuración inicial

            if (empty(trim($input_username)) || empty($input_password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos inválidos. Se requieren nombre de usuario y contraseña.']);
                exit;
            }
            
            if (strlen($input_password) < 6) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
                 exit;
            }

            $username = trim($input_username);
            $new_user_id = generate_user_id();
            $profile_image_filename = null;

            if (!is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                if (!@mkdir(PROFILE_IMG_DIR_ABSOLUTE, 0775, true) && !is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                     error_log("[API Users - create_initial_owner] Error: No se pudo crear el directorio de imágenes de perfil: " . PROFILE_IMG_DIR_ABSOLUTE);
                }
            }
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_tmp_name = $_FILES['profile_image']['tmp_name'];
                $file_type = ''; 
                $image_processing_possible = true;

                if (function_exists('mime_content_type')) {
                    $file_type = mime_content_type($file_tmp_name); 
                } else {
                    error_log("[API Users - create_initial_owner] ADVERTENCIA: La función mime_content_type no existe. La extensión fileinfo de PHP podría no estar habilitada. No se puede verificar el tipo MIME del archivo de forma segura.");
                    // Como fallback, podríamos verificar la extensión del archivo, pero es menos seguro.
                    // O podríamos usar $_FILES['profile_image']['type'] que envía el navegador, pero tampoco es seguro.
                    // Por ahora, si mime_content_type no está, no procesaremos la imagen para evitar errores y por seguridad.
                    $image_processing_possible = false; 
                    // Opcionalmente, podrías añadir un mensaje de error específico para el usuario aquí si la imagen es obligatoria
                    // o si quieres informar sobre la imposibilidad de procesar la imagen.
                }
                
                $file_size = $_FILES['profile_image']['size'];

                if ($image_processing_possible && !empty($file_type) && in_array($file_type, $allowed_types) && $file_size <= 2 * 1024 * 1024) { // 2MB
                    $extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    // Validar extensión además del tipo MIME como una capa extra.
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        error_log("[API Users - create_initial_owner] Extensión de archivo no permitida: " . $extension);
                    } else {
                        $profile_image_filename = $new_user_id . '.' . $extension;
                        $target_path = PROFILE_IMG_DIR_ABSOLUTE . $profile_image_filename;

                        if (!move_uploaded_file($file_tmp_name, $target_path)) {
                            $profile_image_filename = null; 
                            error_log("[API Users - create_initial_owner] Error al mover el archivo subido para el usuario: " . $new_user_id . " a " . $target_path);
                        }
                    }
                } else if ($image_processing_possible) { // Solo registrar si el procesamiento era posible pero falló la validación
                     error_log("[API Users - create_initial_owner] Tipo de archivo no permitido ('".$file_type."') o tamaño excedido (".$file_size.") para el usuario: " . $new_user_id);
                }
            } else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                error_log("[API Users - create_initial_owner] Error de subida de archivo: Código " . $_FILES['profile_image']['error']);
            }

            $new_owner_user = [
                'id' => $new_user_id,
                'username' => htmlspecialchars($username),
                'password' => password_hash($input_password, PASSWORD_DEFAULT),
                'role' => 'owner', 
                'profile_image_filename' => $profile_image_filename, 
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $users_to_save = [$new_owner_user]; // Este es el primer y único usuario

            if (save_all_users($users_to_save)) {
                unset($new_owner_user['password']); 
                if ($profile_image_filename) {
                    $new_owner_user['profile_image_url'] = PROFILE_IMG_BASE_URL . $profile_image_filename;
                } else {
                    $new_owner_user['profile_image_url'] = null;
                }
                unset($new_owner_user['profile_image_filename']); 

                echo json_encode(['success' => true, 'message' => 'Usuario propietario inicial creado exitosamente.', 'data' => $new_owner_user]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al guardar el usuario propietario inicial.']);
            }
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
        break; // BREAK para case 'create_initial_owner'

    case 'create': 
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input_username = $_POST['username'] ?? null;
            $input_password = $_POST['password'] ?? null;
            $input_role = $_POST['role'] ?? null;
            $input_area_id = $_POST['area_id'] ?? null; // Nuevo: área a la que se asigna el usuario
            // Validar rol y permisos según el usuario en sesión
            if ($current_session_user_role === 'owner') {
                // Owner puede crear admins y editores
                if (!in_array($input_role, ['admin', 'editor'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Rol inválido.']);
                    exit;
                }
            } elseif ($current_session_user_role === 'admin') {
                // Admin solo puede crear editores para su área
                if ($input_role !== 'editor') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Solo puedes crear editores.']);
                    exit;
                }
                // Forzar área a una de las áreas del admin
                $admin_areas = get_areas_by_admin($current_session_user_id);
                if (!$input_area_id || !in_array($input_area_id, $admin_areas)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Solo puedes asignar editores a tus áreas.']);
                    exit;
                }
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para crear usuarios.']);
                exit;
            }
            if (empty(trim($input_username)) || empty($input_password) || !$input_role) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos inválidos. Nombre de usuario, contraseña y rol son requeridos.']);
                exit;
            }
            $users = get_all_users();
            $username = trim($input_username);
            foreach ($users as $user) {
                if (strtolower($user['username']) === strtolower($username)) {
                    http_response_code(409); 
                    echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya existe.']);
                    exit;
                }
            }
            $new_user_id = generate_user_id();
            $profile_image_filename = null;

            if (!is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                if (!@mkdir(PROFILE_IMG_DIR_ABSOLUTE, 0775, true) && !is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                     error_log("[API Users - create] Error: No se pudo crear el directorio de imágenes de perfil: " . PROFILE_IMG_DIR_ABSOLUTE . " - " . (error_get_last()['message'] ?? 'Error desconocido'));
                }
            }
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_tmp_name = $_FILES['profile_image']['tmp_name'];
                $file_type = '';
                if (function_exists('mime_content_type')) {
                    $file_type = mime_content_type($file_tmp_name); 
                } else {
                    error_log("[API Users - create] La función mime_content_type no existe. La extensión fileinfo de PHP podría no estar habilitada.");
                }
                $file_size = $_FILES['profile_image']['size'];
                if (!empty($file_type) && in_array($file_type, $allowed_types) && $file_size <= 2 * 1024 * 1024) { 
                    $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $profile_image_filename = $new_user_id . '.' . strtolower($extension);
                    $target_path = PROFILE_IMG_DIR_ABSOLUTE . $profile_image_filename;
                    if (!move_uploaded_file($file_tmp_name, $target_path)) {
                        $profile_image_filename = null; 
                        error_log("[API Users - create] Error al mover el archivo subido para el usuario: " . $new_user_id . " a " . $target_path);
                    }
                }
            } // <-- cierre correcto del if de la imagen

            $new_user = [
                'id' => $new_user_id,
                'username' => htmlspecialchars($username),
                'password' => password_hash($input_password, PASSWORD_DEFAULT),
                'role' => $input_role, 
                'profile_image_filename' => $profile_image_filename, 
                'created_at' => date('Y-m-d H:i:s')
            ];
            $users[] = $new_user;
            if (save_all_users($users)) {
                // Asignar usuario a área en areas.json
                if ($input_role === 'admin' || $input_role === 'editor') {
                    asignar_usuario_a_area($new_user_id, $input_role, $input_area_id);
                }
                unset($new_user['password']); 
                if ($profile_image_filename) {
                    $new_user['profile_image_url'] = PROFILE_IMG_BASE_URL . $profile_image_filename;
                } else {
                    $new_user['profile_image_url'] = null;
                }
                unset($new_user['profile_image_filename']); 
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.', 'data' => $new_user]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al guardar el nuevo usuario.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
        break; // BREAK para case 'create'

    case 'get_profile': 
        header('Content-Type: application/json');
        ensure_logged_in();
        
        $user_id_to_get = $_GET['user_id'] ?? $current_session_user_id; 

        if (isset($_GET['user_id']) && $_GET['user_id'] !== $current_session_user_id && $current_session_user_role !== 'owner') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para ver este perfil.']);
            exit;
        }
        
        $user_data = get_user_by_id($user_id_to_get);

        if ($user_data) {
            unset($user_data['password']);
            if (!empty($user_data['profile_image_filename'])) {
                $user_data['profile_image_url'] = PROFILE_IMG_BASE_URL . $user_data['profile_image_filename'];
            } else {
                $user_data['profile_image_url'] = null;
            }
            unset($user_data['profile_image_filename']);
            echo json_encode(['success' => true, 'data' => $user_data]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        }
        break; // BREAK para case 'get_profile'

    case 'update_profile': 
        ensure_logged_in();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("------ [API Users - update_profile] INICIO ------");
            error_log("[API Users - update_profile] Request Content-Type: " . ($_SERVER["CONTENT_TYPE"] ?? "No especificado")); // NUEVO LOG
            error_log("[API Users - update_profile] Usuario en sesión ID: " . $current_session_user_id);
            error_log("[API Users - update_profile] Datos POST: " . print_r($_POST, true));
            error_log("[API Users - update_profile] Datos FILES: " . print_r($_FILES, true)); 

            $user_id_to_update = $_POST['user_id'] ?? null;

            if ($user_id_to_update !== $current_session_user_id) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No puedes actualizar el perfil de otro usuario.']);
                exit;
            }

            $users = get_all_users();
            $user_index = -1;
            $current_user_data = null;

            foreach ($users as $index => $user) {
                if ($user['id'] === $user_id_to_update) {
                    $user_index = $index;
                    $current_user_data = $user;
                    break;
                }
            }

            if ($user_index === -1) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado para actualizar.']);
                exit;
            }

            $updated_data_for_response = [];
            // Inicializar con los datos actuales para que la respuesta siempre los tenga
            $updated_data_for_response['username'] = $current_user_data['username'];
            if (!empty($current_user_data['profile_image_filename'])) {
                $updated_data_for_response['profile_image_url'] = PROFILE_IMG_BASE_URL . $current_user_data['profile_image_filename'] . '?t=' . time();
            } else {
                $updated_data_for_response['profile_image_url'] = null;
            }

            $changes_made_to_json = false;
            $message = "No se realizaron cambios de datos en el perfil."; 
            $image_upload_error_message = null;
            $image_changed_successfully = false;


            // Actualizar nombre de usuario
            if (isset($_POST['username']) && !empty(trim($_POST['username'])) && trim($_POST['username']) !== $current_user_data['username']) {
                $new_username = trim($_POST['username']);
                // Validar si el nuevo nombre de usuario ya existe para otro usuario
                foreach ($users as $user_check) {
                    if (strtolower($user_check['username']) === strtolower($new_username) && $user_check['id'] !== $user_id_to_update) {
                        http_response_code(409); // Conflict
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Ese nombre de usuario ya está en uso por otra cuenta.']);
                        exit;
                    }
                }
                $users[$user_index]['username'] = htmlspecialchars($new_username);
                $updated_data_for_response['username'] = $users[$user_index]['username'];
                $_SESSION['admin_username'] = $users[$user_index]['username']; // Actualizar nombre en sesión
                $changes_made_to_json = true;
            }

            // Actualizar contraseña
            if (isset($_POST['new_password']) && !empty($_POST['new_password'])) {
                if (empty($_POST['current_password'])) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Se requiere la contraseña actual para establecer una nueva.']);
                    exit;
                }
                if (!password_verify($_POST['current_password'], $current_user_data['password'])) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
                    exit;
                }
                if (strlen($_POST['new_password']) < 6) {
                     http_response_code(400);
                     header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
                    exit;
                }
                if ($_POST['new_password'] !== $_POST['confirm_new_password']) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                   echo json_encode(['success' => false, 'message' => 'Las nuevas contraseñas no coinciden.']);
                   exit;
               }
                $users[$user_index]['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $changes_made_to_json = true;
            }
            
            // --- Manejo de la imagen de perfil ---
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                error_log("[API Users - update_profile] Archivo 'profile_image' recibido. Procesando...");
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_tmp_name = $_FILES['profile_image']['tmp_name'];
                $file_type = mime_content_type($file_tmp_name);
                $file_size = $_FILES['profile_image']['size'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($file_type, $allowed_types)) {
                    $image_upload_error_message = "Tipo de archivo no permitido para la imagen (solo JPG, PNG, GIF).";
                } else if ($file_size > $max_size) {
                    $image_upload_error_message = "El archivo de imagen es demasiado grande (máximo 2MB).";
                } else {
                    $old_image_filename = $current_user_data['profile_image_filename'] ?? null;
                    $extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $new_profile_image_filename = $user_id_to_update . '.' . $extension;
                    $target_path = PROFILE_IMG_DIR_ABSOLUTE . $new_profile_image_filename;

                    if (!is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                        if (!@mkdir(PROFILE_IMG_DIR_ABSOLUTE, 0775, true) && !is_dir(PROFILE_IMG_DIR_ABSOLUTE)) {
                            $image_upload_error_message = "Error crítico: No se pudo crear el directorio de imágenes.";
                            error_log($image_upload_error_message . " Path: " . PROFILE_IMG_DIR_ABSOLUTE);
                        }
                    }

                    if (!$image_upload_error_message) {
                        if (move_uploaded_file($file_tmp_name, $target_path)) {
                            if ($old_image_filename && $old_image_filename !== $new_profile_image_filename && file_exists(PROFILE_IMG_DIR_ABSOLUTE . $old_image_filename)) {
                                @unlink(PROFILE_IMG_DIR_ABSOLUTE . $old_image_filename);
                            }
                            $users[$user_index]['profile_image_filename'] = $new_profile_image_filename;
                            $updated_data_for_response['profile_image_url'] = PROFILE_IMG_BASE_URL . $new_profile_image_filename . '?t=' . time();
                            $changes_made_to_json = true;
                            $image_changed_successfully = true;
                        } else {
                            $image_upload_error_message = "Error al guardar la nueva imagen de perfil.";
                            error_log("[API Users - update_profile] Error al mover el archivo subido: " . $user_id_to_update . " a " . $target_path . " - PHP Error: " . (error_get_last()['message'] ?? 'Unknown error'));
                        }
                    }
                }
            }
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $image_upload_error_message = "Error al subir la imagen (Código: " . $_FILES['profile_image']['error'] . ").";
                error_log("[API Users - update_profile] Error de subida de archivo: " . $image_upload_error_message);
            }
            // --- Fin Manejo de la imagen de perfil ---

            // Determinar mensaje final y estado de éxito
            $response_success = true;
            if ($changes_made_to_json) {
                if (!save_all_users($users)) {
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudieron guardar los cambios en el archivo de usuarios.']);
                    exit;
                }
                // Si la imagen de perfil se actualizó exitosamente, actualizar la sesión
                if ($image_changed_successfully && isset($updated_data_for_response['profile_image_url'])) {
                    $_SESSION['profile_image_url'] = $updated_data_for_response['profile_image_url'];
                }
            }
            
            // Si el nombre de usuario cambió en sesión, actualizarlo
            if(isset($updated_data_for_response['username']) && $_SESSION['admin_username'] !== $updated_data_for_response['username']){
                $_SESSION['admin_username'] = $updated_data_for_response['username'];
            }


            header('Content-Type: application/json');
            echo json_encode([
                'success' => $response_success,
                'message' => $message,
                'data' => $updated_data_for_response
            ]);
            exit;

        } else {
            http_response_code(405); // Method Not Allowed
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }
        break; // BREAK para case 'update_profile'

    case 'update_user': 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("------ [API Users - update_user] INICIO ------");
            error_log("[API Users - update_user] Datos POST: " . print_r($_POST, true));
            error_log("[API Users - update_user] Datos FILES: " . print_r($_FILES, true));

            $user_id_to_update = $_POST['user_id'] ?? null;
            $new_role = $_POST['role'] ?? null;
            $new_area_id = $_POST['area_id'] ?? null;
            $users = get_all_users();
            $user_index = -1;
            $user_to_update = null;
            foreach ($users as $index => $user) {
                if ($user['id'] === $user_id_to_update) {
                    $user_index = $index;
                    $user_to_update = $user;
                    break;
                }
            }
            if ($user_index === -1) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
                exit;
            }
            // Validación de permisos para admin
            if ($current_session_user_role === 'admin') {
                // Solo puede editar editores de sus áreas
                $admin_areas = get_areas_by_admin($current_session_user_id);
                $editores_de_mis_areas = get_editores_de_areas($admin_areas);
                if ($user_to_update['role'] !== 'editor' || !in_array($user_to_update['id'], $editores_de_mis_areas)) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Solo puedes editar editores de tus áreas.']);
                    exit;
                }            }
            // Actualizar rol            
            $old_role = $users[$user_index]['role'];
            
            // Verificar si hay un cambio de rol
            if ($old_role !== $new_role) {
                error_log("[API Users - update_user] Cambio de rol detectado: $old_role -> $new_role");
                $users[$user_index]['role'] = $new_role;
                  // Inicializar arrays de áreas si no existen
                if (!isset($users[$user_index]['areas_admin'])) {
                    $users[$user_index]['areas_admin'] = [];
                } else {
                    // Filtrar valores null/undefined
                    $users[$user_index]['areas_admin'] = array_filter($users[$user_index]['areas_admin'], function($area) {
                        return $area !== null && $area !== '';
                    });
                }
                
                if (!isset($users[$user_index]['areas_editor'])) {
                    $users[$user_index]['areas_editor'] = [];
                } else {
                    // Filtrar valores null/undefined
                    $users[$user_index]['areas_editor'] = array_filter($users[$user_index]['areas_editor'], function($area) {
                        return $area !== null && $area !== '';
                    });
                }
                
                // Si cambiamos de editor a admin o viceversa, aseguramos que mantenga áreas apropiadas
                if ($old_role === 'editor' && $new_role === 'admin') {
                    error_log("[API Users - update_user] Promoción de editor a admin");
                    // Si no hay área nueva especificada pero tiene áreas como editor, usar la primera
                    if (!$new_area_id && !empty($users[$user_index]['areas_editor'])) {
                        $new_area_id = $users[$user_index]['areas_editor'][0];
                        error_log("[API Users - update_user] Usando área de editor existente: $new_area_id");
                    }
                } else if ($old_role === 'admin' && $new_role === 'editor') {
                    error_log("[API Users - update_user] Cambio de admin a editor");
                    // Si no hay área nueva especificada pero tiene áreas como admin, usar la primera
                    if (!$new_area_id && !empty($users[$user_index]['areas_admin'])) {
                        $new_area_id = $users[$user_index]['areas_admin'][0];
                        error_log("[API Users - update_user] Usando área de admin existente: $new_area_id");
                    }
                }
            }
            
            if (save_all_users($users)) {
                // Actualizar asignación en areas.json si hay un área especificada
                if (($new_role === 'admin' || $new_role === 'editor') && $new_area_id) {
                    asignar_usuario_a_area($user_id_to_update, $new_role, $new_area_id);
                }
                http_response_code(200);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente.']);
            } else {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Error al guardar los cambios.']);
            }
        } else {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
        break; // BREAK para case 'update_user'

    case 'delete_user': 
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
            // Validación: solo owner y admin pueden eliminar usuarios
            if ($current_session_user_role !== 'owner' && $current_session_user_role !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar usuarios.']);
                exit;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $user_id_to_delete = $input['user_id'] ?? null;
            if (!$user_id_to_delete) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado.']);
                exit;
            }
            $users = get_all_users();
            $user_found_for_delete = false;
            $users_after_delete = [];
            $deleted_user_image_filename = null; 
            $user_to_delete = null;
            foreach ($users as $user) {
                if ($user['id'] === $user_id_to_delete) {
                    $user_to_delete = $user;
                    if ($user['role'] === 'owner') {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'El propietario no puede ser eliminado.']);
                        exit;
                    }
                    // --- Validación de permisos para admin ---
                    if ($current_session_user_role === 'admin') {
                        $admin_areas = get_areas_by_admin($current_session_user_id);
                        $editores_de_mis_areas = get_editores_de_areas($admin_areas);
                        if ($user['role'] !== 'editor' || !in_array($user['id'], $editores_de_mis_areas)) {
                            http_response_code(403);
                            echo json_encode(['success' => false, 'message' => 'Solo puedes eliminar editores de tus áreas.']);
                            exit;
                        }
                    }
                    // --- Fin validación permisos admin ---
                    $user_found_for_delete = true;
                    $deleted_user_image_filename = $user['profile_image_filename'] ?? null;
                } else {
                    $users_after_delete[] = $user;
                }
            }
            if ($user_found_for_delete) {
                if (save_all_users($users_after_delete)) {
                    if ($deleted_user_image_filename) {
                        $image_path = PROFILE_IMG_DIR_ABSOLUTE . $deleted_user_image_filename;
                        if (file_exists($image_path)) {
                            @unlink($image_path); 
                        }
                    }
                    echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Error al guardar cambios.']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        }
        break; // BREAK para case 'delete_user'

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción de usuario no válida o no especificada.']);
        break;
}

// Funciones auxiliares para áreas
function get_areas_by_admin($admin_id) {
    $areas = json_decode(file_get_contents(__DIR__ . '/../data/areas.json'), true);
    $result = [];
    foreach ($areas as $area) {
        if (in_array($admin_id, $area['admins'])) {
            $result[] = $area['id'];
        }
    }
    return $result;
}
function get_editores_de_areas($areas_ids) {
    $areas = json_decode(file_get_contents(__DIR__ . '/../data/areas.json'), true);
    $editores = [];
    foreach ($areas as $area) {
        if (in_array($area['id'], $areas_ids)) {
            $editores = array_merge($editores, $area['editors']);
        }
    }
    return $editores;
}
function asignar_usuario_a_area($user_id, $rol, $area_id) {
    $areas_path = __DIR__ . '/../data/areas.json';
    $areas = json_decode(file_get_contents($areas_path), true);
    
    // Primero asignamos el usuario al área seleccionada con el rol correcto
    foreach ($areas as &$area) {
        if ($area['id'] === $area_id) {
            // Añadir al usuario al área seleccionada con el rol especificado
            if ($rol === 'admin' && !in_array($user_id, $area['admins'])) {
                $area['admins'][] = $user_id;
                
                // Si el usuario estaba como editor en esta área, lo mantenemos ahí también
                // para permitir roles múltiples en la misma área
                if (in_array($user_id, $area['editors'])) {
                    // Opcional: si quieres implementar roles exclusivos, 
                    // descomentar la siguiente línea:
                    // $key = array_search($user_id, $area['editors']); unset($area['editors'][$key]);
                }
            }
            
            if ($rol === 'editor' && !in_array($user_id, $area['editors'])) {
                $area['editors'][] = $user_id;
                
                // Si el usuario estaba como admin en esta área, lo mantenemos ahí también
                // para permitir roles múltiples en la misma área
                if (in_array($user_id, $area['admins'])) {
                    // Opcional: si quieres implementar roles exclusivos, 
                    // descomentar la siguiente línea:
                    // $key = array_search($user_id, $area['admins']); unset($area['admins'][$key]);
                }
            }
        }
        // Ya no eliminamos al usuario de otras áreas para mantener su presencia multi-área
    }
    
    // Actualizar el archivo de áreas
    file_put_contents($areas_path, json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Actualizar también los arrays en el usuario
    $users_path = __DIR__ . '/../data/users.json';
    $users = json_decode(file_get_contents($users_path), true);        foreach ($users as &$user) {
            if ($user['id'] === $user_id) {
                // Asegurarnos de que existan los arrays necesarios
                if (!isset($user['areas_admin'])) {
                    $user['areas_admin'] = [];
                } else {
                    // Filtrar valores null/undefined
                    $user['areas_admin'] = array_filter($user['areas_admin'], function($area) {
                        return $area !== null && $area !== '';
                    });
                }
                
                if (!isset($user['areas_editor'])) {
                    $user['areas_editor'] = [];
                } else {
                    // Filtrar valores null/undefined
                    $user['areas_editor'] = array_filter($user['areas_editor'], function($area) {
                        return $area !== null && $area !== '';
                    });
                }
                
                // Actualizar el array correspondiente
                if ($rol === 'admin' && !in_array($area_id, $user['areas_admin'])) {
                    $user['areas_admin'][] = $area_id;
                }
            if ($rol === 'editor' && !in_array($area_id, $user['areas_editor'])) {
                $user['areas_editor'][] = $area_id;
            }
            
            break; // Ya encontramos al usuario, no necesitamos seguir buscando
        }
    }
    
    // Guardar los cambios en el archivo de usuarios
    file_put_contents($users_path, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

} // Fin del if (basename($_SERVER['PHP_SELF']) === 'users.php')
?>
